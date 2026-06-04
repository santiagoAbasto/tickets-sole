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
        return (new MailMessage)
            ->subject("[{$this->ticket->code}] Nuevo ticket: {$this->ticket->subject}")
            ->greeting('Nuevo ticket recibido')
            ->line("Se creó el ticket {$this->ticket->code}.")
            ->line("Asunto: {$this->ticket->subject}")
            ->action('Ver ticket', route('admin.tickets.show', $this->ticket))
            ->line('Gracias por usar la mesa de ayuda de Osole.');
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
