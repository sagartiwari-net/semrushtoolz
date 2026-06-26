<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UserExportService
{
    public function __construct(
        protected AdminUserQueryService $queries,
    ) {}

    public function download(Request $request): StreamedResponse
    {
        $users = $this->filteredQuery($request)->get();
        $filename = 'users-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($users) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'ID',
                'Name',
                'Email',
                'Status',
                'Active Plan',
                'Subscription Status',
                'Joined',
                'Referral Code',
            ]);

            foreach ($users as $user) {
                $row = $this->queries->presentUser($user);
                fputcsv($out, [
                    $row['id'],
                    $row['name'],
                    $row['email'],
                    $row['status'],
                    $row['plan'],
                    $row['subscription_status'],
                    $row['joined'],
                    $user->referral_code,
                ]);
            }

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    protected function filteredQuery(Request $request)
    {
        $query = User::with(['subscriptions.plan', 'subscriptions.tool'])
            ->whereIn('role', ['user', 'admin', 'super_admin'])
            ->whereNotNull('email_verified_at');

        $this->queries->applyFilters($query, $request);

        return $query->orderByDesc('created_at');
    }
}
