<?php

namespace App\Http\Controllers\Dashboard;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Models\SiteSetting;
use App\Services\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function __construct(
        protected SupportTicketService $tickets,
    ) {}

    public function create(): View
    {
        return view('dashboard.support.create');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:200'],
            'body' => ['required', 'string', 'max:5000'],
            'priority' => ['nullable', 'in:low,medium,high'],
        ]);

        $ticket = $this->tickets->create(
            $request->user(),
            $data['subject'],
            $data['body'],
            $data['priority'] ?? 'medium',
        );

        return redirect()
            ->route('dashboard.support.show', $ticket)
            ->with('success', 'Ticket created.');
    }

    public function show(SupportTicket $ticket): View
    {
        abort_unless($ticket->user_id === auth()->id(), 403);

        $ticket->load(['messages.user']);

        return view('dashboard.support.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        abort_unless($ticket->user_id === auth()->id(), 403);
        abort_if($ticket->status === SupportTicket::STATUS_CLOSED, 403);

        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $this->tickets->addMessage($ticket, $request->user(), $data['body'], false);

        return back()->with('success', 'Message sent.');
    }
}
