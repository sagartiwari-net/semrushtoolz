<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\SubscriptionService;
use App\Services\ToolEndpointService;
use Illuminate\Cookie\CookieValuePrefix;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Portable membership check for Bar2 / reseller portals.
 * Same response shape as bar2 reseller/tm-check.php.
 *
 * Auth: X-TM-Check-Key (or ?key=) must match TM_CHECK_SECRET.
 * Session: SemrushToolz login cookie (Bar2 may forward the same id as PHPSESSID).
 *
 * products: plain ints only — e.g. [1, 2, 3, 4] (never "#1").
 */
class TmCheckController extends Controller
{
    public function __invoke(Request $request, SubscriptionService $subscriptions, ToolEndpointService $endpoints): JsonResponse
    {
        if ($request->isMethod('OPTIONS')) {
            return response()->json(null, 204)->withHeaders($this->corsHeaders());
        }

        $secret = (string) config('services.tm_check.secret', '');
        $key = (string) ($request->header('X-TM-Check-Key') ?: $request->query('key', ''));

        if ($secret === '' || ! hash_equals($secret, $key)) {
            return $this->fail('forbidden', 403);
        }

        $user = $this->resolveUser($request);

        if (! $user) {
            return $this->fail('not_logged_in');
        }

        $userPayload = $this->userPayload($user);

        if ($user->status === 'blocked') {
            return $this->fail('blocked', 200, $userPayload);
        }

        $activeSubs = $subscriptions->activeSubscriptions($user);
        if ($activeSubs->isEmpty()) {
            // Still return user so Bar2 can identify the member without a plan.
            return $this->fail('no_subscription', 200, $userPayload);
        }

        // Prefer Ahrefs bar / Ahrefs grant when available (custom plans).
        $access = app(\App\Services\ToolAccessService::class);
        $hasBar = $access->canAccess($user, 'ahrefs_bar')
            || $access->userGrantsTool($user, 'ahrefs_bar')
            || $access->userGrantsTool($user, 'ahrefs');

        $products = array_values(array_unique(array_filter(
            array_map('intval', $endpoints->resolveProductIds($user)),
            fn (int $id) => $id > 0
        )));

        $required = $this->parseProductQuery($request);
        // If Bar2 sends ?products=… require a matching plan product id,
        // unless user already has Ahrefs / Ahrefs Bar access.
        if ($required !== [] && count(array_intersect($required, $products)) === 0 && ! $hasBar) {
            return $this->fail('no_product', 200, $userPayload);
        }

        return response()->json([
            'ok' => true,
            'site' => $request->getHost(),
            'user' => $userPayload,
            'products' => $products,
        ])->withHeaders($this->corsHeaders());
    }

    /**
     * @return array{uid: int, login: string, email: string, name: string}
     */
    protected function userPayload(User $user): array
    {
        return [
            'uid' => (int) $user->id,
            'login' => (string) ($user->email ?? ''),
            'email' => (string) ($user->email ?? ''),
            'name' => (string) ($user->name ?: $user->email ?: 'member'),
        ];
    }

    protected function resolveUser(Request $request): ?User
    {
        if ($request->user()) {
            return $request->user();
        }

        $sessionId = $this->extractSessionId($request);
        if ($sessionId === '') {
            return null;
        }

        if (config('session.driver') !== 'database') {
            return null;
        }

        $lifetime = (int) config('session.lifetime', 120);
        $row = DB::table(config('session.table', 'sessions'))
            ->where('id', $sessionId)
            ->where('last_activity', '>=', now()->subMinutes($lifetime)->getTimestamp())
            ->first();

        if (! $row || empty($row->user_id)) {
            return null;
        }

        return User::query()->find($row->user_id);
    }

    protected function extractSessionId(Request $request): string
    {
        $sessionCookie = (string) config('session.cookie');

        // Already decrypted by EncryptCookies when cookie name matches.
        $fromApp = trim((string) $request->cookies->get($sessionCookie, ''));
        if ($fromApp !== '') {
            return $fromApp;
        }

        $aliasNames = [
            $sessionCookie,
            'semrushtoolz-session',
            'PHPSESSID',
            'amember',
            'amember_ru',
            'amember_r',
            'amember_sess',
            'sess',
        ];

        // Raw Cookie header — Bar2 often renames the value to PHPSESSID=...
        $rawHeader = (string) $request->header('Cookie', '');
        if ($rawHeader !== '' && preg_match_all('/(?:^|;\s*)([^=]+)=([^;]*)/', $rawHeader, $matches, PREG_SET_ORDER)) {
            foreach ($matches as $m) {
                $name = trim($m[1]);
                $value = trim(urldecode($m[2]));
                if ($value === '' || ! in_array($name, $aliasNames, true)) {
                    continue;
                }
                $id = $this->normalizeSessionId($value);
                if ($id !== '') {
                    return $id;
                }
            }
            foreach ($matches as $m) {
                $value = trim(urldecode($m[2]));
                if (strlen($value) < 20) {
                    continue;
                }
                $id = $this->normalizeSessionId($value);
                if ($id !== '') {
                    return $id;
                }
            }
        }

        return '';
    }

    /**
     * Decrypt Laravel cookie payload when Bar2 forwards encrypted browser cookie as PHPSESSID.
     */
    protected function normalizeSessionId(string $value): string
    {
        $value = trim($value);
        if ($value === '') {
            return '';
        }

        try {
            $encrypter = app('encrypter');
            $decrypted = $encrypter->decrypt($value, EncryptCookies::serialized());
            $sessionCookie = (string) config('session.cookie');

            $validated = CookieValuePrefix::validate($sessionCookie, $decrypted, $encrypter->getAllKeys());
            if (is_string($validated) && $validated !== '') {
                return $validated;
            }

            // Prefix was built for semrushtoolz-session; strip hmac| prefix.
            if (strlen($decrypted) > 41 && ($decrypted[40] ?? '') === '|') {
                return CookieValuePrefix::remove($decrypted);
            }

            return is_string($decrypted) ? $decrypted : '';
        } catch (DecryptException) {
            return $value;
        } catch (\Throwable) {
            return $value;
        }
    }

    /**
     * @return array<int, int>
     */
    protected function parseProductQuery(Request $request): array
    {
        $raw = trim((string) $request->query('products', ''));
        if ($raw === '') {
            return [];
        }

        $ids = [];
        foreach (preg_split('/[,\s]+/', $raw) as $part) {
            $part = ltrim(trim((string) $part), '#');
            if ($part !== '' && ctype_digit($part)) {
                $ids[] = (int) $part;
            }
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  array{uid: int, login: string, email: string, name: string}|null  $user
     */
    protected function fail(string $error, int $http = 200, ?array $user = null): JsonResponse
    {
        $out = [
            'ok' => false,
            'error' => $error,
        ];

        if ($user !== null) {
            $out['user'] = $user;
        }

        return response()->json($out, $http)->withHeaders($this->corsHeaders());
    }

    /**
     * @return array<string, string>
     */
    protected function corsHeaders(): array
    {
        return [
            'Cache-Control' => 'no-store',
            'Access-Control-Allow-Origin' => '*',
            'Access-Control-Allow-Methods' => 'GET, POST, OPTIONS',
            'Access-Control-Allow-Headers' => 'Content-Type, Cookie, X-TM-Check-Key',
        ];
    }
}
