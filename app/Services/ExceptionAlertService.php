<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Throwable;

class ExceptionAlertService
{
    public function report(Throwable $e): void
    {
        if (! app()->environment('production') || ! $this->shouldAlert($e)) {
            return;
        }

        $email = config('monitoring.alert_email');

        if (! $email) {
            return;
        }

        $fingerprint = md5($e::class.$e->getMessage().$e->getFile().$e->getLine());
        $cacheKey = 'exception_alert:'.$fingerprint;

        if (Cache::has($cacheKey)) {
            return;
        }

        Cache::put($cacheKey, true, now()->addMinutes((int) config('monitoring.alert_cooldown_minutes', 30)));

        try {
            Mail::raw($this->formatMessage($e), function ($message) use ($email) {
                $message->to($email)
                    ->subject('['.config('app.name').'] Production error alert');
            });
        } catch (\Throwable $mailError) {
            Log::error('Failed to send exception alert email', [
                'error' => $mailError->getMessage(),
                'original' => $e->getMessage(),
            ]);
        }
    }

    protected function formatMessage(Throwable $e): string
    {
        $url = request()?->fullUrl() ?? 'CLI';

        return implode("\n", [
            'Application: '.config('app.name'),
            'URL: '.$url,
            'Exception: '.$e::class,
            'Message: '.$e->getMessage(),
            'File: '.$e->getFile().':'.$e->getLine(),
            '',
            'Check Sentry or storage/logs for full trace.',
        ]);
    }

    protected function shouldAlert(Throwable $e): bool
    {
        if ($e instanceof \Illuminate\Validation\ValidationException) {
            return false;
        }

        if ($e instanceof \Illuminate\Auth\AuthenticationException) {
            return false;
        }

        if ($e instanceof \Symfony\Component\HttpKernel\Exception\HttpExceptionInterface) {
            return $e->getStatusCode() >= 500;
        }

        return true;
    }
}
