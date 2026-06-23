<?php

namespace App\Http\Controllers;

use App\Services\PayPalService;
use App\Services\PayPalWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class PayPalWebhookController extends Controller
{
    public function __invoke(Request $request, PayPalService $paypal, PayPalWebhookService $handler): Response
    {
        $body = $request->getContent();

        if ($paypal->isConfigured() && ! $paypal->verifyWebhook($request->headers->all(), $body)) {
            Log::warning('PayPal webhook signature verification failed');

            return response('Invalid signature', 400);
        }

        $event = json_decode($body, true);
        if (! is_array($event)) {
            return response('Invalid payload', 400);
        }

        try {
            $handler->handle($event);
        } catch (\Throwable $e) {
            Log::error('PayPal webhook error', ['message' => $e->getMessage()]);

            return response('Processing failed', 500);
        }

        return response('OK', 200);
    }
}
