<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketActivityLog;
use App\Models\User;

class TicketActivityLoggerService
{
    /**
     * Record an activity entry on a ticket.
     *
     * @param  array<string, mixed>  $meta
     */
    public function log(Ticket $ticket, string $action, string $description, array $meta = [], ?User $actor = null): TicketActivityLog
    {
        return $ticket->activityLogs()->create([
            'user_id' => ($actor ?? auth()->user())?->id,
            'action' => $action,
            'description' => $description,
            'meta' => $meta ?: null,
        ]);
    }

    public function created(Ticket $ticket, ?User $actor = null): void
    {
        $this->log($ticket, 'created', 'Ticket creado', [], $actor);
    }

    public function assigned(Ticket $ticket, User $agent, ?User $actor = null): void
    {
        $this->log($ticket, 'assigned', "Asignado a {$agent->name}", ['agent_id' => $agent->id], $actor);
    }

    public function unassigned(Ticket $ticket, ?User $actor = null): void
    {
        $this->log($ticket, 'unassigned', 'Asignación removida', [], $actor);
    }

    /** An agent took the ticket for themselves via "Seguir ticket". */
    public function claimed(Ticket $ticket, User $agent, ?User $actor = null): void
    {
        $this->log($ticket, 'claimed', "{$agent->name} tomó el ticket", ['agent_id' => $agent->id], $actor ?? $agent);
    }

    public function statusChanged(Ticket $ticket, string $from, string $to, ?User $actor = null): void
    {
        $this->log($ticket, 'status_changed', "Estado: {$from} → {$to}", compact('from', 'to'), $actor);
    }

    public function priorityChanged(Ticket $ticket, string $from, string $to, ?User $actor = null): void
    {
        $this->log($ticket, 'priority_changed', "Prioridad: {$from} → {$to}", compact('from', 'to'), $actor);
    }

    public function replied(Ticket $ticket, string $authorType, ?User $actor = null): void
    {
        $label = $authorType === 'customer' ? 'cliente' : 'agente';
        $this->log($ticket, 'replied', "Nueva respuesta del {$label}", ['author_type' => $authorType], $actor);
    }

    public function noteAdded(Ticket $ticket, ?User $actor = null): void
    {
        $this->log($ticket, 'note_added', 'Nota interna agregada', [], $actor);
    }

    public function customerNotified(Ticket $ticket, ?User $actor = null): void
    {
        $this->log($ticket, 'customer_notified', 'Aviso de creación enviado al cliente', [], $actor);
    }

    public function whatsappContacted(Ticket $ticket, ?User $actor = null): void
    {
        $this->log($ticket, 'whatsapp_contacted', 'Contactado por WhatsApp', [], $actor);
    }

    public function delegationRequested(Ticket $ticket, User $target, ?User $actor = null): void
    {
        $this->log($ticket, 'delegation_requested', "Solicitó delegar el soporte a {$target->name}", ['target_id' => $target->id], $actor);
    }

    public function delegationApproved(Ticket $ticket, string $from, string $to, ?User $actor = null): void
    {
        $this->log($ticket, 'delegation_approved', "Delegación aprobada: {$from} → {$to}", compact('from', 'to'), $actor);
    }

    public function delegationRejected(Ticket $ticket, User $target, ?User $actor = null): void
    {
        $this->log($ticket, 'delegation_rejected', "Delegación a {$target->name} rechazada", ['target_id' => $target->id], $actor);
    }

    public function attachmentAdded(Ticket $ticket, string $filename, ?User $actor = null): void
    {
        $this->log($ticket, 'attachment_added', "Archivo adjuntado: {$filename}", ['filename' => $filename], $actor);
    }

    public function attachmentRemoved(Ticket $ticket, string $filename, ?User $actor = null): void
    {
        $this->log($ticket, 'attachment_removed', "Archivo eliminado: {$filename}", ['filename' => $filename], $actor);
    }

    public function resolved(Ticket $ticket, ?User $actor = null): void
    {
        $this->log($ticket, 'resolved', 'Ticket marcado como resuelto', [], $actor);
    }

    public function closed(Ticket $ticket, ?User $actor = null): void
    {
        $this->log($ticket, 'closed', 'Ticket cerrado', [], $actor);
    }

    public function reopened(Ticket $ticket, ?User $actor = null): void
    {
        $this->log($ticket, 'reopened', 'Ticket reabierto', [], $actor);
    }
}
