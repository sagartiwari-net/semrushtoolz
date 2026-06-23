<?php

namespace App\Services;

use App\Models\User;
use App\Support\AffiliateReportFilters;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AffiliateExportService
{
    public function __construct(
        protected AffiliateService $affiliates,
    ) {}

    public function download(User $user, string $format, bool $admin = false): StreamedResponse
    {
        $filters = AffiliateReportFilters::fromRequest(request());
        $report = $admin
            ? $this->affiliates->adminReport($filters)
            : $this->affiliates->reportForUser($user, $filters);
        $filename = ($admin ? 'affiliate-admin-report' : 'affiliate-report-'.$user->id).'-'.now()->format('Y-m-d');

        if ($format === 'xlsx' || $format === 'excel') {
            return $this->excelResponse($report, $filename, $admin);
        }

        return $this->csvResponse($report, $filename, $admin);
    }

    protected function csvResponse(array $report, string $filename, bool $admin): StreamedResponse
    {
        $breakdownLabel = $report['breakdown_type'] === 'monthly' ? 'Monthly Breakdown' : 'Daily Breakdown';
        $dateColumn = $report['breakdown_type'] === 'monthly' ? 'Month' : 'Date';

        return response()->streamDownload(function () use ($report, $admin, $breakdownLabel, $dateColumn) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Affiliate Report — '.$report['period_label'].' — '.now()->format('Y-m-d H:i')]);
            fputcsv($out, []);

            fputcsv($out, ['Summary']);
            foreach ($report['summary'] as $key => $value) {
                fputcsv($out, [ucwords(str_replace('_', ' ', $key)), $value]);
            }

            fputcsv($out, []);
            fputcsv($out, [$breakdownLabel]);
            fputcsv($out, [$dateColumn, 'Clicks', 'Signups', 'Orders']);
            foreach ($report['breakdown'] as $row) {
                fputcsv($out, [$row['date'], $row['clicks'], $row['signups'], $row['orders']]);
            }

            if ($admin && ! empty($report['top_codes'])) {
                fputcsv($out, []);
                fputcsv($out, ['Top Referral Codes']);
                fputcsv($out, ['Code', 'Clicks']);
                foreach ($report['top_codes'] as $row) {
                    fputcsv($out, [$row['code'], $row['clicks']]);
                }
            }

            if (! $admin && ! empty($report['activity'])) {
                fputcsv($out, []);
                fputcsv($out, ['Recent Activity']);
                fputcsv($out, ['Type', 'Details', 'Date']);
                foreach ($report['activity'] as $row) {
                    fputcsv($out, [$row['label'], $row['detail'], $row['date']]);
                }
            }

            fclose($out);
        }, $filename.'.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function excelResponse(array $report, string $filename, bool $admin): StreamedResponse
    {
        $html = view('exports.affiliate-report-excel', compact('report', 'admin'))->render();

        return response()->streamDownload(
            fn () => print($html),
            $filename.'.xls',
            ['Content-Type' => 'application/vnd.ms-excel; charset=UTF-8']
        );
    }
}
