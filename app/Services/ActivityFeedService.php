<?php

namespace App\Services;

use App\Models\Order;
use App\Models\ToolSession;
use App\Models\User;
use App\Models\UserLoginLog;
use App\Models\WalletTransaction;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;

class ActivityFeedService
{
    public const DASHBOARD_LIMIT = 5;

    public function forUser(User $user, int $limit = self::DASHBOARD_LIMIT): array
    {
        return $this->buildItems($user)
            ->take($limit)
            ->map(fn ($item) => $this->formatItem($item))
            ->values()
            ->all();
    }

    public function paginatedForUser(User $user, int $perPage = 10): LengthAwarePaginator
    {
        $items = $this->buildItems($user);
        $page = max(1, (int) request()->input('page', 1));

        return new LengthAwarePaginator(
            $items->forPage($page, $perPage)->map(fn ($item) => $this->formatItem($item))->values(),
            $items->count(),
            $perPage,
            $page,
            ['path' => route('dashboard.activity'), 'query' => request()->query()],
        );
    }

    public function totalForUser(User $user): int
    {
        return $this->buildItems($user)->count();
    }

    protected function buildItems(User $user): Collection
    {
        $items = collect();

        $user->toolSessions()
            ->latest('started_at')
            ->limit(50)
            ->get()
            ->each(function (ToolSession $session) use ($items) {
                $toolName = ucfirst(str_replace('_', ' ', $session->tool));
                $items->push([
                    'at' => $session->started_at ?? $session->created_at,
                    'title' => $session->status === 'active' && ! $session->ended_at
                        ? "Using {$toolName}"
                        : "Accessed {$toolName}",
                    'meta' => ($session->started_at ?? $session->created_at)?->diffForHumans() ?? '—',
                    'color' => 'accent',
                ]);
            });

        $user->loginLogs()
            ->where('action', '!=', 'page_view')
            ->latest('logged_at')
            ->limit(50)
            ->get()
            ->each(function (UserLoginLog $log) use ($items) {
                $device = trim(($log->platform ?? '').' — '.($log->browser ?? ''));
                $items->push([
                    'at' => $log->logged_at ?? $log->created_at,
                    'title' => match ($log->action) {
                        'login' => 'Signed in',
                        'payment' => 'Payment completed',
                        default => ucfirst(str_replace('_', ' ', $log->action ?? 'Activity')),
                    },
                    'meta' => ($log->logged_at ?? $log->created_at)?->diffForHumans()
                        .($device ? ' · '.$device : ''),
                    'color' => $log->action === 'login' ? 'success' : 'blue',
                ]);
            });

        Order::query()
            ->where('user_id', $user->id)
            ->where('status', 'completed')
            ->latest('paid_at')
            ->limit(20)
            ->get()
            ->each(function (Order $order) use ($items) {
                $items->push([
                    'at' => $order->paid_at ?? $order->created_at,
                    'title' => $order->isWalletTopup()
                        ? 'Wallet top-up completed'
                        : 'Purchased '.$order->purchasedItemName(),
                    'meta' => ($order->paid_at ?? $order->created_at)?->diffForHumans() ?? '—',
                    'color' => 'blue',
                ]);
            });

        WalletTransaction::query()
            ->where('user_id', $user->id)
            ->whereIn('type', [
                WalletTransaction::TYPE_CASHBACK,
                WalletTransaction::TYPE_AFFILIATE_TRANSFER,
                WalletTransaction::TYPE_AFFILIATE_BONUS,
            ])
            ->latest()
            ->limit(20)
            ->get()
            ->each(function (WalletTransaction $tx) use ($items) {
                $items->push([
                    'at' => $tx->created_at,
                    'title' => match ($tx->type) {
                        WalletTransaction::TYPE_CASHBACK => 'Wallet cashback received',
                        WalletTransaction::TYPE_AFFILIATE_TRANSFER => 'Affiliate earnings moved to wallet',
                        WalletTransaction::TYPE_AFFILIATE_BONUS => 'Affiliate wallet transfer bonus',
                        WalletTransaction::TYPE_TOPUP => 'Wallet credited',
                        default => $tx->typeLabel(),
                    },
                    'meta' => $tx->created_at?->diffForHumans() ?? '—',
                    'color' => 'green',
                ]);
            });

        return $items->sortByDesc('at')->values();
    }

    protected function formatItem(array $item): array
    {
        return [
            'title' => $item['title'],
            'meta' => $item['meta'],
            'color' => $item['color'],
        ];
    }

    public function topToolForUser(User $user): ?array
    {
        $top = $user->toolSessions()
            ->selectRaw('tool, count(*) as total')
            ->groupBy('tool')
            ->orderByDesc('total')
            ->first();

        if (! $top) {
            return null;
        }

        return [
            'name' => ucfirst(str_replace('_', ' ', $top->tool)),
            'count' => (int) $top->total,
        ];
    }
}
