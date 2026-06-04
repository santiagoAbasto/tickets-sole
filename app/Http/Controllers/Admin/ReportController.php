<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\ReportService;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ReportController extends Controller
{
    public function __construct(private ReportService $reports) {}

    public function index(Request $request): View
    {
        [$from, $to] = $this->range($request);

        return view('admin.reports.index', [
            'filters' => ['from' => $from->toDateString(), 'to' => $to->toDateString()],
            'report' => $this->reports->summary($from, $to),
        ]);
    }

    public function exportCsv(Request $request): StreamedResponse
    {
        [$from, $to] = $this->range($request);
        $rows = $this->reports->rows($from, $to);
        $filename = "tickets_{$from->toDateString()}_{$to->toDateString()}.csv";

        return response()->streamDownload(function () use ($rows) {
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM for Excel
            fputcsv($out, ['Código', 'Asunto', 'Cliente', 'Email', 'Categoría', 'Prioridad', 'Estado', 'Agente', 'Creado', 'Resuelto', 'Horas resolución']);

            foreach ($rows as $t) {
                fputcsv($out, [
                    $t->code,
                    $t->subject,
                    $t->customer?->name,
                    $t->customer?->email,
                    $t->category?->name,
                    $t->priority?->name,
                    $t->status?->name,
                    $t->assignee?->name ?? 'Sin asignar',
                    $t->created_at?->format('Y-m-d H:i'),
                    $t->resolved_at?->format('Y-m-d H:i'),
                    $t->resolutionHours(),
                ]);
            }
            fclose($out);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function exportPdf(Request $request)
    {
        [$from, $to] = $this->range($request);

        $pdf = Pdf::loadView('reports.pdf', [
            'from' => $from,
            'to' => $to,
            'report' => $this->reports->summary($from, $to),
            'generatedAt' => now(),
        ])->setPaper('a4');

        return $pdf->download("reporte_{$from->toDateString()}_{$to->toDateString()}.pdf");
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function range(Request $request): array
    {
        $to = $request->filled('to') ? Carbon::parse($request->get('to'))->endOfDay() : now()->endOfDay();
        $from = $request->filled('from') ? Carbon::parse($request->get('from'))->startOfDay() : now()->subDays(29)->startOfDay();

        if ($from->gt($to)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }
}
