<?php

namespace App\Services;

use App\Models\Ticket;
use App\Models\TicketCategory;
use App\Models\TicketPriority;
use App\Models\TicketStatus;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

class TicketDashboardStatsService
{
    /** When set, every metric is scoped to tickets assigned to this agent. */
    private ?int $agentId = null;

    public function __construct(
        private AgentPerformanceService $performance,
    ) {}

    /** Scope all dashboard metrics to a single agent's tickets. */
    public function forAgent(?int $agentId): static
    {
        $this->agentId = $agentId;

        return $this;
    }

    /** Base query, scoped to the agent when applicable. */
    private function base(): Builder
    {
        return Ticket::query()->when($this->agentId, fn (Builder $q) => $q->where('assigned_to', $this->agentId));
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function cards(): array
    {
        $createdThisWeek = $this->base()->whereBetween('created_at', [now()->subDays(7), now()])->count();
        $createdPrevWeek = $this->base()->whereBetween('created_at', [now()->subDays(14), now()->subDays(7)])->count();

        $resolvedToday = $this->base()->whereDate('resolved_at', today())->count();
        $resolvedYesterday = $this->base()->whereDate('resolved_at', today()->subDay())->count();

        $resolvedMonth = $this->base()->whereMonth('resolved_at', now()->month)->whereYear('resolved_at', now()->year)->count();
        $resolvedPrevMonth = $this->base()->whereMonth('resolved_at', now()->subMonth()->month)
            ->whereYear('resolved_at', now()->subMonth()->year)->count();

        $frAvgNow = $this->avgHours('first_response_at', now()->startOfMonth());
        $frAvgPrev = $this->avgHours('first_response_at', now()->subMonth()->startOfMonth(), now()->startOfMonth());

        $resAvgNow = $this->avgHours('resolved_at', now()->startOfMonth());
        $resAvgPrev = $this->avgHours('resolved_at', now()->subMonth()->startOfMonth(), now()->startOfMonth());

        $statusCount = $this->statusCounts();

        return [
            ['key' => 'total', 'label' => 'Total de tickets', 'value' => $this->base()->count(), 'icon' => 'Tickets', 'tone' => 'brand', 'format' => 'number', 'trend' => $this->trend($createdThisWeek, $createdPrevWeek), 'caption' => 'creados últimos 7 días'],
            ['key' => 'open', 'label' => 'Tickets abiertos', 'value' => $statusCount['abierto'] ?? 0, 'icon' => 'Inbox', 'tone' => 'blue', 'format' => 'number'],
            ['key' => 'in_process', 'label' => 'En proceso', 'value' => $statusCount['en-proceso'] ?? 0, 'icon' => 'Loader', 'tone' => 'indigo', 'format' => 'number'],
            ['key' => 'overdue', 'label' => 'Atrasados', 'value' => $this->base()->overdue()->count(), 'icon' => 'AlarmClock', 'tone' => 'danger', 'format' => 'number', 'caption' => 'fuera de SLA'],
            ['key' => 'resolved_today', 'label' => 'Resueltos hoy', 'value' => $resolvedToday, 'icon' => 'CheckCircle', 'tone' => 'success', 'format' => 'number', 'trend' => $this->trend($resolvedToday, $resolvedYesterday), 'caption' => 'vs. ayer'],
            ['key' => 'resolved_month', 'label' => 'Resueltos este mes', 'value' => $resolvedMonth, 'icon' => 'CalendarCheck', 'tone' => 'success', 'format' => 'number', 'trend' => $this->trend($resolvedMonth, $resolvedPrevMonth), 'caption' => 'vs. mes anterior'],
            ['key' => 'avg_first_response', 'label' => 'Tiempo 1ª respuesta', 'value' => $frAvgNow, 'icon' => 'Zap', 'tone' => 'amber', 'format' => 'hours', 'trend' => $this->trend($frAvgNow, $frAvgPrev), 'lower_is_better' => true, 'caption' => 'promedio del mes'],
            ['key' => 'avg_resolution', 'label' => 'Tiempo de resolución', 'value' => $resAvgNow, 'icon' => 'Timer', 'tone' => 'violet', 'format' => 'hours', 'trend' => $this->trend($resAvgNow, $resAvgPrev), 'lower_is_better' => true, 'caption' => 'promedio del mes'],
        ];
    }

    /**
     * Compact operational counters for the command strip.
     *
     * @return array<string, int|float|null>
     */
    public function queuePulse(): array
    {
        $now = now();
        $oldestDueAt = $this->base()->overdue()->oldest('due_at')->value('due_at');

        return [
            'open' => $this->base()->open()->count(),
            'new_24h' => $this->base()->open()->where('created_at', '>=', $now->copy()->subDay())->count(),
            'due_soon_8h' => $this->base()->open()
                ->whereNotNull('due_at')
                ->whereBetween('due_at', [$now, $now->copy()->addHours(8)])
                ->count(),
            'critical_overdue' => $this->base()->overdue()->where('due_at', '<', $now->copy()->subHours(24))->count(),
            'unassigned' => $this->base()->open()->whereNull('assigned_to')->count(),
            'oldest_overdue_hours' => $oldestDueAt ? round(Carbon::parse($oldestDueAt)->diffInMinutes($now) / 60, 1) : null,
        ];
    }

    /**
     * SLA bands used as a quick heat-map. Bands intentionally overlap with
     * queuePulse because they answer a visual question: where is the pressure?
     *
     * @return array<int, array<string, mixed>>
     */
    public function slaBands(): array
    {
        $now = now();

        return [
            [
                'key' => 'new',
                'label' => 'Nuevos',
                'caption' => 'últimas 24 h',
                'value' => $this->base()->open()->where('created_at', '>=', $now->copy()->subDay())->count(),
                'color' => '#0ea5e9',
            ],
            [
                'key' => 'due_soon',
                'label' => 'Por vencer',
                'caption' => 'próximas 8 h',
                'value' => $this->base()->open()
                    ->whereNotNull('due_at')
                    ->whereBetween('due_at', [$now, $now->copy()->addHours(8)])
                    ->count(),
                'color' => '#f59e0b',
            ],
            [
                'key' => 'overdue_soft',
                'label' => 'Atraso leve',
                'caption' => '0 a 4 h',
                'value' => $this->base()->overdue()
                    ->where('due_at', '>=', $now->copy()->subHours(4))
                    ->count(),
                'color' => '#fb7185',
            ],
            [
                'key' => 'overdue_hot',
                'label' => 'Atraso alto',
                'caption' => '4 a 24 h',
                'value' => $this->base()->overdue()
                    ->where('due_at', '<', $now->copy()->subHours(4))
                    ->where('due_at', '>=', $now->copy()->subHours(24))
                    ->count(),
                'color' => '#f43f5e',
            ],
            [
                'key' => 'overdue_critical',
                'label' => 'Críticos',
                'caption' => '+24 h',
                'value' => $this->base()->overdue()
                    ->where('due_at', '<', $now->copy()->subHours(24))
                    ->count(),
                'color' => '#dc2626',
            ],
        ];
    }

    /**
     * @return array<int, array{date: string, label: string, created: int, resolved: int}>
     */
    public function createdVsResolved(int $days = 14): array
    {
        $start = today()->subDays($days - 1);

        $created = $this->base()->where('created_at', '>=', $start)
            ->selectRaw('DATE(created_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');

        $resolved = $this->base()->whereNotNull('resolved_at')->where('resolved_at', '>=', $start)
            ->selectRaw('DATE(resolved_at) as d, COUNT(*) as c')->groupBy('d')->pluck('c', 'd');

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

    public function byStatus(): array
    {
        $counts = $this->statusCounts();

        return TicketStatus::ordered()->get()->map(fn (TicketStatus $s) => [
            'name' => $s->name, 'slug' => $s->slug, 'color' => $s->color,
            'value' => (int) ($counts[$s->slug] ?? 0),
        ])->all();
    }

    public function byPriority(): array
    {
        $counts = $this->base()->selectRaw('priority_id, COUNT(*) as c')->groupBy('priority_id')->pluck('c', 'priority_id');

        return TicketPriority::ordered()->get()->map(fn (TicketPriority $p) => [
            'name' => $p->name, 'slug' => $p->slug, 'color' => $p->color,
            'value' => (int) ($counts[$p->id] ?? 0),
        ])->all();
    }

    public function byCategory(): array
    {
        $counts = $this->base()->selectRaw('category_id, COUNT(*) as c')->groupBy('category_id')->pluck('c', 'category_id');

        return TicketCategory::ordered()->get()->map(fn (TicketCategory $c) => [
            'name' => $c->name, 'slug' => $c->slug, 'color' => $c->color,
            'value' => (int) ($counts[$c->id] ?? 0),
        ])->all();
    }

    public function funnel(): array
    {
        $statusCount = $this->statusCounts();

        return [
            ['stage' => 'Creados', 'value' => $this->base()->count()],
            ['stage' => 'Asignados', 'value' => $this->base()->whereNotNull('assigned_to')->count()],
            ['stage' => 'En proceso', 'value' => $this->base()->whereNotNull('first_response_at')->count()],
            ['stage' => 'Resueltos', 'value' => $this->base()->resolved()->count()],
            ['stage' => 'Cerrados', 'value' => $statusCount['cerrado'] ?? 0],
        ];
    }

    public function latestTickets(int $limit = 8): Collection
    {
        return $this->base()->with([
            'customer:id,name,email,avatar_path', 'assignee:id,name,avatar_path',
            'priority', 'status', 'category:id,name,slug,color',
        ])->latest()->limit($limit)->get()->map(fn (Ticket $t) => [
            'id' => $t->id, 'code' => $t->code, 'subject' => $t->subject,
            'customer' => $t->customer?->only(['name', 'email']),
            'agent' => $t->assignee?->only(['name']),
            'category' => $t->category?->only(['name', 'color']),
            'priority' => $t->priority?->only(['name', 'slug', 'color']),
            'status' => $t->status?->only(['name', 'slug', 'color']),
            'source' => $t->source,
            'is_overdue' => $t->is_overdue,
            'overdue_hours' => $t->overdue_hours,
            'overdue_human' => $t->overdueForHumans(),
            'due_at' => $t->due_at?->toIso8601String(),
            'age_hours' => $t->created_at ? round($t->created_at->diffInMinutes(now()) / 60, 1) : null,
            'created_at' => $t->created_at?->toIso8601String(),
        ]);
    }

    /** Overdue tickets with exact hours, scoped to the agent when applicable. */
    public function overdueTickets(int $limit = 8): Collection
    {
        return $this->base()->overdue()
            ->with(['customer:id,name', 'assignee:id,name', 'priority', 'status'])
            ->orderBy('due_at')->limit($limit)->get()
            ->map(fn (Ticket $t) => [
                'id' => $t->id, 'code' => $t->code, 'subject' => $t->subject,
                'customer' => $t->customer?->name, 'agent' => $t->assignee?->name,
                'priority' => $t->priority?->only(['name', 'slug', 'color']),
                'overdue_human' => $t->overdueForHumans(),
                'overdue_hours' => $t->overdue_hours,
                'due_at' => $t->due_at?->toIso8601String(),
            ]);
    }

    public function overdueCount(): int
    {
        return $this->base()->overdue()->count();
    }

    public function leaderboard(int $limit = 6, bool $includeSuperAdmins = true): Collection
    {
        return $this->performance->leaderboard($limit, $includeSuperAdmins);
    }

    // ----------------------------------------------------------------------

    private function statusCounts(): array
    {
        $byId = $this->base()->selectRaw('status_id, COUNT(*) as c')->groupBy('status_id')->pluck('c', 'status_id');
        $slugById = TicketStatus::pluck('slug', 'id');

        $out = [];
        foreach ($byId as $id => $count) {
            if ($slug = $slugById[$id] ?? null) {
                $out[$slug] = (int) $count;
            }
        }

        return $out;
    }

    private function avgHours(string $endColumn, Carbon $from, ?Carbon $to = null): ?float
    {
        $query = $this->base()->whereNotNull($endColumn)->where($endColumn, '>=', $from);

        if ($to) {
            $query->where($endColumn, '<', $to);
        }

        $minutes = $query->selectRaw("AVG(TIMESTAMPDIFF(MINUTE, created_at, {$endColumn})) as avg_min")->value('avg_min');

        return $minutes ? round($minutes / 60, 1) : null;
    }

    /**
     * @return array{percent: int, direction: string}|null
     */
    private function trend(int|float|null $current, int|float|null $previous): ?array
    {
        $current = (float) ($current ?? 0);
        $previous = (float) ($previous ?? 0);

        if ($previous == 0.0) {
            return $current == 0.0 ? null : ['percent' => 100, 'direction' => 'up'];
        }

        $percent = (int) round(($current - $previous) / $previous * 100);

        return ['percent' => abs($percent), 'direction' => $percent > 0 ? 'up' : ($percent < 0 ? 'down' : 'flat')];
    }
}
