<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    /**
     * New tickets since `after` (for the Super Admin browser notifications).
     * Super Admin only. Returns the current latest id so the client can baseline.
     */
    public function ticketAlerts(Request $request): JsonResponse
    {
        abort_unless($request->user()->hasRole('Super Admin'), 403);

        $after = $request->integer('after');
        $userId = $request->user()->id;

        $tickets = $after > 0
            ? Ticket::with('customer:id,name')
                ->where('id', '>', $after)
                // Web/portal tickets (created_by null) count; skip ones the admin loaded themselves.
                ->where(fn ($q) => $q->whereNull('created_by')->orWhere('created_by', '!=', $userId))
                ->latest('id')
                ->limit(15)
                ->get()
                ->map(fn (Ticket $t) => [
                    'id' => $t->id,
                    'code' => $t->code,
                    'subject' => $t->subject,
                    'customer' => $t->customer?->name,
                    'source' => $t->source,
                    'url' => route('admin.tickets.show', $t->id),
                ])
                ->values()
            : collect();

        return response()->json([
            'latest_id' => (int) Ticket::max('id'),
            'tickets' => $tickets,
        ]);
    }

    /** Open a notification: mark it read and go to its target. */
    public function open(Request $request, string $id): RedirectResponse
    {
        $notification = $request->user()->notifications()->whereKey($id)->first();

        if (! $notification) {
            return redirect()->route('admin.tickets.dashboard');
        }

        $notification->markAsRead();

        return redirect()->to($notification->data['url'] ?? route('admin.tickets.dashboard'));
    }

    public function markAllRead(Request $request): RedirectResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return back()->with('success', 'Notificaciones marcadas como leídas.');
    }
}
