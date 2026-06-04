<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketAssignedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public Ticket $ticket) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject("[{$this->ticket->code}] Te asignaron un ticket")
            ->greeting("Hola {$notifiable->name},")
            ->line("Se te asignó el ticket {$this->ticket->code}.")
            ->line("Asunto: {$this->ticket->subject}")
            ->action('Atender ticket', route('admin.tickets.show', $this->ticket));
    }
}
