<?php

namespace App\Policies;

use App\Models\Ticket;
use App\Models\User;

class TicketPolicy
{
    // Super Admin is granted everything via Gate::before in AppServiceProvider.

    public function viewAny(User $user): bool
    {
        return $user->hasPermissionTo('tickets.viewAny');
    }

    public function view(User $user, Ticket $ticket): bool
    {
        if ($user->isCustomer()) {
            return $this->ownsTicket($user, $ticket);
        }

        if (! $user->hasPermissionTo('tickets.view')) {
            return false;
        }

        return $this->staffCanAccess($user, $ticket);
    }

    public function create(User $user): bool
    {
        return $user->hasPermissionTo('tickets.create');
    }

    public function update(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.update') && $this->staffCanAccess($user, $ticket);
    }

    public function delete(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.delete');
    }

    public function assign(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.assign');
    }

    public function changeStatus(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.changeStatus') && $this->staffCanAct($user, $ticket);
    }

    public function changePriority(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.changePriority') && $this->staffCanAct($user, $ticket);
    }

    public function reply(User $user, Ticket $ticket): bool
    {
        if ($user->isCustomer()) {
            return $user->hasPermissionTo('tickets.reply') && $this->ownsTicket($user, $ticket);
        }

        return $user->hasPermissionTo('tickets.reply') && $this->staffCanAct($user, $ticket);
    }

    public function notifyCustomer(User $user, Ticket $ticket): bool
    {
        return $user->isStaff()
            && $user->hasPermissionTo('tickets.reply')
            && $this->staffCanAct($user, $ticket);
    }

    /** Internal notes — staff only, never customers. */
    public function addNote(User $user, Ticket $ticket): bool
    {
        return $user->isStaff()
            && $user->hasPermissionTo('tickets.note')
            && $this->staffCanAct($user, $ticket);
    }

    public function attach(User $user, Ticket $ticket): bool
    {
        if ($user->isCustomer()) {
            return $user->hasPermissionTo('tickets.attach') && $this->ownsTicket($user, $ticket);
        }

        return $user->hasPermissionTo('tickets.attach') && $this->staffCanAct($user, $ticket);
    }

    /**
     * The assigned agent may request to delegate the ticket to another agent
     * (a Super Admin / Admin then approves). Managers reassign directly instead.
     */
    public function delegate(User $user, Ticket $ticket): bool
    {
        return $user->hasRole('Agente')
            && ! $user->hasAnyRole(['Super Admin', 'Admin'])
            && $ticket->assigned_to === $user->id;
    }

    /** Approve or reject a pending delegation request — anyone who can assign. */
    public function reviewDelegation(User $user, Ticket $ticket): bool
    {
        return $user->hasPermissionTo('tickets.assign');
    }

    /**
     * "Seguir ticket": any ticket-working staff member (Agente / Admin / Super
     * Admin) may take a ticket that isn't theirs and make it their own, so
     * whoever is free can pick it up instead of waiting on the default assignee.
     * Designers (create + view only, no tickets.reply) cannot. No approval
     * needed — unlike delegation. Hidden once the ticket is already theirs.
     */
    public function claim(User $user, Ticket $ticket): bool
    {
        return $user->isStaff()
            && $user->hasPermissionTo('tickets.reply')
            && $ticket->assigned_to !== $user->id;
    }

    // ----------------------------------------------------------------------
    // Visibility rules
    // ----------------------------------------------------------------------

    /**
     * Staff (admins and agents) can VIEW any ticket.
     */
    private function staffCanAccess(User $user, Ticket $ticket): bool
    {
        return $user->isStaff();
    }

    /**
     * Mutating actions (reply, notes, status, attach, notify). Managers
     * (Super Admin / Admin) may act on any ticket; a plain agent may only act
     * on the ticket assigned to them. Prevents an agent from working a ticket
     * that belongs to a colleague.
     */
    private function staffCanAct(User $user, Ticket $ticket): bool
    {
        if ($user->hasAnyRole(['Super Admin', 'Admin'])) {
            return true;
        }

        return $ticket->assigned_to === $user->id;
    }

    private function ownsTicket(User $user, Ticket $ticket): bool
    {
        return $ticket->customer && $ticket->customer->user_id === $user->id;
    }
}
