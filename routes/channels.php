<?php

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('App.Models.User.{id}', function (User $user, int $id) {
    return (int) $user->id === $id;
});

// Private per-ticket channel: only users allowed to view the ticket may listen.
Broadcast::channel('ticket.{ticket}', function (User $user, Ticket $ticket) {
    return $user->can('view', $ticket);
});

// Staff-only dashboard channel for live queue/KPI updates.
Broadcast::channel('dashboard', function (User $user) {
    return $user->isStaff();
});
