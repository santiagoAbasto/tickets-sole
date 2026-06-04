<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Notifications\TicketSlaAlertNotification;
use App\Services\SlaEscalationService;
use App\Services\TicketNotificationService;
use Illuminate\Console\Command;

class CheckTicketSla extends Command
{
    protected $signature = 'tickets:check-sla {--hours=4 : Umbral en horas para "vence pronto"}';

    protected $description = 'Detecta tickets que vencen pronto o están vencidos: alerta al equipo y escala automáticamente los vencidos.';

    public function handle(TicketNotificationService $notifier, SlaEscalationService $escalator): int
    {
        $hours = (int) $this->option('hours');
        $dueSoon = 0;
        $overdue = 0;

        // 1) Due soon: open, due within the threshold, not yet alerted.
        Ticket::query()
            ->open()
            ->whereNull('due_soon_notified_at')
            ->whereNotNull('due_at')
            ->whereBetween('due_at', [now(), now()->addHours($hours)])
            ->with(['priority', 'status'])
            ->chunkById(100, function ($tickets) use ($notifier, &$dueSoon) {
                foreach ($tickets as $ticket) {
                    $notifier->slaAlert($ticket, TicketSlaAlertNotification::DUE_SOON);
                    $ticket->forceFill(['due_soon_notified_at' => now()])->save();
                    $dueSoon++;
                }
            });

        // 2) Overdue: open, past due, not yet alerted → alert + escalate once.
        Ticket::query()
            ->overdue()
            ->whereNull('overdue_notified_at')
            ->with(['priority', 'status'])
            ->chunkById(100, function ($tickets) use ($notifier, $escalator, &$overdue) {
                foreach ($tickets as $ticket) {
                    $notifier->slaAlert($ticket, TicketSlaAlertNotification::OVERDUE);
                    $escalator->escalate($ticket);
                    $ticket->forceFill(['overdue_notified_at' => now()])->save();
                    $overdue++;
                }
            });

        $this->info("SLA: {$dueSoon} por vencer, {$overdue} vencidos escalados.");

        return self::SUCCESS;
    }
}
