<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { color: #1e293b; font-size: 12px; margin: 0; }
        .header { border-bottom: 2px solid #4f46e5; padding-bottom: 12px; margin-bottom: 18px; }
        .brand { color: #4f46e5; font-size: 18px; font-weight: bold; }
        .muted { color: #64748b; font-size: 11px; }
        h2 { font-size: 13px; color: #334155; margin: 18px 0 6px; }
        .kpis { width: 100%; border-collapse: collapse; margin-bottom: 6px; }
        .kpis td { width: 33%; padding: 10px; border: 1px solid #e2e8f0; }
        .kpi-val { font-size: 20px; font-weight: bold; color: #0f172a; }
        .kpi-lbl { color: #64748b; font-size: 10px; }
        table.data { width: 100%; border-collapse: collapse; margin-top: 4px; }
        table.data th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 10px; color: #475569; text-transform: uppercase; }
        table.data td { padding: 6px 8px; border-bottom: 1px solid #eef2f6; }
        .dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; margin-right: 5px; }
        .footer { margin-top: 24px; color: #94a3b8; font-size: 10px; text-align: center; }
    </style>
</head>
<body>
    <div class="header">
        <div class="brand">Osole Helpdesk · Reporte</div>
        <div class="muted">
            Período: {{ $from->isoFormat('DD MMM YYYY') }} — {{ $to->isoFormat('DD MMM YYYY') }} ·
            Generado el {{ $generatedAt->isoFormat('DD MMM YYYY HH:mm') }}
        </div>
    </div>

    <table class="kpis">
        <tr>
            <td><div class="kpi-val">{{ $report['kpis']['created'] }}</div><div class="kpi-lbl">Tickets creados</div></td>
            <td><div class="kpi-val">{{ $report['kpis']['resolved'] }}</div><div class="kpi-lbl">Resueltos</div></td>
            <td><div class="kpi-val">{{ $report['kpis']['resolution_rate'] }}%</div><div class="kpi-lbl">Tasa de resolución</div></td>
        </tr>
        <tr>
            <td><div class="kpi-val">{{ $report['kpis']['avg_first_response_hours'] ?? '—' }} h</div><div class="kpi-lbl">Tiempo 1ª respuesta (prom.)</div></td>
            <td><div class="kpi-val">{{ $report['kpis']['avg_resolution_hours'] ?? '—' }} h</div><div class="kpi-lbl">Tiempo de resolución (prom.)</div></td>
            <td><div class="kpi-val">{{ $report['kpis']['overdue'] }}</div><div class="kpi-lbl">Atrasados (actual)</div></td>
        </tr>
    </table>

    <h2>Por estado</h2>
    <table class="data">
        <thead><tr><th>Estado</th><th>Tickets</th></tr></thead>
        <tbody>
        @foreach ($report['by_status'] as $r)
            <tr><td><span class="dot" style="background: {{ $r['color'] }}"></span>{{ $r['name'] }}</td><td>{{ $r['value'] }}</td></tr>
        @endforeach
        </tbody>
    </table>

    <h2>Productividad por agente</h2>
    <table class="data">
        <thead><tr><th>Agente</th><th>Resueltos</th><th>Prom. resolución</th></tr></thead>
        <tbody>
        @foreach ($report['by_agent'] as $a)
            <tr><td>{{ $a['name'] }}</td><td>{{ $a['resolved'] }}</td><td>{{ $a['avg_resolution_hours'] ?? '—' }} h</td></tr>
        @endforeach
        </tbody>
    </table>

    <div class="footer">Osole.com.ar · Mesa de ayuda</div>
</body>
</html>
