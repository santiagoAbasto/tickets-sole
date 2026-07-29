<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Nuevo ticket {{ $ticket->code }}</title>
</head>
<body style="margin:0;background:#f4f7fb;color:#162033;font-family:Arial,'Helvetica Neue',Helvetica,sans-serif;">
    <div style="display:none;max-height:0;overflow:hidden;opacity:0;">
        Se creó el ticket {{ $ticket->code }}: {{ $ticket->subject }}.
    </div>

    <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="background:#f4f7fb;padding:28px 12px;">
        <tr>
            <td align="center">
                <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="width:100%;max-width:640px;background:#ffffff;border:1px solid #dfe7f2;border-radius:18px;overflow:hidden;box-shadow:0 18px 45px rgba(22,32,51,0.08);">
                    <tr>
                        <td style="background:#0f172a;padding:26px 30px;">
                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0">
                                <tr>
                                    <td style="vertical-align:middle;">
                                        <table role="presentation" cellpadding="0" cellspacing="0">
                                            <tr>
                                                <td style="width:44px;vertical-align:middle;">
                                                    <img src="{{ $logoUrl }}" width="44" height="44" alt="Osole" style="display:block;border:0;border-radius:10px;">
                                                </td>
                                                <td style="padding-left:12px;vertical-align:middle;">
                                                    <div style="font-size:16px;font-weight:700;line-height:1.2;color:#ffffff;">Osole Soporte</div>
                                                    <div style="font-size:12px;line-height:1.4;color:#aab7cf;">Mesa de ayuda digital</div>
                                                </td>
                                            </tr>
                                        </table>
                                    </td>
                                    <td align="right" style="vertical-align:middle;">
                                        <span style="display:inline-block;border-radius:999px;background:#ecfeff;color:#075985;font-size:12px;font-weight:700;padding:7px 11px;">{{ $ticket->code }}</span>
                                    </td>
                                </tr>
                            </table>
                        </td>
                    </tr>

                    <tr>
                        <td style="padding:30px;">
                            <p style="margin:0 0 8px 0;color:#4f46e5;font-size:13px;font-weight:700;">Nuevo ticket recibido</p>
                            <h1 style="margin:0;color:#101827;font-size:24px;line-height:1.25;font-weight:800;">{{ $ticket->subject }}</h1>
                            <p style="margin:12px 0 0 0;color:#64748b;font-size:15px;line-height:1.65;">
                                Se registró una nueva consulta en Osole Tickets. Revisá el caso, asignalo si corresponde y respondé desde el panel interno.
                            </p>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:24px;border:1px solid #e2e8f0;border-radius:14px;overflow:hidden;">
                                <tr>
                                    <td style="background:#f8fafc;padding:14px 16px;color:#64748b;font-size:12px;font-weight:700;text-transform:uppercase;">Cliente</td>
                                </tr>
                                <tr>
                                    <td style="padding:16px;">
                                        <div style="font-size:16px;font-weight:700;color:#1e293b;">{{ $ticket->customer?->name ?? 'Cliente sin nombre' }}</div>
                                        <div style="margin-top:4px;font-size:14px;color:#64748b;">{{ $ticket->customer?->email ?? 'Sin email registrado' }}</div>
                                        @if ($ticket->customer?->phone)
                                            <div style="margin-top:4px;font-size:14px;color:#64748b;">{{ $ticket->customer->phone }}</div>
                                        @endif
                                    </td>
                                </tr>
                            </table>

                            <table role="presentation" width="100%" cellpadding="0" cellspacing="0" style="margin-top:18px;">
                                <tr>
                                    <td style="width:50%;padding:0 8px 10px 0;">
                                        <div style="border:1px solid #e2e8f0;border-radius:12px;padding:13px 14px;">
                                            <div style="font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Estado</div>
                                            <div style="margin-top:5px;font-size:14px;font-weight:700;color:#1e293b;">{{ $ticket->status?->name ?? 'Sin estado' }}</div>
                                        </div>
                                    </td>
                                    <td style="width:50%;padding:0 0 10px 8px;">
                                        <div style="border:1px solid #e2e8f0;border-radius:12px;padding:13px 14px;">
                                            <div style="font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Prioridad</div>
                                            <div style="margin-top:5px;font-size:14px;font-weight:700;color:#1e293b;">{{ $ticket->priority?->name ?? 'Sin prioridad' }}</div>
                                        </div>
                                    </td>
                                </tr>
                                <tr>
                                    <td style="width:50%;padding:0 8px 0 0;">
                                        <div style="border:1px solid #e2e8f0;border-radius:12px;padding:13px 14px;">
                                            <div style="font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Categoría</div>
                                            <div style="margin-top:5px;font-size:14px;font-weight:700;color:#1e293b;">{{ $ticket->category?->name ?? 'Sin categoría' }}</div>
                                        </div>
                                    </td>
                                    <td style="width:50%;padding:0 0 0 8px;">
                                        <div style="border:1px solid #e2e8f0;border-radius:12px;padding:13px 14px;">
                                            <div style="font-size:12px;font-weight:700;color:#94a3b8;text-transform:uppercase;">Agente</div>
                                            <div style="margin-top:5px;font-size:14px;font-weight:700;color:#1e293b;">{{ $ticket->assignee?->name ?? 'Sin asignar' }}</div>
                                        </div>
                                    </td>
                                </tr>
                            </table>

                            @if ($ticket->description)
                                <div style="margin-top:20px;border:1px solid #dbe3f0;background:#f8fafc;border-radius:12px;padding:16px;">
                                    <div style="font-size:12px;font-weight:700;color:#64748b;text-transform:uppercase;">Descripción</div>
                                    <div style="margin-top:8px;color:#334155;font-size:14px;line-height:1.6;white-space:pre-line;">{{ $ticket->description }}</div>
                                </div>
                            @endif

                            <div style="margin-top:28px;text-align:center;">
                                <a href="{{ $ticketUrl }}" style="display:inline-block;background:#4f46e5;color:#ffffff;text-decoration:none;font-size:15px;font-weight:800;border-radius:12px;padding:14px 22px;">Ver ticket en el panel</a>
                            </div>
                        </td>
                    </tr>

                    <tr>
                        <td style="background:#f8fafc;border-top:1px solid #e2e8f0;padding:18px 30px;text-align:center;color:#64748b;font-size:12px;line-height:1.5;">
                            Este correo fue enviado automáticamente por Osole Tickets. Si respondés este email, la respuesta irá al correo del cliente cuando esté disponible.
                        </td>
                    </tr>
                </table>
            </td>
        </tr>
    </table>
</body>
</html>
