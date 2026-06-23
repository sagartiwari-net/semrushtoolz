<?php

namespace App\Http\Controllers;

use App\Services\BuyahrefPaymentService;
use App\Services\BuyahrefWebhookService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Log;

class BuyahrefWebhookController extends Controller
{
    public function __invoke(
        Request $request,
        BuyahrefPaymentService $buyahref,
        BuyahrefWebhookService $handler
    ): Response {
        $body = $request->getContent();

        if ($buyahref->isConfigured() && ! $buyahref->verifyWebhookSignature($request, $body)) {
            Log::warning('Buyahref webhook signature verification failed');

            return response('Invalid signature', 400);
        }

        $payload = json_decode($body, true);
        if (! is_array($payload)) {
            return response('Invalid payload', 400);
        }

        try {
            $handler->handle($payload);
        } catch (\Throwable $e) {
            Log::error('Buyahref webhook error', ['message' => $e->getMessage()]);

            return response('Processing failed', 500);
        }

        return response('OK', 200);
    }
}
