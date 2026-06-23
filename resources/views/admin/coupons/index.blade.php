@extends('layouts.admin')

@section('title', 'Coupons')

@section('content')
    <div class="mb-5 flex flex-wrap items-center justify-between gap-3">
        <div>
            <h1 class="dash-page-title">Coupons</h1>
            <p class="text-sm text-ink-secondary">Create discount codes for checkout.</p>
        </div>
        <a href="{{ route('admin.coupons.create') }}" class="ui-btn-primary">+ Create Coupon</a>
    </div>

    @if (session('success'))
        <div class="mb-5 rounded-xl border border-success/30 bg-success/10 px-4 py-3 text-sm text-success">{{ session('success') }}</div>
    @endif

    <div class="dash-table-wrap">
        <table class="dash-table">
            <thead>
                <tr>
                    <th>Code</th>
                    <th>Discount</th>
                    <th>Rules</th>
                    <th>Uses</th>
                    <th>Valid</th>
                    <th>Status</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
                @forelse ($coupons as $coupon)
                    <tr>
                        <td class="font-mono font-semibold text-ink">{{ $coupon->code }}</td>
                        <td>{{ $coupon->discountLabel() }}</td>
                        <td class="text-xs text-ink-secondary">{{ $coupon->restrictionsSummary() }}</td>
                        <td>{{ $coupon->usesLabel() }}</td>
                        <td class="text-sm text-ink-secondary">
                            @if ($coupon->valid_from || $coupon->valid_until)
                                {{ $coupon->valid_from?->format('M j, Y') ?? '—' }}
                                →
                                {{ $coupon->valid_until?->format('M j, Y') ?? '—' }}
                            @else
                                Always
                            @endif
                        </td>
                        <td>
                            <span @class(['dash-badge-online' => $coupon->is_active, 'dash-badge-offline' => ! $coupon->is_active])>
                                {{ $coupon->is_active ? 'Active' : 'Disabled' }}
                            </span>
                        </td>
                        <td class="space-x-2 whitespace-nowrap">
                            <a href="{{ route('admin.coupons.edit', $coupon) }}" class="ui-btn-ghost text-xs">Edit</a>
                            <form method="POST" action="{{ route('admin.coupons.toggle', $coupon) }}" class="inline">
                                @csrf
                                <button type="submit" class="ui-btn-ghost text-xs">{{ $coupon->is_active ? 'Disable' : 'Enable' }}</button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="py-8 text-center text-sm text-ink-muted">No coupons yet. Create one for checkout.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
@endsection
