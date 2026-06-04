<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use App\Models\User;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    /**
     * Aggregate metrics for a date range (inclusive), keyed for the report UI/PDF.
     *
     * @return array<string, mixed>
     */
    public function summary(Carbon $from, Carbon $to): array
    {
        $createdInRange = Ticket::whereBetween('created_at', [$from, $to]);
        $resolvedInRange = Ticket::whereNotNull('resolved_at')->whereBetween('resolved_at', [$from, $to]);

        $created = (clone $createdInRange)->count();
        $resolved = (clone $resolvedInRange)->count();

        return [
            'kpis' => [
                'created' => $created,
                'resolved' => $resolved,
                'resolution_rate' => $created > 0 ? (int) round($resolved / $created * 100) : 0,
                'avg_resolution_hours' => $this->avgHours($resolvedInRange, 'resolved_at'),
                'avg_first_response_hours' => $this->avgHours(
                    Ticket::whereNotNull('first_response_at')->whereBetween('first_response_at', [$from, $to]),
                    'first_response_at',
                ),
                'overdue' => Ticket::overdue()->count(),
            ],
            'daily' => $this->daily($from, $to),
            'by_status' => $this->byCatalog(clone $createdInRange, 'status_id', TicketStatus::ordered()->get()),
            'by_priority' => $this->byCatalog(clone $createdInRange, 'priority_id', TicketPriority::ordered()->get()),
            'by_category' => $this->byCatalog(clone $createdInRange, 'category_id', TicketCategory::ordered()->get()),
            'by_agent' => $this->byAgent($from, $to),
        ];
    }

    /** Flat ticket rows for CSV export. */
    public function rows(Carbon $from, Carbon $to): Collection
    {
        return Ticket::whereBetween('created_at', [$from, $to])
            ->with(['customer:id,name,email', 'assignee:id,name', 'category:id,name', 'priority:id,name', 'status:id,name'])
            ->orderBy('created_at')
            ->get();
    }

    private function avgHours($query, string $endColumn): ?float
    {
        $minutes = $query
            ->selectRaw("AVG(TIMESTAMPDIFF(MINUTE, created_at, {$endColumn})) as m")
            ->value('m');

        return $minutes ? round($minutes / 60, 1) : null;
    }

    private function daily(Carbon $from, Carbon $to): array
    {
        $days = min($from->diffInDays($to) + 1, 92); // cap series length
        $start = $to->copy()->subDays($days - 1)->startOfDay();

        $created = Ticket::where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) d, COUNT(*) c')->groupBy('d')->pluck('c', 'd');
        $resolved = Ticket::whereNotNull('resolved_at')->where('resolved_at', '>=', $start)
            ->selectRaw('DATE(resolved_at) d, COUNT(*) c')->groupBy('d')->pluck('c', 'd');

        $series = [];
        for ($i = 0; $i < $days; $i++) {
            $day = $start->copy()->addDays($i);
            $key = $day->toDateString();
            $series[] = [
                'date' => $key,
                'label' => $day->isoFormat('DD MMM'),
                'created' => (int) ($created[$key] ?? 0),
                'resolved' => (int) ($resolved[$key] ?? 0),
            ];
        }

        return $series;
    }

    private function byCatalog($query, string $column, Collection $catalog): array
    {
        $counts = $query->selectRaw("{$column} as k, COUNT(*) c")->groupBy($column)->pluck('c', 'k');

        return $catalog->map(fn ($item) => [
            'name' => $item->name,
            'color' => $item->color,
            'value' => (int) ($counts[$item->id] ?? 0),
        ])->all();
    }

    private function byAgent(Carbon $from, Carbon $to): array
    {
        $resolved = Ticket::whereNotNull('resolved_at')->whereBetween('resolved_at', [$from, $to])
            ->whereNotNull('assigned_to')
            ->selectRaw('assigned_to, COUNT(*) c, AVG(TIMESTAMPDIFF(MINUTE, created_at, resolved_at)) avg_min')
            ->groupBy('assigned_to')
            ->get()
            ->keyBy('assigned_to');

        return User::query()->agents()->active()->get(['id', 'name'])
            ->map(fn ($u) => [
                'name' => $u->name,
                'resolved' => (int) ($resolved[$u->id]->c ?? 0),
                'avg_resolution_hours' => isset($resolved[$u->id]) && $resolved[$u->id]->avg_min
                    ? round($resolved[$u->id]->avg_min / 60, 1) : null,
            ])
            ->sortByDesc('resolved')
            ->values()
            ->all();
    }
}
