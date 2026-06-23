<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliateCommission;
use App\Models\AffiliatePayout;
use App\Models\User;
use App\Services\AffiliateExportService;
use App\Services\AffiliateService;
use App\Services\EmailBroadcastService;
use App\Services\TransactionalEmailService;
use App\Support\AffiliateReportFilters;
use App\Support\TablePageSize;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AffiliateController extends Controller
{
    public function __construct(
        protected AffiliateService $affiliates,
        protected AffiliateExportService $exports,
        protected TransactionalEmailService $mail,
        protected EmailBroadcastService $broadcasts,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->query('commission_status', 'all');
        $perPage = TablePageSize::resolve($request);
        $reportFilters = AffiliateReportFilters::fromRequest($request);
        $report = $this->affiliates->adminReport($reportFilters);

        return view('admin.affiliates', [
            'stats' => $this->affiliates->adminStats(),
            'payouts' => $this->affiliates->pendingPayouts(),
            'allPayouts' => $this->affiliates->paginatedAdminPayouts($perPage),
            'commissions' => $this->affiliates->paginatedAdminCommissions($status, $perPage),
            'topAffiliates' => $this->affiliates->topAffiliates(),
            'report' => $report,
            'reportFilters' => $reportFilters,
            'reportBreakdown' => $this->affiliates->paginatedReportBreakdown($report['breakdown'], $perPage),
            'commissionStatus' => $status,
            'activeTab' => $request->query('tab', 'commissions'),
            'perPage' => $perPage,
            'broadcastPending' => $this->broadcasts->pendingCount(),
        ]);
    }

    public function export(string $format): StreamedResponse
    {
        abort_unless(in_array($format, ['csv', 'xlsx', 'excel'], true), 404);

        return $this->exports->download(request()->user(), $format, admin: true);
    }

    public function sendMonthlyReport(User $user)
    {
        if (! $user->referral_code) {
            return back()->with('error', 'This user is not an affiliate.');
        }

        $this->mail->sendAffiliateMonthlyReport($user, force: true);

        return back()->with('success', "Monthly report sent to {$user->email}.");
    }

    public function startBroadcast(Request $request)
    {
        $data = $request->validate([
            'audience' => ['required', 'in:affiliates,non_affiliates,both,all'],
        ]);

        $campaignId = $this->broadcasts->startCampaign($data['audience']);
        $queued = $this->broadcasts->pendingCount($campaignId);

        if ($queued === 0) {
            return back()->with('error', 'No recipients found for this audience.');
        }

        return back()->with('success', "Broadcast queued for {$queued} user(s). Emails will go out in batches of 8 every 15 minutes.");
    }

    public function process(Request $request, AffiliatePayout $payout)
    {
        if ($payout->status !== AffiliatePayout::STATUS_PENDING) {
            return back()->with('error', 'This payout is no longer pending.');
        }

        $data = $request->validate([
            'admin_note' => ['nullable', 'string', 'max:500'],
        ]);

        $this->affiliates->processPayout($payout, $request->user(), $data['admin_note'] ?? null);

        return back()->with('success', 'Payout marked as processed.');
    }

    public function reject(Request $request, AffiliatePayout $payout)
    {
        if ($payout->status !== AffiliatePayout::STATUS_PENDING) {
            return back()->with('error', 'This payout is no longer pending.');
        }

        $data = $request->validate([
            'admin_note' => ['required', 'string', 'max:500'],
        ]);

        $this->affiliates->rejectPayout($payout, $request->user(), $data['admin_note']);

        return back()->with('success', 'Payout request rejected.');
    }

    public function approveCommission(AffiliateCommission $commission)
    {
        try {
            $this->affiliates->approveCommission($commission);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Commission approved.');
    }

    public function rejectCommission(AffiliateCommission $commission)
    {
        try {
            $this->affiliates->rejectCommission($commission);
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Commission rejected.');
    }
}
