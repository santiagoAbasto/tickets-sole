<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketCreatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket, public bool $email = true) {}

    /**
     * Staff (User) always get the in-app notification; only the primary
     * recipient also gets an email (keeps inboxes and SMTP limits sane).
     *
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        if ($notifiable instanceof User) {
            return $this->email ? ['database', 'mail'] : ['database'];
        }

        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $this->ticket->loadMissing('customer', 'category', 'priority', 'status', 'assignee');

        $message = (new MailMessage)
            ->subject("[{$this->ticket->code}] Nuevo ticket: {$this->ticket->subject}")
            ->view('emails.ticket-created', [
                'ticket' => $this->ticket,
                'ticketUrl' => route('admin.tickets.show', $this->ticket),
                'logoUrl' => asset('favicon/web-app-manifest-192x192.png'),
            ]);

        if ($this->ticket->customer?->email) {
            $message->replyTo($this->ticket->customer->email, $this->ticket->customer->name);
        }

        return $message;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ticket_created',
            'ticket_id' => $this->ticket->id,
            'code' => $this->ticket->code,
            'subject' => $this->ticket->subject,
            'source' => $this->ticket->source,
            'icon' => 'ticket',
            'title' => 'Nuevo ticket',
            'message' => "{$this->ticket->code} · {$this->ticket->subject}",
            'url' => route('admin.tickets.show', $this->ticket),
        ];
    }
}
