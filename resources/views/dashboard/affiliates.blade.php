@extends('layouts.dashboard')

@section('title', 'Affiliates')

@section('content')
    <h1 class="dash-page-title">Affiliate Program</h1>

    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    <div class="dash-stats-grid">
        <x-dashboard.stat-card label="Total Referrals" :value="$stats['referrals']" :sub="$stats['converted'].' converted'" icon="blue">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Total Earned" :value="'₹'.number_format($stats['total_earned'], 0)" :sub="$stats['commission_rate'].'% commission'" icon="green">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" x2="12" y1="2" y2="22"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Available Payout" :value="'₹'.number_format($stats['pending_payout'], 0)" :sub="'Min ₹'.$stats['min_payout'].' to withdraw'" icon="orange">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect width="20" height="14" x="2" y="5" rx="2"/><line x1="2" x2="22" y1="10" y2="10"/></svg>
        </x-dashboard.stat-card>
        <x-dashboard.stat-card label="Paid Out" :value="'₹'.number_format($stats['paid_out'], 0)" sub="Total processed" icon="purple">
            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v20M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg>
        </x-dashboard.stat-card>
    </div>

    <div class="dash-card">
        <h3 class="dash-card-title">Your Referral Link</h3>
        <div class="flex flex-wrap items-center gap-2">
            <input id="referral-link" class="ui-input flex-1 font-mono text-sm" readonly value="{{ url('/?ref='.$user['referral_code']) }}">
            <button type="button" class="ui-btn-primary" onclick="navigator.clipboard.writeText(document.getElementById('referral-link').value); this.textContent='Copied!'">Copy Link</button>
        </div>
        <p class="mt-2 text-xs text-ink-muted">
            Code: <strong class="font-mono">{{ $user['referral_code'] }}</strong>
            · Earn <strong>{{ $stats['commission_rate'] }}%</strong> on each referred purchase
            · Auto-applies on signup
            @if ($stats['held_amount'] > 0)
                · <strong>₹{{ number_format($stats['held_amount'], 0) }}</strong> on hold (PayPal {{ $stats['paypal_hold_months'] }}-month policy)
            @endif
            @if (($stats['wallet_transferred'] ?? 0) > 0)
                · <strong>₹{{ number_format($stats['wallet_transferred'], 0) }}</strong> moved to wallet
            @endif
        </p>
    </div>

    <x-dash-tabs :tabs="['commissions' => 'Commissions', 'payouts' => 'Payout History', 'reports' => 'Reports']" :active="$activeTab">
        @if ($activeTab === 'commissions')
            <div class="mb-4 flex flex-wrap items-center justify-between gap-3">
                @if ($stats['pending_payout'] >= $stats['min_payout'] && ! $stats['has_pending_request'])
                    <button type="button" class="ui-btn-primary" onclick="document.getElementById('payout-form').classList.toggle('hidden')">Request Payout</button>
                @elseif ($stats['has_pending_request'])
                    <span class="text-sm text-warning">Payout request pending review</span>
                @else
                    <span class="text-sm text-ink-muted">Minimum ₹{{ number_format($stats['min_payout']) }} required to request payout</span>
                @endif

                @if ($walletConfig['enabled'] && $stats['pending_payout'] >= $walletConfig['min_affiliate_transfer'])
                    <button type="button" class="ui-btn-outline" onclick="document.getElementById('wallet-transfer-form').classList.toggle('hidden')">Transfer to Wallet</button>
                @endif
            </div>

            @if ($walletConfig['enabled'] && $stats['pending_payout'] >= $walletConfig['min_affiliate_transfer'])
                <form id="wallet-transfer-form" method="POST" action="{{ route('dashboard.affiliates.transfer-wallet') }}" class="hidden mb-4 space-y-3 rounded-xl border border-accent/30 bg-accent/5 p-4">
                    @csrf
                    <p class="text-sm text-ink-secondary">
                        Transfer affiliate earnings to your <strong>SemrushToolz Wallet</strong> and get a
                        <strong>{{ (int) ($walletConfig['affiliate_transfer_bonus_percent'] * 100) }}% bonus</strong>.
                        Transfers are <strong>irreversible</strong> and wallet balance can only be used for subscriptions.
                    </p>
                    <div class="flex flex-wrap items-end gap-3">
                        <div class="min-w-[10rem] flex-1">
                            <label class="ui-label">Amount (₹)</label>
                            <input class="ui-input" type="number" name="amount" min="{{ $walletConfig['min_affiliate_transfer'] }}" max="{{ $stats['pending_payout'] }}" step="1" value="{{ min($stats['pending_payout'], max($walletConfig['min_affiliate_transfer'], 100)) }}" required>
                        </div>
                        <button type="submit" class="ui-btn-primary">Transfer to Wallet</button>
                    </div>
                    <p class="text-xs text-ink-muted">Available: ₹{{ number_format($stats['pending_payout'], 0) }} · Min ₹{{ number_format($walletConfig['min_affiliate_transfer']) }}</p>
                </form>
            @endif

            <form id="payout-form" method="POST" action="{{ route('dashboard.affiliates.payout') }}" class="@unless($stats['pending_payout'] >= $stats['min_payout'] && ! $stats['has_pending_request']) hidden @endunless mb-4 grid gap-3 rounded-xl border border-line p-4 md:grid-cols-3">
                @csrf
                <select class="ui-input" name="method" required>
                    <option value="upi">UPI</option>
                    <option value="bank">Bank Transfer</option>
                </select>
                <input class="ui-input md:col-span-2" name="payout_detail" placeholder="UPI ID or bank details" required>
                <button type="submit" class="ui-btn-primary md:col-span-3">Submit ₹{{ number_format($stats['pending_payout'], 0) }} payout request</button>
            </form>

            <div class="dash-table-wrap border-0 shadow-none">
                <table class="dash-table">
                    <thead><tr><th>User</th><th>Date</th><th>Plan</th><th>Commission</th><th>Status</th></tr></thead>
                    <tbody>
                    @forelse ($commissions as $row)
                        <tr>
                            <td>{{ $row['user'] }}</td>
                            <td>{{ $row['date'] }}</td>
                            <td>{{ $row['plan'] }}</td>
                            <td>{{ $row['amount'] }}</td>
                            <td>
                                <span @class([
                                    'dash-badge-online' => in_array($row['status_raw'], ['approved', 'paid']),
                                    'dash-badge-pending' => in_array($row['status_raw'], ['pending', 'held']),
                                    'dash-badge-offline' => in_array($row['status_raw'], ['rejected', 'reversed', 'wallet_transferred']),
                                ])>{{ $row['status'] }}</span>
                                @if ($row['hold_note'])
                                    <div class="mt-1 text-xs text-ink-muted">{{ $row['hold_note'] }}</div>
                                @endif
                            </td>
                        </tr>
                    @empty
                        <tr><td colspan="5" class="py-8 text-center text-ink-muted">No commissions yet. Share your referral link to get started.</td></tr>
                    @endforelse
                </tbody>
            </table>
            <x-dash-pagination :paginator="$commissions" :per-page="$perPage" />
            </div>
        @elseif ($activeTab === 'payouts')
            <div class="dash-table-wrap border-0 shadow-none">
                <table class="dash-table">
                    <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th><th>Processed</th></tr></thead>
                    <tbody>
                        @forelse ($payoutHistory as $row)
                            <tr>
                                <td>{{ $row['date'] }}</td>
                                <td>{{ $row['amount'] }}</td>
                                <td>{{ $row['method'] }}</td>
                                <td>
                                    <span @class([
                                        'dash-badge-online' => $row['status_raw'] === 'processed',
                                        'dash-badge-pending' => $row['status_raw'] === 'pending',
                                        'dash-badge-offline' => $row['status_raw'] === 'rejected',
                                    ])>{{ $row['status'] }}</span>
                                </td>
                                <td>{{ $row['processed'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="py-6 text-center text-ink-muted">No payout requests yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <x-dash-pagination :paginator="$payoutHistory" :per-page="$perPage" />
            </div>
        @else
            @php $s = $report['summary']; @endphp

            <x-affiliate-report-filters
                :filters="$reportFilters"
                :export-csv-route="route('dashboard.affiliates.export', 'csv')"
                :export-excel-route="route('dashboard.affiliates.export', 'excel')"
            />

            <div class="dash-report-grid">
                <div class="dash-report-stat"><div class="dash-report-stat-label">Link Clicks</div><div class="dash-report-stat-value">{{ $s['clicks'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Signups</div><div class="dash-report-stat-value">{{ $s['signups'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Conversions</div><div class="dash-report-stat-value">{{ $s['conversions'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Referred Revenue</div><div class="dash-report-stat-value">{{ $s['revenue'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Click → Signup</div><div class="dash-report-stat-value">{{ $s['click_to_signup'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Signup → Sale</div><div class="dash-report-stat-value">{{ $s['signup_to_sale'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Refunds</div><div class="dash-report-stat-value">{{ $s['refunds'] }}</div></div>
                <div class="dash-report-stat"><div class="dash-report-stat-label">Refund Amount</div><div class="dash-report-stat-value">{{ $s['refund_amount'] }}</div></div>
            </div>

            <x-affiliate-report-breakdown
                :report="$report"
                :breakdown="$reportBreakdown"
                :per-page="$perPage"
                :drill-down-base-url="route('dashboard.affiliates')"
            />

            <h4 class="mb-3 text-sm font-bold text-ink">Recent activity</h4>
            <div class="dash-table-wrap border-0 shadow-none">
                <table class="dash-table">
                    <thead><tr><th>Type</th><th>Details</th><th>Date</th></tr></thead>
                    <tbody>
                        @forelse ($activity as $event)
                            <tr>
                                <td>
                                    <span @class([
                                        'dash-badge-online' => $event['type'] === 'commission',
                                        'dash-badge-pending' => in_array($event['type'], ['click', 'signup']),
                                        'dash-badge-offline' => $event['type'] === 'refund',
                                    ])>{{ $event['label'] }}</span>
                                </td>
                                <td>{{ $event['detail'] }}</td>
                                <td class="text-ink-muted">{{ $event['date'] }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="py-6 text-center text-ink-muted">No activity recorded yet. Share your link — clicks are counted once per IP per day.</td></tr>
                        @endforelse
                    </tbody>
                </table>
                <x-dash-pagination :paginator="$activity" :per-page="$perPage" />
            </div>
        @endif
    </x-dash-tabs>
@endsection
