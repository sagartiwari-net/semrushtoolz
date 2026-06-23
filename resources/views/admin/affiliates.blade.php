@extends('layouts.admin')

@section('title', 'Affiliates')

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
        <p class="text-sm text-ink-secondary">
            Commission rate: <strong>{{ $stats['commission_rate'] }}%</strong> · Min payout: <strong>₹{{ number_format($stats['min_payout']) }}</strong>
            <span class="text-ink-muted">(<a href="{{ route('admin.settings') }}" class="text-accent hover:underline">Settings → Affiliate</a>)</span>
        </p>
    </div>

    <div class="dash-stats-grid mb-6" style="grid-template-columns: repeat(4, 1fr);">
        <x-dashboard.stat-card label="Active Affiliates" :value="(string) $stats['active_affiliates']" icon="blue"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg></x-dashboard.stat-card>
        <x-dashboard.stat-card label="Pending Payouts" :value="'₹'.number_format($stats['pending_payouts'], 0)" icon="orange"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></x-dashboard.stat-card>
        <x-dashboard.stat-card label="Pending Commissions" :value="(string) $stats['pending_commissions']" icon="purple"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20"/></svg></x-dashboard.stat-card>
        <x-dashboard.stat-card label="Total Paid Out" :value="'₹'.number_format($stats['total_paid'], 0)" icon="green"><svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><path d="m9 11 3 3L22 4"/></svg></x-dashboard.stat-card>
    </div>

    <div class="dash-card mb-4">
        <h3 class="dash-card-title">Affiliate Program Broadcast</h3>
        <p class="mb-3 text-sm text-ink-secondary">Queue a promotional email to users in batches (8 every 15 minutes). Edit templates under <a href="{{ route('admin.email-presets.index') }}" class="text-accent hover:underline">Email Presets → Affiliate Program</a>.</p>
        @if ($broadcastPending > 0)
            <p class="mb-3 text-sm text-ink-muted"><strong>{{ $broadcastPending }}</strong> email(s) still in queue.</p>
        @endif
        <form method="POST" action="{{ route('admin.affiliates.broadcast') }}" class="flex flex-wrap items-end gap-3">
            @csrf
            <div>
                <label class="ui-label">Audience</label>
                <select name="audience" class="ui-input w-auto py-1.5 text-sm" required>
                    <option value="both">Both — boost for affiliates, invite for others</option>
                    <option value="affiliates">Active affiliates only (boost email)</option>
                    <option value="non_affiliates">Non-affiliates only (invite email)</option>
                    <option value="all">All users (auto-pick template)</option>
                </select>
            </div>
            <button type="submit" class="ui-btn-primary text-sm" onclick="return confirm('Queue broadcast emails? They will be sent gradually to avoid spam filters.')">Start broadcast</button>
        </form>
    </div>

    <div class="grid gap-4 xl:grid-cols-2 mb-4">
        <div class="dash-card !mb-0">
            <h3 class="dash-card-title">Payout Requests</h3>
            <div class="dash-table-wrap border-0 shadow-none">
                <table class="dash-table">
                    <thead><tr><th>User</th><th>Amount</th><th>Method</th><th>Requested</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($payouts as $payout)
                            <tr>
                                <td>{{ $payout['user'] }}</td>
                                <td>{{ $payout['amount'] }}</td>
                                <td>{{ $payout['method'] }}</td>
                                <td>{{ $payout['requested'] }}</td>
                                <td class="space-x-1">
                                    <form method="POST" action="{{ route('admin.affiliates.payout.process', $payout['id']) }}" class="inline">@csrf<button type="submit" class="ui-btn-primary text-xs">Process</button></form>
                                    <button type="button" class="ui-btn-ghost text-xs text-danger" onclick="document.getElementById('reject-payout-{{ $payout['id'] }}').classList.toggle('hidden')">Reject</button>
                                </td>
                            </tr>
                            <tr id="reject-payout-{{ $payout['id'] }}" class="hidden">
                                <td colspan="5">
                                    <form method="POST" action="{{ route('admin.affiliates.payout.reject', $payout['id']) }}" class="flex gap-2 p-2">@csrf<input class="ui-input flex-1 text-sm" name="admin_note" placeholder="Rejection reason" required><button type="submit" class="ui-btn-outline text-xs">Confirm</button></form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-ink-muted">No pending payouts.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="dash-card !mb-0">
            <h3 class="dash-card-title">Top Affiliates</h3>
            <div class="dash-table-wrap border-0 shadow-none">
                <table class="dash-table">
                    <thead><tr><th>User</th><th>Referrals</th><th>Earned</th><th>Code</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($topAffiliates as $affiliate)
                            <tr>
                                <td><div class="font-medium text-ink">{{ $affiliate['name'] }}</div><div class="text-xs text-ink-muted">{{ $affiliate['email'] }}</div></td>
                                <td>{{ $affiliate['referrals'] }}</td>
                                <td>{{ $affiliate['earned'] }}</td>
                                <td class="font-mono text-xs">{{ $affiliate['code'] }}</td>
                                <td>
                                    <form method="POST" action="{{ route('admin.affiliates.send-report', $affiliate['user_id'] ?? 0) }}" class="inline">
                                        @csrf
                                        <button type="submit" class="ui-btn-ghost text-xs" title="Send monthly report now">Send report</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-ink-muted">No affiliates yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <x-dash-tabs
        :tabs="['commissions' => 'All Commissions', 'payouts' => 'Payout History', 'reports' => 'Reports']"
        :active="$activeTab"
        :preserve="['commission_status']"
    >
        @if ($activeTab === 'commissions')
            <div class="mb-4 flex justify-end">
                <form method="GET" class="flex gap-2">
                    <input type="hidden" name="tab" value="commissions">
                    <select class="ui-input w-auto text-sm" name="commission_status" onchange="this.form.submit()">
                        <option value="all" @selected($commissionStatus === 'all')>All</option>
                        <option value="pending" @selected($commissionStatus === 'pending')>Pending</option>
                        <option value="approved" @selected($commissionStatus === 'approved')>Approved</option>
                        <option value="paid" @selected($commissionStatus === 'paid')>Paid</option>
                        <option value="rejected" @selected($commissionStatus === 'rejected')>Rejected</option>
                        <option value="held" @selected($commissionStatus === 'held')>On Hold (PayPal)</option>
                    </select>
                </form>
            </div>
            <div class="dash-table-wrap border-0 shadow-none">
                <table class="dash-table">
                    <thead><tr><th>Referrer</th><th>Referred</th><th>Order</th><th>Plan</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr></thead>
                    <tbody>
                        @forelse ($commissions as $row)
                            <tr>
                                <td>{{ $row['referrer'] }}</td>
                                <td>{{ $row['referred'] }}</td>
                                <td class="font-mono text-xs">{{ $row['order'] }}</td>
                                <td>{{ $row['plan'] }}</td>
                                <td>{{ $row['amount'] }}</td>
                                <td><span @class(['dash-badge-pending' => in_array($row['status_raw'], ['pending', 'held']), 'dash-badge-online' => in_array($row['status_raw'], ['approved', 'paid']), 'dash-badge-offline' => in_array($row['status_raw'], ['rejected', 'reversed'])])>{{ $row['status'] }}</span></td>
                                <td>{{ $row['date'] }}</td>
                                <td>
                                    @if ($row['status_raw'] === 'pending')
                                        <form method="POST" action="{{ route('admin.affiliates.commission.approve', $row['id']) }}" class="inline">@csrf<button type="submit" class="ui-btn-primary text-xs">Approve</button></form>
                                        <form method="POST" action="{{ route('admin.affiliates.commission.reject', $row['id']) }}" class="inline">@csrf<button type="submit" class="ui-btn-ghost text-xs text-danger">Reject</button></form>
                                    @endif
                                    @if ($row['status_raw'] === 'held' && $row['hold_until'])
                                        <div class="text-xs text-ink-muted">Until {{ $row['hold_until'] }}</div>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="8" class="py-8 text-center text-ink-muted">No commissions found.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <x-dash-pagination :paginator="$commissions" :per-page="$perPage" />
            </div>
        @elseif ($activeTab === 'payouts')
            <div class="dash-table-wrap border-0 shadow-none">
                <table class="dash-table">
                    <thead><tr><th>User</th><th>Amount</th><th>Method</th><th>Status</th><th>Requested</th><th>Processed</th></tr></thead>
                    <tbody>
                        @forelse ($allPayouts as $row)
                            <tr>
                                <td>{{ $row['user'] }}</td>
                                <td>{{ $row['amount'] }}</td>
                                <td>{{ $row['method'] }}</td>
                                <td><span @class(['dash-badge-online' => $row['status_raw'] === 'processed', 'dash-badge-pending' => $row['status_raw'] === 'pending', 'dash-badge-offline' => $row['status_raw'] === 'rejected'])>{{ $row['status'] }}</span></td>
                                <td>{{ $row['requested'] }}</td>
                                <td>{{ $row['processed'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="py-6 text-center text-ink-muted">No payout history yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <x-dash-pagination :paginator="$allPayouts" :per-page="$perPage" />
            </div>
        @else
            @php $s = $report['summary']; @endphp

            <x-affiliate-report-filters
                :filters="$reportFilters"
                :export-csv-route="route('admin.affiliates.export', 'csv')"
                :export-excel-route="route('admin.affiliates.export', 'excel')"
            />

            <div class="dash-report-grid">
                <div class="dash-report-stat"><div class="dash-report-stat-label">Total Clicks</div><div class="dash-report-stat-value">{{ $s['clicks'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Signups</div><div class="dash-report-stat-value">{{ $s['signups'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Conversions</div><div class="dash-report-stat-value">{{ $s['conversions'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Referred Revenue</div><div class="dash-report-stat-value">{{ $s['revenue'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Commissions Paid</div><div class="dash-report-stat-value">{{ $s['commissions_paid'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Refunds</div><div class="dash-report-stat-value">{{ $s['refunds'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Refund Amount</div><div class="dash-report-stat-value">{{ $s['refund_amount'] }}</div></div>
            </div>

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <x-affiliate-report-breakdown
                        :report="$report"
                        :breakdown="$reportBreakdown"
                        :per-page="$perPage"
                        :drill-down-base-url="route('admin.affiliates')"
                    />
                </div>
                <div>
                    <h4 class="mb-3 text-sm font-bold text-ink">Top referral codes (clicks)</h4>
                    <div class="dash-table-wrap border-0 shadow-none">
                        <table class="dash-table">
                            <thead><tr><th>Code</th><th>Clicks</th></tr></thead>
                            <tbody>
                                @forelse ($report['top_codes'] as $row)
                                    <tr><td class="font-mono">{{ $row['code'] }}</td><td>{{ $row['clicks'] }}</td></tr>
                                @empty
                                    <tr><td colspan="2" class="py-6 text-center text-ink-muted">No click data yet.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        @endif
    </x-dash-tabs>
@endsection
