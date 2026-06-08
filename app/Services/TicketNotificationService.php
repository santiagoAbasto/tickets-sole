<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketMessage;
use App\Models\User;
use App\Notifications\CustomerTicketCreatedNotification;
use App\Notifications\TicketAssignedNotification;
use App\Notifications\TicketCreatedNotification;
use App\Notifications\TicketRepliedNotification;
use App\Notifications\TicketSlaAlertNotification;
use App\Notifications\TicketStatusChangedNotification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification;

class TicketNotificationService
{
    public function __construct(private TelegramNotifier $telegram) {}

    /**
     * In-app notify all Admins/Super Admins (+ assigned agent); email goes only
     * to the primary recipient to keep inboxes (and SMTP limits) sane.
     */
    public function ticketCreated(Ticket $ticket): void
    {
        $recipients = $this->staffAudience($ticket);
        $primaryId = $this->primaryStaffId($ticket, $recipients);

        foreach ($recipients as $user) {
            $this->safely(fn () => $user->notify(new TicketCreatedNotification($ticket, email: $user->id === $primaryId)));
        }

        // Telegram ping — deferred so a slow/unreachable bot never delays ticket
        // creation. No-op unless configured + enabled.
        defer(fn () => $this->telegram->ticketCreated($ticket));
    }

    public function ticketCreatedForCustomer(Ticket $ticket): void
    {
        $this->notifyCustomer($ticket, new CustomerTicketCreatedNotification($ticket));
    }

    public function ticketAssigned(Ticket $ticket, User $agent): void
    {
        $this->safely(fn () => $agent->notify(new TicketAssignedNotification($ticket)));
    }

    /**
     * Notify the "other side" of the conversation about a new reply:
     * a customer reply pings staff; an agent reply pings the customer.
     */
    public function ticketReplied(Ticket $ticket, TicketMessage $message): void
    {
        if ($message->author_type === TicketMessage::AUTHOR_CUSTOMER) {
            $recipients = $this->staffAudience($ticket);
            $primaryId = $this->primaryStaffId($ticket, $recipients);

            foreach ($recipients as $user) {
                $this->safely(fn () => $user->notify(new TicketRepliedNotification($ticket, $message, email: $user->id === $primaryId)));
            }

            return;
        }

        $this->notifyCustomer($ticket, new TicketRepliedNotification($ticket, $message));
    }

    public function statusChanged(Ticket $ticket, string $statusName): void
    {
        $this->notifyCustomer($ticket, new TicketStatusChangedNotification($ticket, $statusName));
    }

    /** SLA alert (due soon / overdue) to the assigned agent or admins. */
    public function slaAlert(Ticket $ticket, string $kind): void
    {
        $recipients = $this->staffRecipients($ticket);

        if ($recipients->isNotEmpty()) {
            $this->safely(fn () => Notification::send($recipients, new TicketSlaAlertNotification($ticket, $kind)));
        }
    }

    // ----------------------------------------------------------------------

    /** Everyone who should be aware: all active admins/super + assigned agent. */
    private function staffAudience(Ticket $ticket): Collection
    {
        $ids = User::role(['Admin', 'Super Admin'])->where('is_active', true)->pluck('id');

        if ($ticket->assigned_to) {
            $ids->push($ticket->assigned_to);
        }

        return User::whereIn('id', $ids->unique())->get();
    }

    /** The single recipient who also gets an email (assigned agent, else first admin). */
    private function primaryStaffId(Ticket $ticket, Collection $audience): ?int
    {
        return $ticket->assigned_to ?: $audience->first()?->id;
    }

    /** Assigned agent if present, otherwise fall back to admins (SLA alerts). */
    private function staffRecipients(Ticket $ticket): Collection
    {
        if ($ticket->assigned_to) {
            return User::whereKey($ticket->assigned_to)->get();
        }

        return User::role(['Admin', 'Super Admin'])->where('is_active', true)->get();
    }

    private function notifyCustomer(Ticket $ticket, $notification): void
    {
        $ticket->loadMissing('customer');
        $email = $ticket->customer?->email;

        if (! $email) {
            return;
        }

        $this->safely(fn () => Notification::route('mail', $email)->notify($notification));
    }

    /**
     * Best-effort send: a transport failure (SMTP down, a rejected recipient,
     * etc.) must never bubble up and break the originating request — creating a
     * ticket or posting a reply has to succeed even when mail does not. In-app
     * channels run before mail in via(), so the bell notification still lands.
     * Failures are reported (logged) and swallowed.
     */
    private function safely(callable $send): void
    {
        try {
            $send();
        } catch (\Throwable $e) {
            report($e);
        }
    }
}
