<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Collection;

class AgentPerformanceService
{
    /**
     * Detailed performance metrics for a single agent.
     *
     * @return array<string, mixed>
     */
    public function forAgent(User $agent): array
    {
        $assigned = Ticket::query()->assignedTo($agent->id);

        $resolvedTotal = (clone $assigned)->resolved()->count();
        $withinSla = (clone $assigned)->resolved()
            ->whereColumn('resolved_at', '<=', 'due_at')
            ->count();

        $avgMinutes = (clone $assigned)->resolved()
            ->whereNotNull('due_at')
            ->selectRaw('AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_min')
            ->value('avg_min');

        return [
            'id' => $agent->id,
            'name' => $agent->name,
            'job_title' => $agent->job_title,
            'avatar_url' => $agent->avatarUrl(),
            'initials' => $agent->initials(),
            'resolved_today' => (clone $assigned)->whereDate('resolved_at', today())->count(),
            'resolved_month' => (clone $assigned)
                ->whereMonth('resolved_at', now()->month)
                ->whereYear('resolved_at', now()->year)
                ->count(),
            'resolved_total' => $resolvedTotal,
            'pending' => (clone $assigned)->open()->count(),
            'overdue' => (clone $assigned)->overdue()->count(),
            'avg_resolution_hours' => $avgMinutes ? round($avgMinutes / 60, 1) : null,
            'efficiency' => $resolvedTotal > 0 ? (int) round($withinSla / $resolvedTotal * 100) : null,
        ];
    }

    /**
     * Leaderboard of agents ranked by tickets resolved this month.
     * Uses grouped aggregate queries (constant query count, no N+1).
     */
    public function leaderboard(int $limit = 8, bool $includeSuperAdmins = true): Collection
    {
        $query = User::query()->agents()->active();

        if (! $includeSuperAdmins) {
            $query->whereDoesntHave('roles', fn ($q) => $q->where('name', 'Super Admin'));
        }

        $agents = $query->get(['id', 'name', 'avatar_path', 'job_title']);

        $resolvedMonth = $this->groupedCount(
            Ticket::query()->resolved()
                ->whereMonth('resolved_at', now()->month)
                ->whereYear('resolved_at', now()->year)
        );
        $resolvedTotal = $this->groupedCount(Ticket::query()->resolved());
        $withinSla = $this->groupedCount(
            Ticket::query()->resolved()->whereColumn('resolved_at', '<=', 'due_at')
        );
        $pending = $this->groupedCount(Ticket::query()->open());
        $overdue = $this->groupedCount(Ticket::query()->overdue());

        $avgMinutes = Ticket::query()->resolved()
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->selectRaw('assigned_to, AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) as avg_min')
            ->pluck('avg_min', 'assigned_to');

        return $agents->map(function (User $agent) use ($resolvedMonth, $resolvedTotal, $withinSla, $pending, $overdue, $avgMinutes) {
            $total = (int) ($resolvedTotal[$agent->id] ?? 0);
            $sla = (int) ($withinSla[$agent->id] ?? 0);
            $avg = $avgMinutes[$agent->id] ?? null;

            return [
                'id' => $agent->id,
                'name' => $agent->name,
                'job_title' => $agent->job_title,
                'avatar_url' => $agent->avatarUrl(),
                'initials' => $agent->initials(),
                'resolved_month' => (int) ($resolvedMonth[$agent->id] ?? 0),
                'resolved_total' => $total,
                'pending' => (int) ($pending[$agent->id] ?? 0),
                'overdue' => (int) ($overdue[$agent->id] ?? 0),
                'avg_resolution_hours' => $avg ? round($avg / 60, 1) : null,
                'efficiency' => $total > 0 ? (int) round($sla / $total * 100) : null,
            ];
        })
            ->sortByDesc('resolved_month')
            ->values()
            ->take($limit);
    }

    /** assigned_to => count, for any ticket query grouped by agent. */
    private function groupedCount($query): Collection
    {
        return $query
            ->whereNotNull('assigned_to')
            ->groupBy('assigned_to')
            ->selectRaw('assigned_to, COUNT(*) as aggregate')
            ->pluck('aggregate', 'assigned_to');
    }
}
