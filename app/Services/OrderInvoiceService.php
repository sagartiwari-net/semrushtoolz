<?php

namespace App\Services;

use App\Models\Order;
use App\Models\SiteSetting;
use Barryvdh\DomPDF\Facade\Pdf;
use Symfony\Component\HttpFoundation\Response;

class OrderInvoiceService
{
    public function __construct(
        protected OrderService $orders,
    ) {}

    public function canDownload(Order $order): bool
    {
        return in_array($order->status, ['completed', 'refunded'], true);
    }

    public function download(Order $order): Response
    {
        abort_unless($this->canDownload($order), 404, 'Invoice is only available for paid orders.');

        $order->loadMissing(['user', 'plan', 'tool', 'coupon']);
        $general = SiteSetting::generalConfig();

        $data = [
            'order' => $order,
            'siteName' => $general['site_name'],
            'supportEmail' => $general['support_email'],
            'gst' => SiteSetting::gstConfig(),
            'statusLabel' => $this->orders->statusLabel($order->status),
            'formattedTotal' => $this->orders->formatAmount($order),
            'currencySymbol' => $order->currency === 'inr' ? '₹' : '$',
        ];

        return Pdf::loadView('invoices.order', $data)
            ->setPaper('a4')
            ->download('invoice-'.$order->order_number.'.pdf');
    }
}
