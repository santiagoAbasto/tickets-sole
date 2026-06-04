<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class CustomerTicketCreatedNotification extends Notification implements ShouldQueue
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
        $this->ticket->loadMissing('customer');

        return (new MailMessage)
            ->subject("[{$this->ticket->code}] Ticket creado: {$this->ticket->subject}")
            ->greeting('Creamos un ticket para tu consulta')
            ->line("Tu ticket {$this->ticket->code} quedó registrado en la mesa de ayuda de Osole.")
            ->line("Asunto: {$this->ticket->subject}")
            ->line('Para consultar el estado, ingresá con este código y el email donde recibiste este mensaje.')
            ->action('Seguir ticket', route('public.track.form'))
            ->line('Gracias. El equipo de soporte te responderá por este mismo hilo de atención.');
    }
}
