<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportTicket;
use App\Services\SupportTicketService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SupportTicketController extends Controller
{
    public function __construct(
        protected SupportTicketService $tickets,
    ) {}

    public function index(Request $request): View
    {
        $query = SupportTicket::with('user')->latest();

        if ($request->filled('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        if ($request->filled('priority') && $request->priority !== 'all') {
            $query->where('priority', $request->priority);
        }

        $tickets = $query->limit(100)->get()->map(fn (SupportTicket $t) => [
            'id' => $t->ticket_number,
            'ticket_id' => $t->id,
            'user' => $t->user->name,
            'subject' => $t->subject,
            'priority' => $t->priorityLabel(),
            'status' => $t->statusLabel(),
            'date' => $t->created_at->format('M d'),
        ]);

        return view('admin.tickets', compact('tickets'));
    }

    public function show(SupportTicket $ticket): View
    {
        $ticket->load(['user', 'messages.user']);

        return view('admin.tickets.show', compact('ticket'));
    }

    public function reply(Request $request, SupportTicket $ticket)
    {
        $data = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
        ]);

        $this->tickets->addMessage($ticket, $request->user(), $data['body'], true);

        return back()->with('success', 'Reply sent.');
    }

    public function close(SupportTicket $ticket)
    {
        $this->tickets->close($ticket);

        return redirect()->route('admin.tickets')->with('success', 'Ticket closed.');
    }
}
