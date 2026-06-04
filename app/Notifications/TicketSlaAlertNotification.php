<?php

namespace App\Notifications;

use App\Models\Ticket;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class TicketSlaAlertNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public const DUE_SOON = 'due_soon';

    public const OVERDUE = 'overdue';

    public function __construct(public Ticket $ticket, public string $kind) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $overdue = $this->kind === self::OVERDUE;
        $subject = $overdue
            ? "[{$this->ticket->code}] SLA vencido"
            : "[{$this->ticket->code}] Vence pronto";

        $mail = (new MailMessage)
            ->subject($subject)
            ->greeting($overdue ? 'SLA vencido' : 'Atención: vence pronto')
            ->line("Ticket {$this->ticket->code} — {$this->ticket->subject}")
            ->line('Prioridad: '.($this->ticket->priority->name ?? '—'));

        if ($overdue) {
            $mail->line('Atrasado: '.$this->ticket->overdueForHumans())
                ->error();
        } else {
            $mail->line('Vence: '.optional($this->ticket->due_at)->diffForHumans());
        }

        return $mail->action('Atender ahora', route('admin.tickets.show', $this->ticket));
    }
}
