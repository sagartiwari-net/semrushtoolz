@extends('layouts.admin')

@section('title', $user->name)

@section('content')
    @if (session('success'))
        <div class="mb-4 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif
    @if (session('error'))
        <div class="mb-4 rounded-xl border border-danger/30 bg-danger/10 px-4 py-3 text-sm text-danger">{{ session('error') }}</div>
    @endif

    <div class="mb-5 flex flex-wrap items-start justify-between gap-3">
        <div>
            <a href="{{ route('admin.users') }}" class="text-sm text-accent hover:underline">← All Users</a>
            <h1 class="dash-page-title mt-1">{{ $user->name }}</h1>
            <p class="text-sm text-ink-muted">{{ $user->email }} · Joined {{ $user->created_at->format('M d, Y') }} · ID #{{ $user->id }}</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <span @class([
                'dash-badge-online' => $user->status === 'active',
                'dash-badge-offline' => $user->status === 'blocked',
            ])>{{ ucfirst($user->status) }}</span>
            @if ($user->email_verified_at)
                <span class="dash-badge-online">Email verified</span>
            @else
                <span class="dash-badge-pending">Email not verified</span>
            @endif
            <a href="{{ route('admin.security.user', $user) }}" class="ui-btn-outline text-xs">Security detail</a>
        </div>
    </div>

    <div class="dash-stats-grid mb-6" style="grid-template-columns: repeat(4, minmax(0, 1fr));">
        <x-dashboard.stat-card label="Wallet" :value="'₹'.number_format($user->wallet_balance, 2)" icon="blue" />
        <x-dashboard.stat-card label="Active plans" :value="$activeSubscriptions->count()" icon="orange" />
        <x-dashboard.stat-card label="IPs (7 days)" :value="$uniqueIpsWeek" icon="green" />
        <x-dashboard.stat-card label="Devices (recent)" :value="$uniqueDevicesWeek" icon="blue" />
    </div>

    @php
        $tabs = [
            'account' => 'Account',
            'subscriptions' => 'Subscriptions ('.$activeSubscriptions->count().')',
            'activity' => 'Activity ('.$accessLogsCount.')',
            'orders' => 'Orders ('.$ordersCount.')',
            'affiliate' => 'Affiliate ('.$referralsCount.')',
        ];
    @endphp

    <x-dash-tabs :tabs="$tabs" :active="$tab" :preserve="['per_page', 'affiliate_tab']">
        @if ($tab === 'account')
            <div class="grid gap-6 lg:grid-cols-2 xl:grid-cols-3">
                <form method="POST" action="{{ route('admin.users.update', $user) }}" class="dash-card space-y-4 xl:col-span-2">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="tab" value="account">
                    <h3 class="dash-card-title">Account details</h3>

                    <div class="grid gap-4 sm:grid-cols-2">
                        <div>
                            <label class="ui-label">Name</label>
                            <input class="ui-input" name="name" value="{{ old('name', $user->name) }}" required>
                        </div>
                        <div>
                            <label class="ui-label">Email</label>
                            <input class="ui-input" name="email" type="email" value="{{ old('email', $user->email) }}" required>
                        </div>
                        <div>
                            <label class="ui-label">Phone</label>
                            <input class="ui-input" name="phone" value="{{ old('phone', $user->phone) }}">
                        </div>
                        <div>
                            <label class="ui-label">Referral code</label>
                            <input class="ui-input font-mono" name="referral_code" value="{{ old('referral_code', $user->referral_code) }}">
                        </div>
                        <div>
                            <label class="ui-label">Status</label>
                            <select class="ui-input" name="status">
                                <option value="active" @selected(old('status', $user->status) === 'active')>Active</option>
                                <option value="blocked" @selected(old('status', $user->status) === 'blocked')>Blocked</option>
                            </select>
                        </div>
                    </div>
                    <label class="flex items-center gap-2 text-sm">
                        <input type="checkbox" name="email_verified" value="1" class="rounded" @checked(old('email_verified', (bool) $user->email_verified_at))>
                        Mark email as verified
                    </label>
                    <button type="submit" class="ui-btn-primary">Save profile</button>
                </form>

                <div class="space-y-6">
                    <form method="POST" action="{{ route('admin.users.password', $user) }}" class="dash-card space-y-4">
                        @csrf
                        <input type="hidden" name="tab" value="account">
                        <h3 class="dash-card-title">Change password</h3>
                        <div>
                            <label class="ui-label">New password</label>
                            <input class="ui-input" type="password" name="password" required autocomplete="new-password">
                        </div>
                        <div>
                            <label class="ui-label">Confirm password</label>
                            <input class="ui-input" type="password" name="password_confirmation" required>
                        </div>
                        <button type="submit" class="ui-btn-outline w-full">Update password</button>
                    </form>

                    <div class="dash-card space-y-3">
                        <h3 class="dash-card-title">Quick actions</h3>
                        @if ($activeSessions->isNotEmpty())
                            <form method="POST" action="{{ route('admin.users.kill-sessions', $user) }}" onsubmit="return confirm('End all active sessions?')">
                                @csrf
                                <input type="hidden" name="tab" value="account">
                                <button type="submit" class="ui-btn-outline w-full border-warning text-warning">Kill all sessions ({{ $activeSessions->count() }})</button>
                            </form>
                        @endif
                        @if ($user->isBlocked())
                            <form method="POST" action="{{ route('admin.security.unblock', $user) }}">
                                @csrf
                                <input type="hidden" name="tab" value="account">
                                <button type="submit" class="ui-btn-primary w-full">Unblock user</button>
                            </form>
                        @else
                            <form method="POST" action="{{ route('admin.security.block', $user) }}" onsubmit="return confirm('Block this user?')">
                                @csrf
                                <input type="hidden" name="reason" value="Blocked from user profile">
                                <input type="hidden" name="tab" value="account">
                                <button type="submit" class="ui-btn-outline w-full border-danger text-danger">Block user now</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>

        @elseif ($tab === 'subscriptions')
            <div class="space-y-6">
                <div class="dash-card">
                    <h3 class="dash-card-title">Active subscriptions</h3>

                    @if ($activeSubscriptions->isNotEmpty())
                        <div class="space-y-2">
                            @foreach ($activeSubscriptions as $sub)
                                <div class="rounded-xl border border-success/30 bg-success/5 p-4">
                                    <div class="flex flex-wrap items-start justify-between gap-2">
                                        <div>
                                            <p class="font-bold text-ink">{{ $sub->plan?->name ?? $sub->tool?->name ?? 'Subscription' }}</p>
                                            <p class="text-sm text-ink-muted">
                                                Active until {{ $sub->ends_at->format('M d, Y') }}
                                                ({{ $sub->daysRemaining() }} days left)
                                            </p>
                                        </div>
                                        <span class="dash-badge-online">Active</span>
                                    </div>
                                    <div class="mt-3 flex flex-wrap gap-2">
                                        <form method="POST" action="{{ route('admin.users.subscriptions.extend', [$user, $sub]) }}" class="flex flex-wrap items-end gap-2">
                                            @csrf
                                            <input type="hidden" name="tab" value="subscriptions">
                                            <input class="ui-input w-20 text-sm" type="number" name="extend_months" min="1" max="12" value="1" placeholder="Mo">
                                            <button type="submit" class="ui-btn-outline text-xs">Extend months</button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.users.subscriptions.cancel', [$user, $sub]) }}" onsubmit="return confirm('Cancel this plan without refund? User will lose access immediately.')">
                                            @csrf
                                            <input type="hidden" name="tab" value="subscriptions">
                                            <button type="submit" class="ui-btn-ghost text-xs text-danger">Cancel (no refund)</button>
                                        </form>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    @else
                        <p class="text-sm text-ink-muted">No active subscription.</p>
                    @endif
                </div>

                <form method="POST" action="{{ route('admin.users.subscriptions.grant', $user) }}" class="dash-card rounded-xl border border-dashed border-line">
                    @csrf
                    <input type="hidden" name="tab" value="subscriptions">
                    <p class="mb-3 text-sm font-semibold text-ink">Manually activate access</p>
                    <p class="mb-3 text-xs text-ink-muted">Combo/trial = Plans. Individual Semrush, Site Audit, Ahrefs, etc. = Shop tools.</p>
                    <div class="grid gap-3 sm:grid-cols-3">
                        <div class="sm:col-span-2">
                            <label class="ui-label">Plan or tool</label>
                            <select class="ui-input" name="grant" required>
                                @if ($plans->isNotEmpty())
                                    <optgroup label="Plans (bundles)">
                                        @foreach ($plans as $plan)
                                            <option value="plan:{{ $plan->id }}">{{ $plan->name }} (₹{{ number_format($plan->price_inr) }})</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                                @if (($shopTools ?? collect())->isNotEmpty())
                                    <optgroup label="Individual tools">
                                        @foreach ($shopTools as $tool)
                                            <option value="tool:{{ $tool->id }}">{{ $tool->name }} (₹{{ number_format($tool->price_inr) }})</option>
                                        @endforeach
                                    </optgroup>
                                @endif
                            </select>
                        </div>
                        <div>
                            <label class="ui-label">Months</label>
                            <input class="ui-input" type="number" name="duration_months" min="1" max="24" value="1">
                        </div>
                        <div>
                            <label class="ui-label">Days (trial)</label>
                            <input class="ui-input" type="number" name="duration_days" min="1" max="90" placeholder="Optional">
                        </div>
                    </div>
                    <button type="submit" class="ui-btn-primary mt-3">Activate access</button>
                </form>

                <div class="dash-card">
                    <h3 class="dash-card-title">Subscription history</h3>
                    <div class="dash-table-wrap">
                        <table class="dash-table text-sm">
                            <thead><tr><th>Plan</th><th>Status</th><th>Ends</th><th>Paid</th></tr></thead>
                            <tbody>
                                @forelse ($subscriptionHistory as $sub)
                                    <tr>
                                        <td>{{ $sub->plan?->name ?? $sub->tool?->name ?? '—' }}</td>
                                        <td>{{ ucfirst($sub->status) }}</td>
                                        <td>{{ $sub->ends_at?->format('M d, Y') ?? '—' }}</td>
                                        <td>₹{{ number_format($sub->amount_paid, 0) }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-6 text-center text-ink-muted">No subscription history.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-dash-pagination :paginator="$subscriptionHistory" :per-page="$perPage" />
                </div>
            </div>

        @elseif ($tab === 'activity')
            <div class="grid gap-6 lg:grid-cols-2">
                <div class="dash-card">
                    <h3 class="dash-card-title">Access logs</h3>
                    <p class="mb-3 text-xs text-ink-muted">{{ $uniqueIpsWeek }} unique IPs · {{ $uniqueDevicesWeek }} devices (last 7 days)</p>
                    <div class="dash-table-wrap">
                        <table class="dash-table text-xs">
                            <thead><tr><th>When</th><th>IP</th><th>Action</th><th>Device</th></tr></thead>
                            <tbody>
                                @forelse ($accessLogs as $log)
                                    <tr>
                                        <td>{{ $log->logged_at->format('M d, H:i') }}</td>
                                        <td class="font-mono">{{ $log->ip_address }}</td>
                                        <td>{{ $log->action }}</td>
                                        <td>{{ $log->browser }} / {{ $log->platform }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="4" class="py-6 text-center text-ink-muted">No logs yet</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <x-dash-pagination :paginator="$accessLogs" :per-page="$perPage" />
                </div>

                <div class="dash-card">
                    <h3 class="dash-card-title">Active sessions</h3>
                    <div class="dash-table-wrap">
                        <table class="dash-table text-xs">
                            <thead><tr><th>Last active</th><th>IP</th><th>Fingerprint</th></tr></thead>
                            <tbody>
                                @forelse ($activeSessions as $session)
                                    <tr>
                                        <td>{{ $session['last_activity'] }}</td>
                                        <td class="font-mono">{{ $session['ip'] }}</td>
                                        <td class="font-mono">{{ $session['fingerprint'] }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="3" class="py-6 text-center text-ink-muted">No active sessions</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

        @elseif ($tab === 'orders')
            <div class="dash-card">
                <h3 class="dash-card-title">Orders</h3>
                <div class="dash-table-wrap">
                    <table class="dash-table text-sm">
                        <thead><tr><th>Order</th><th>Item</th><th>Amount</th><th>Status</th><th>Date</th><th></th></tr></thead>
                        <tbody>
                            @forelse ($orders as $order)
                                @php
                                    $activeSub = $order->subscription && $order->subscription->isActive();
                                @endphp
                                <tr>
                                    <td><a href="{{ route('admin.orders.show', $order) }}" class="text-accent hover:underline">{{ $order->order_number }}</a></td>
                                    <td>{{ $order->plan?->name ?? $order->tool?->name ?? '—' }}</td>
                                    <td>{{ $order->currency === 'inr' ? '₹' : '$' }}{{ number_format($order->total, 0) }}</td>
                                    <td>{{ ucfirst($order->status) }}</td>
                                    <td>{{ $order->created_at->format('M d, Y') }}</td>
                                    <td class="text-xs whitespace-nowrap">
                                        @if ($order->status === 'completed' && $order->isSubscription())
                                            @if ($activeSub)
                                                <form method="POST" action="{{ route('admin.orders.revoke-access', $order) }}" class="inline" onsubmit="return confirm('Cancel plan without refund?')">
                                                    @csrf
                                                    <button type="submit" class="text-warning hover:underline">Revoke</button>
                                                </form>
                                                <span class="text-ink-muted">·</span>
                                            @endif
                                            <a href="{{ route('admin.orders.show', $order) }}#refund" class="text-danger hover:underline">Refund</a>
                                        @else
                                            <a href="{{ route('admin.orders.show', $order) }}" class="text-accent hover:underline">View</a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr><td colspan="6" class="py-6 text-center text-ink-muted">No orders yet.</td></tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <x-dash-pagination :paginator="$orders" :per-page="$perPage" />
            </div>

        @elseif ($tab === 'affiliate')
            <div class="space-y-6">
                <div class="dash-stats-grid-3up">
                    <x-dashboard.stat-card label="Referrals" :value="$affiliate['referrals']" icon="blue" />
                    <x-dashboard.stat-card label="Total earned" :value="$affiliate['total_earned_label']" icon="orange" />
                    <x-dashboard.stat-card label="Payable balance" :value="$affiliate['pending_payout_label']" icon="green" />
                </div>

                <dl class="grid gap-2 text-sm sm:grid-cols-2">
                    <div><dt class="text-ink-muted">Referral link</dt><dd class="break-all font-mono text-xs">{{ url('/?ref='.$user->referral_code) }}</dd></div>
                    <div><dt class="text-ink-muted">Commission rate</dt><dd>{{ $affiliate['commission_rate'] }}%</dd></div>
                    @if ($referredBy)
                        <div><dt class="text-ink-muted">Referred by</dt><dd><a href="{{ route('admin.users.show', $referredBy) }}" class="text-accent hover:underline">{{ $referredBy->name }}</a> ({{ $referredBy->email }})</dd></div>
                    @endif
                </dl>

                @php
                    $affiliateTabs = [
                        'referrals' => 'Users referred',
                        'commissions' => 'Commissions',
                        'payouts' => 'Payout requests',
                    ];
                @endphp

                <x-dash-tabs :tabs="$affiliateTabs" :active="$affiliateTab" param="affiliate_tab" :preserve="['tab', 'per_page']">
                    @if ($affiliateTab === 'referrals')
                        <div class="dash-table-wrap border-0 shadow-none">
                            <table class="dash-table text-sm">
                                <thead><tr><th>Name</th><th>Email</th><th>Joined</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($referrals as $ref)
                                        <tr>
                                            <td><a href="{{ route('admin.users.show', $ref) }}" class="font-medium text-accent hover:underline">{{ $ref->name }}</a></td>
                                            <td>{{ $ref->email }}</td>
                                            <td>{{ $ref->created_at->format('M d, Y') }}</td>
                                            <td>{{ ucfirst($ref->status) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="py-6 text-center text-ink-muted">No referrals yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <x-dash-pagination :paginator="$referrals" :per-page="$perPage" />
                    @elseif ($affiliateTab === 'commissions')
                        <div class="dash-table-wrap border-0 shadow-none">
                            <table class="dash-table text-sm">
                                <thead><tr><th>Date</th><th>From</th><th>Amount</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($commissions as $c)
                                        <tr>
                                            <td>{{ $c->created_at->format('M d, Y') }}</td>
                                            <td>{{ $c->referred?->email ?? '—' }}</td>
                                            <td>₹{{ number_format($c->amount, 2) }}</td>
                                            <td>{{ ucfirst($c->status) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="py-6 text-center text-ink-muted">No commissions yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <x-dash-pagination :paginator="$commissions" :per-page="$perPage" />
                    @else
                        <div class="dash-table-wrap border-0 shadow-none">
                            <table class="dash-table text-sm">
                                <thead><tr><th>Date</th><th>Amount</th><th>Method</th><th>Status</th></tr></thead>
                                <tbody>
                                    @forelse ($payouts as $p)
                                        <tr>
                                            <td>{{ $p->created_at->format('M d, Y') }}</td>
                                            <td>₹{{ number_format($p->amount, 2) }}</td>
                                            <td>{{ $p->method }}</td>
                                            <td>{{ ucfirst($p->status) }}</td>
                                        </tr>
                                    @empty
                                        <tr><td colspan="4" class="py-6 text-center text-ink-muted">No payout requests yet.</td></tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                        <x-dash-pagination :paginator="$payouts" :per-page="$perPage" />
                    @endif
                </x-dash-tabs>
            </div>
        @endif
    </x-dash-tabs>
@endsection
