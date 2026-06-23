<?php

namespace App\Services;

use App\Models\SupportTicket;
use App\Models\SupportTicketMessage;
use App\Models\User;

class SupportTicketService
{
    public function create(User $user, string $subject, string $body, string $priority = 'medium'): SupportTicket
    {
        $ticket = SupportTicket::create([
            'ticket_number' => SupportTicket::generateNumber(),
            'user_id' => $user->id,
            'subject' => $subject,
            'priority' => $priority,
            'status' => SupportTicket::STATUS_OPEN,
        ]);

        $this->addMessage($ticket, $user, $body, false);

        return $ticket;
    }

    public function addMessage(
        SupportTicket $ticket,
        ?User $author,
        string $body,
        bool $isStaff,
    ): SupportTicketMessage {
        $message = $ticket->messages()->create([
            'user_id' => $author?->id,
            'is_staff' => $isStaff,
            'body' => $body,
        ]);

        if ($isStaff && $ticket->status === SupportTicket::STATUS_OPEN) {
            $ticket->update(['status' => SupportTicket::STATUS_REPLIED]);
        } elseif (! $isStaff && $ticket->status === SupportTicket::STATUS_REPLIED) {
            $ticket->update(['status' => SupportTicket::STATUS_OPEN]);
        }

        return $message;
    }

    public function close(SupportTicket $ticket): void
    {
        $ticket->update([
            'status' => SupportTicket::STATUS_CLOSED,
            'closed_at' => now(),
        ]);
    }

    public function listForUser(User $user, int $limit = 20): array
    {
        return $user->supportTickets()
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (SupportTicket $t) => [
                'id' => $t->ticket_number,
                'ticket_id' => $t->id,
                'subject' => $t->subject,
                'status' => $t->statusLabel(),
                'date' => $t->created_at->format('M d'),
            ])
            ->all();
    }
}
