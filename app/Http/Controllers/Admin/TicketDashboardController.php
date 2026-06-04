<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Services\TicketDashboardStatsService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TicketDashboardController extends Controller
{
    public function __construct(private TicketDashboardStatsService $stats) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Ticket::class);

        $user = $request->user();
        // Plain agents see only their own tickets; admins/super see everything.
        $agentView = $user->is_agent && ! $user->hasAnyRole(['Super Admin', 'Admin']);
        $this->stats->forAgent($agentView ? $user->id : null);

        return view('admin.tickets.dashboard', [
            'agentView' => $agentView,
            'cards' => $this->stats->cards(),
            'queuePulse' => $this->stats->queuePulse(),
            'charts' => [
                'createdVsResolved' => $this->stats->createdVsResolved(14),
                'byStatus' => $this->stats->byStatus(),
                'byPriority' => $this->stats->byPriority(),
                'funnel' => $this->stats->funnel(),
            ],
            'slaBands' => $this->stats->slaBands(),
            'overdueTickets' => $this->stats->overdueTickets($agentView ? 6 : 6),
            'latestTickets' => $this->stats->latestTickets(8),
            'leaderboard' => $agentView ? collect() : $this->stats->leaderboard(6, $user->hasRole('Super Admin')),
        ]);
    }
}
