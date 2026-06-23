<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\ToolAccessServer;
use App\Services\DashboardPresenter;
use App\Services\ExtensionAccessService;
use App\Services\ToolAccessService;
use App\Services\ToolEndpointService;
use App\Services\ToolHandshakeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ToolController extends Controller
{
    public function __construct(
        protected DashboardPresenter $presenter,
        protected ToolAccessService $tools,
        protected ToolHandshakeService $handshake,
        protected ExtensionAccessService $extensionAccess,
        protected ToolEndpointService $endpoints
    ) {}

    protected function shared(): array
    {
        $user = Auth::user();

        return [
            'user' => $this->presenter->userContext($user),
            'notifications' => $this->presenter->notifications($user),
        ];
    }

    public function hub(string $group)
    {
        $config = $this->handshake->hubConfig($group);

        abort_unless($config, 404);

        $grant = $config['grant'] ?? $group;

        if (! $this->tools->userGrantsTool(Auth::user(), $grant)) {
            return redirect()->route('dashboard.shop')
                ->with('error', 'Subscribe to a plan that includes '.($config['title'] ?? $group).' to access this page.');
        }

        return view('dashboard.access.hub', array_merge($this->shared(), [
            'group' => $group,
            'hub' => $config,
            'activeNav' => 'dashboard.tools',
        ]));
    }

    public function route(Request $request, string $tool)
    {
        abort_unless($this->endpoints->endpoint($tool), 404);

        try {
            $redirectUrl = $this->handshake->handshake(
                Auth::user(),
                $tool,
                $request->ip()
            );

            return redirect()->away($redirectUrl);
        } catch (\Throwable $e) {
            return view('dashboard.access.error', array_merge($this->shared(), [
                'message' => $e->getMessage(),
                'tool' => $tool,
                'activeNav' => 'dashboard.tools',
            ]));
        }
    }

    public function extAccess(Request $request, string $tool)
    {
        $server = ToolAccessServer::where('slug', $tool)->where('is_active', true)->first();
        $toolKey = $server?->extension_tool_key ?: $tool;

        try {
            $redirectUrl = $this->extensionAccess->initiate(
                Auth::user(),
                $toolKey,
                $request->ip()
            );

            return redirect()->away($redirectUrl);
        } catch (\Throwable $e) {
            return view('dashboard.access.error', array_merge($this->shared(), [
                'message' => $e->getMessage(),
                'tool' => $tool,
                'activeNav' => 'dashboard.tools',
            ]));
        }
    }

    public function endSession(Request $request, string $tool)
    {
        $session = $this->tools->activeSession(Auth::user(), $tool);

        if ($session) {
            $this->tools->endSession($session);
        }

        return back()->with('success', 'Session ended.');
    }
}
