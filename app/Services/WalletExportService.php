<?php

namespace App\Services;

use App\Models\WalletTransaction;
use Symfony\Component\HttpFoundation\StreamedResponse;

class WalletExportService
{
    public function download(?string $type = null, ?string $search = null): StreamedResponse
    {
        $query = WalletTransaction::query()
            ->with('user:id,name,email')
            ->when(filled($type), fn ($q) => $q->where('type', $type))
            ->when(filled($search), function ($q) use ($search) {
                $q->where(function ($inner) use ($search) {
                    $inner->whereHas('user', function ($userQuery) use ($search) {
                        $userQuery->where('email', 'like', "%{$search}%")
                            ->orWhere('name', 'like', "%{$search}%");
                    })->orWhere('description', 'like', "%{$search}%");
                });
            })
            ->latest();

        $filename = 'wallet-transactions-'.now()->format('Y-m-d-His').'.csv';

        return response()->streamDownload(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, [
                'Date',
                'User Name',
                'User Email',
                'Type',
                'Amount',
                'Balance After',
                'Currency',
                'Description',
                'Reference Type',
                'Reference ID',
            ]);

            $query->chunk(200, function ($transactions) use ($out) {
                foreach ($transactions as $tx) {
                    fputcsv($out, [
                        $tx->created_at->format('Y-m-d H:i:s'),
                        $tx->user?->name ?? '',
                        $tx->user?->email ?? '',
                        $tx->typeLabel(),
                        $tx->amount,
                        $tx->balance_after,
                        strtoupper($tx->currency),
                        $tx->description ?? '',
                        $tx->reference_type ?? '',
                        $tx->reference_id ?? '',
                    ]);
                }
            });

            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
