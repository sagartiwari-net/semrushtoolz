<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SecurityAlert;
use App\Models\User;
use App\Services\SecurityMonitorService;
use App\Services\UserSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SecurityController extends Controller
{
    public function __construct(
        protected SecurityMonitorService $security,
        protected UserSessionService $sessions,
    ) {}

    public function index()
    {
        $alerts = SecurityAlert::with('user')
            ->orderByDesc('created_at')
            ->limit(50)
            ->get();

        $openCount = SecurityAlert::where('status', 'open')->count();
        $criticalCount = SecurityAlert::where('status', 'open')->where('severity', 'critical')->count();

        $flaggedUsers = User::where('security_alert_count', '>', 0)
            ->orWhere('status', 'blocked')
            ->orderByDesc('security_alert_count')
            ->limit(10)
            ->get();

        return view('admin.security', compact('alerts', 'openCount', 'criticalCount', 'flaggedUsers'));
    }

    public function showUser(User $user)
    {
        $summary = $this->security->getUserIpSummary($user->id);
        $alerts = $user->securityAlerts()->orderByDesc('created_at')->get();
        $sessions = $this->sessions
            ->activeSessions($user->id)
            ->map(fn ($session) => $this->sessions->formatSessionRow($session));

        return view('admin.user-security', compact('user', 'summary', 'alerts', 'sessions'));
    }

    public function blockUser(Request $request, User $user)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $this->security->blockUser($user, $request->reason);

        SecurityAlert::where('user_id', $user->id)
            ->where('status', 'open')
            ->update([
                'status' => 'resolved',
                'resolved_at' => now(),
                'admin_note' => 'User blocked: '.$request->reason,
            ]);

        return back()->with('success', "User {$user->name} has been blocked immediately.");
    }

    public function unblockUser(User $user)
    {
        $this->security->unblockUser($user);

        return back()->with('success', "User {$user->name} has been unblocked.");
    }

    public function resolveAlert(SecurityAlert $alert)
    {
        $alert->update([
            'status' => 'resolved',
            'resolved_at' => now(),
        ]);

        return back()->with('success', 'Alert marked as resolved.');
    }

    public function killAllSessions(User $user)
    {
        $count = $this->sessions->killAllSessions($user->id);

        return back()->with('success', "Ended {$count} active session(s) for {$user->name}. They can sign in again now.");
    }

    public function killSession(Request $request, string $sessionId)
    {
        $session = DB::table('sessions')->where('id', $sessionId)->first();

        abort_unless($session, 404);

        $this->sessions->killSession($sessionId);

        return back()->with('success', 'Session ended.');
    }
}
