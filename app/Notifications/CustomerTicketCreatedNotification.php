<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\URL;

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
            ->line('Hacé clic en el botón para seguir tu ticket y ver las respuestas — se abre directo, sin cargar nada.')
            ->action('Seguir mi ticket', URL::signedRoute('public.track.direct', ['ticket' => $this->ticket->id]))
            ->line('Gracias. El equipo de soporte te responderá por este mismo hilo de atención.');
    }
}
