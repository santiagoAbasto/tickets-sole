<?php

namespace App\Notifications;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketRepliedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket, public TicketMessage $message, public bool $email = true) {}

    /**
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
        $excerpt = str($this->message->body)->limit(160);

        return (new MailMessage)
            ->subject("[{$this->ticket->code}] Nueva respuesta")
            ->greeting('Hay una nueva respuesta')
            ->line("Ticket {$this->ticket->code} — {$this->ticket->subject}")
            ->line('"'.$excerpt.'"')
            ->action('Ver conversación', route('admin.tickets.show', $this->ticket));
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'type' => 'ticket_replied',
            'ticket_id' => $this->ticket->id,
            'code' => $this->ticket->code,
            'icon' => 'message-square',
            'title' => 'Nueva respuesta',
            'message' => "{$this->ticket->code} · {$this->ticket->subject}",
            'url' => route('admin.tickets.show', $this->ticket),
        ];
    }
}
