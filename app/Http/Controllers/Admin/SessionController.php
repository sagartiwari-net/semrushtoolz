<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AffiliatePayout;
use App\Models\ToolSession;
use App\Services\AdminDashboardService;
use App\Services\ToolAccessService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SessionController extends Controller
{
    public function __construct(
        protected AdminDashboardService $dashboard,
        protected ToolAccessService $toolAccess,
    ) {}

    public function index(): View
    {
        $stats = $this->dashboard->sessionStats();

        return view('admin.sessions', [
            'liveSessions' => $stats['live_sessions'],
            'seatCards' => $stats['seat_cards'],
            'sessions' => $this->dashboard->activeSessions(),
        ]);
    }

    public function destroy(ToolSession $session)
    {
        $this->toolAccess->endSession($session, 'admin_killed');

        return back()->with('success', 'Session ended.');
    }
}
