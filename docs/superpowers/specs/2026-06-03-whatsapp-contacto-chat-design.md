# Diseño — Contacto por WhatsApp + chat mejorado en el ticket

**Fecha:** 2026-06-03
**Estado:** ✅ Implementado (2026-06-03) — 17/17 tests verdes, verificado en navegador
**Superficie:** `resources/views/admin/tickets/show.blade.php` (vista interna del ticket, roles Super Admin / Admin / Agente)
**Registro de diseño:** product (la UI sirve a la tarea; familiaridad ganada; color restringido)

---

## 1. Objetivo y alcance

Dar al equipo interno una forma de **contactar al cliente por WhatsApp** desde la vista del ticket, sin perder el hilo. El agente envía con un clic un mensaje prearmado que incluye el **código del ticket**, el **email registrado** y el **enlace de seguimiento público** (`/seguimiento`), y la conversación puede continuar por WhatsApp o por la plataforma. De paso, se pule la **conversación/chat** de la vista.

### Enfoque técnico (decidido)

**Click-to-chat con enlaces `wa.me`.** Un `<a target="_blank">` abre WhatsApp (web o app) con el número del cliente y el texto ya cargado. **Sin** WhatsApp Business API, **sin** Meta, **sin** librerías no oficiales (Baileys/whatsapp-web.js), **sin** números baneables. Solo el stack actual: Blade + Tailwind v4 (CLI) + Alpine.js + Lucide. Sin paso de build nuevo.

### No-objetivos (YAGNI)

- No se reciben mensajes de WhatsApp dentro del sistema (no hay webhook ni API).
- No se sincroniza el historial real del chat de WhatsApp.
- No hay CRUD de plantillas en Ajustes (las plantillas viven en `config/whatsapp.php`, editables por dev; el agente las retoca antes de enviar).
- No multi-tenant / no envío masivo.

---

## 2. Decisiones tomadas con el usuario

1. **No perder el hilo →** cada envío registra **actividad** en el ticket (`whatsapp_contacted`) y, con un toggle, guarda el texto enviado como **nota interna**. La plataforma sigue siendo la fuente de verdad.
2. **Mensajes →** **plantillas editables** (3 por defecto), con variables, retocables antes de enviar.
3. **Alcance visual →** rediseñar el bloque de contacto (Email + WhatsApp) **y** pulir la conversación (burbujas, agrupación, separadores de día, compositor).

---

## 3. Diseño UX/UI (elevado con impeccable + ui-ux-pro-max)

El bloque vive en el panel lateral del `show`, sobre superficie clara (`bg-surface`), dentro de la `x-card` existente. Reemplaza el bloque actual "Cliente / Avisar al cliente" por **Cliente + Canales de contacto**.

### 3.1 Principios de craft aplicados

- **Color restringido, con significado.** El verde de WhatsApp se trata como un **token de canal con significado** (igual que los colores de estado/prioridad), no como decoración. Se usa **solo** en el glifo del canal y en el único botón primario "Abrir WhatsApp". Todo lo demás se mantiene slate/indigo. El resto de la jerarquía sigue siendo indigo brand.
- **Sin barras laterales de color** (`border-left`/`right` de color > 1px): prohibido. El canal de WhatsApp se distingue con un **glifo en chip tintado suave** (`bg`/`text`/`ring` verde), igual que los chips de estado del sistema, nunca con una franja.
- **Nada de modales.** Todo el compositor es **inline / progressive disclosure** en el panel. Los modales son la respuesta perezosa; acá no hacen falta.
- **Dos canales, no dos tarjetas idénticas.** Email y WhatsApp son dos filas de canal diferenciadas (no el patrón "grilla de tarjetas icono+título+texto"). Email es una acción secundaria sobria; WhatsApp es el canal con el compositor.
- **Divulgación progresiva.** Por defecto se ve: número + chips de plantilla + botón. La **vista previa editable** aparece al elegir plantilla (no satura de entrada).
- **Cifras tabulares** (`tabular-nums`) para el número de teléfono.
- **Copy sin em dash**, microcopy en español rioplatense, conciso.

### 3.2 Anatomía del bloque (panel lateral)

```
┌─ Cliente ─────────────────────────────────────┐
│  (avatar)  Juan Pérez                          │
│            ✉ juan@acme.com · 🏢 Acme            │
├─ Canales de contacto ─────────────────────────┤
│                                                │
│  ✉  Email                    [ Avisar ]        │  ← acción secundaria, sobria
│      Manda el código + link de seguimiento     │
│                                                │
│  (●)  WhatsApp                                  │  ← glifo verde en chip tintado
│       +54 9 11 1234-5678   ✎ vincular           │     número en tabular-nums
│       ┌ Plantilla ──────────────────────────┐  │
│       │ (Identificación)(Seguimiento)(Resuelto)│ ← chips tipo radio
│       └─────────────────────────────────────┘  │
│       ┌ Mensaje (editable) ─ aparece al elegir ┐│  ← progressive disclosure
│       │ Hola Juan 👋 Te escribimos de Osole por ││
│       │ tu solicitud OSL-1042. Seguí tu caso en ││
│       │ osole.com/seguimiento con tu código y…  ││
│       └─────────────────────────────────────┘  │
│       ☑ Guardar copia como nota interna         │
│       [  ◍  Abrir WhatsApp  ]                    │  ← único botón primario, verde
└────────────────────────────────────────────────┘
```

**Sin número cargado:** el sub-bloque de WhatsApp muestra solo una CTA discreta "Vincular WhatsApp" que abre el input inline; los chips/botón aparecen recién cuando hay número válido.

### 3.3 Color y contraste (valores)

- **Glifo de canal:** WhatsApp brand `#25D366` sobre chip `bg-emerald-50` + `ring-emerald-100` (glifo = ícono, requiere ≥3:1, cumple). Patrón idéntico a los chips tintados del sistema.
- **Botón "Abrir WhatsApp":** verde **profundo** para que el texto blanco pase AA (≥4.5:1). El verde vivo `#25D366` con texto blanco **no** pasa (~1.5:1), así que el fill del botón usa un verde profundo tipo `emerald-700 #047857` / `green-700 #15803d` (texto blanco ≈ 4.5–5:1). Se valida el contraste real al implementar y se ajusta el tono si hace falta. El glifo dentro del botón puede ir en blanco.
- **Focus ring:** se mantiene `ring-2 ring-brand-500 ring-offset-2` (consistencia con el resto del sistema), no un ring verde ad hoc.
- Tokens OKLCH-tinted ya definidos en `app.css`; no se introducen neutros puros.

### 3.4 Motion

- Aparición de la vista previa y del input de vincular: `x-transition` **opacity + translate-y-1**, ~200ms, ease-out `[0.22,1,0.36,1]`. **Nunca** animar `height`/layout (transform/opacity únicamente).
- Confirmación de envío: micro-feedback de 150–200ms (cambio de estado del botón a "Registrado ✓"), no spinner largo.
- Respeta `prefers-reduced-motion` (ya neutralizado globalmente en `app.css`).

### 3.5 Rediseño de la conversación (chat)

En `resources/views/components/ticket/message.blade.php` y el contenedor de `show.blade.php`:

- **Agrupación de mensajes consecutivos** del mismo autor (mismo `author_type` + autor, dentro de ~5 min): se oculta avatar/nombre repetido; las burbujas se juntan visualmente.
- **Separadores de día** en la línea de tiempo: "Hoy", "Ayer", o fecha (`d M Y`), calculados en el bloque `@php` que arma `$timeline`.
- **Burbujas pulidas:** sombra sutil, cola refinada (ya existe `rounded-tl-sm`/`rounded-tr-sm`), mejor ritmo de espaciado. Se mantiene cliente a la izquierda (blanco, ring slate), agente a la derecha (brand-600).
- **Badge sutil de canal** en notas originadas por WhatsApp (la nota guardada lleva un prefijo/ícono que la identifica; reutiliza el estilo de nota interna ámbar existente).
- **Compositor:** se conserva el patrón de pestañas Responder / Nota interna; se pule el toolbar (alineación, estados de foco) y se mantiene el aviso "saldrá como {agente}".
- **Loading de mensajes previos (si aplica):** skeleton, no spinner en el contenido (regla del sistema).

---

## 4. Backend

### 4.1 Config — `config/whatsapp.php` (nuevo)

```php
return [
    'default_country' => env('WHATSAPP_COUNTRY', '54'), // Argentina
    'templates' => [
        [
            'key'   => 'identificacion',
            'label' => 'Identificación',
            'icon'  => 'badge-check', // Lucide
            'text'  => "Hola {cliente} 👋 Te escribimos de {empresa} por tu solicitud *{codigo}*.\n".
                       "Para seguir tu caso entrá a {link} con tu código y tu email registrado ({email}).\n".
                       "También podés responder por acá. ¡Quedamos en contacto!",
        ],
        [
            'key'   => 'seguimiento',
            'label' => 'Seguimiento',
            'icon'  => 'message-circle',
            'text'  => "Hola {cliente}, novedades sobre tu ticket *{codigo}*:\n\n".
                       "Cualquier cosa respondé por acá o seguilo en {link}.",
        ],
        [
            'key'   => 'resuelto',
            'label' => 'Resuelto',
            'icon'  => 'circle-check-big',
            'text'  => "Hola {cliente}, marcamos tu ticket *{codigo}* como resuelto ✅\n".
                       "Si necesitás algo más, respondé por acá o entrá a {link}. ¡Gracias!",
        ],
    ],
];
```

> Nota: los emojis (👋 ✅) son **contenido del mensaje** que se envía al cliente por WhatsApp (natural y amistoso en ese canal), **no** iconografía de interfaz. La UI usa solo iconos Lucide/SVG.

### 4.2 Helper — `app/Support/Whatsapp.php` (nuevo)

- `normalize(?string $raw, ?string $defaultCc = null): ?string`
  - Quita todo lo no numérico; resuelve `+`, prefijo internacional `00`.
  - Lógica Argentina: saca el `0` troncal y el `15` de celular; antepone `default_country` (`54`) si falta; asegura el `9` de celular tras el `54`.
  - Devuelve dígitos E.164 **sin** `+` (formato `wa.me`), o `null` si es demasiado corto/ inválido.
- `link(string $normalized, string $text): string` → `"https://wa.me/{$normalized}?text=".rawurlencode($text)`.

El número resuelto se muestra y es **editable** en la UI (red de seguridad si la normalización falla en algún caso borde).

### 4.3 Servicio — `app/Services/WhatsappTemplateService.php` (nuevo)

`resolve(Ticket $ticket, User $agent): array` devuelve, para el payload de la vista:
- `phone` (crudo), `phone_normalized`, `wa_base` (`https://wa.me/<normalized>` o `null`), `has_phone` (bool), `track_url` (URL absoluta de `route('public.track.form')`).
- `templates`: cada plantilla de config con variables **ya sustituidas**:
  - `{cliente}` = primer nombre del customer (fallback "" → saludo "Hola 👋").
  - `{codigo}` = `ticket.code`
  - `{email}` = `customer.email` o `"(sin email registrado)"`
  - `{link}` = `track_url`
  - `{empresa}` = `config('app.name')`
  - `{agente}` = `agent.name`

Mantener este servicio aparte deja liviano al ya-grande `Admin/TicketController`.

### 4.4 Logger — `TicketActivityLoggerService` (editado)

Agregar:
```php
public function whatsappContacted(Ticket $ticket, ?User $actor = null): void
{
    $this->log($ticket, 'whatsapp_contacted', 'Contactado por WhatsApp', [], $actor);
}
```
Y en `show.blade.php`, mapa de iconos: `'whatsapp_contacted' => 'message-circle'`.

### 4.5 Controlador — `app/Http/Controllers/Admin/TicketWhatsappController.php` (nuevo)

- `updateNumber(UpdateWhatsappNumberRequest, Ticket): JsonResponse`
  - `authorize('notifyCustomer', $ticket)`.
  - Valida `phone` (string, requerido). Normaliza con `Whatsapp::normalize`; si da `null` → 422 con mensaje "Número no válido, incluí el código de país".
  - Persiste el `phone` (crudo) en `ticket.customer`. Devuelve `{ phone, phone_normalized, wa_base }`.
- `log(LogWhatsappRequest, Ticket): JsonResponse`
  - `authorize('notifyCustomer', $ticket)`.
  - Valida `{ body: required string, template_key: nullable string, save_note: boolean }`.
  - `logger->whatsappContacted($ticket, $request->user())`.
  - Si `save_note` → crea `TicketNote` (autor = agente) con body `"📱 Enviado por WhatsApp:\n\n{body}"`.
  - `ticket->last_activity_at = now()`; guarda. Devuelve `{ ok: true }`.

> **Verificar al implementar:** que `TicketPolicy::notifyCustomer` autorice a **Agente** (hoy gobierna el botón "Avisar al cliente"). Si solo permite Admin/Super Admin, ajustar la policy o usar la ability `reply` para gobernar el bloque de WhatsApp.

### 4.6 Rutas — `routes/web.php` (editado)

Dentro del grupo `admin` → `tickets` (name prefix `admin.tickets.`):
```php
Route::put('{ticket}/whatsapp-number', [TicketWhatsappController::class, 'updateNumber'])->name('whatsapp.number');
Route::post('{ticket}/whatsapp/log',  [TicketWhatsappController::class, 'log'])->name('whatsapp.log');
```

### 4.7 Payload de la vista — `Admin/TicketController@show` (editado)

Inyectar `'whatsapp' => app(WhatsappTemplateService::class)->resolve($ticket, $request->user())` en el array de la vista (gateado por `can.notifyCustomer`). `customer.phone` ya está en `transformDetail`.

---

## 5. Frontend (Blade + Alpine)

- **`resources/views/components/icon/whatsapp.blade.php` (nuevo):** SVG inline del glifo de WhatsApp, tamaño por clase (`{{ $attributes }}`).
- **`resources/views/components/ticket/whatsapp-panel.blade.php` (nuevo):** todo el sub-bloque WhatsApp con `x-data` inline:
  - Estado: `{ template, body, saveNote, editingNumber, phoneInput, finalNumber, waBase, sent }`.
  - `pick(key)`: setea `body` con el texto resuelto de la plantilla y revela la vista previa.
  - `waUrl()`: `waBase + '?text=' + encodeURIComponent(body)`.
  - `saveNumber()`: `fetch PUT whatsapp.number` (CSRF) → actualiza `finalNumber`/`waBase`; muestra error inline si 422.
  - `send()`: `window.open(waUrl(), '_blank')` **y** `fetch POST whatsapp.log` con `{ body, template_key, save_note }`; al ok → micro-feedback "Registrado ✓" y, si `save_note`, refresco suave de la línea de tiempo (o `location.reload()` simple si se prefiere mínimo).
  - El botón es un `<a :href="waUrl()" target="_blank" @click="send()">` real → funciona aunque el `fetch` falle o JS esté limitado; el registro es best-effort y no bloquea.
- **`show.blade.php` (editado):** reemplaza el bloque Cliente/Avisar por Cliente + Canales de contacto (incluye `<x-ticket.whatsapp-panel :whatsapp="$ticket['whatsapp']" />`); agrega separadores de día y flags de agrupación al armar `$timeline`; agrega el icono de actividad `whatsapp_contacted`.
- **`message.blade.php` (editado):** soporta `is_grouped` (oculta avatar/nombre), pule burbujas.
- CSRF: usar el `<meta name="csrf-token">`/token existente que ya consume el script de chat en tiempo real.

---

## 6. Accesibilidad

- Botón WhatsApp: `<a>` real, `aria-label="Abrir WhatsApp con el cliente"`, alto ≥44px (`min-h-10`).
- Toggle "Guardar copia como nota": `<input type="checkbox">` con `<label>` asociado.
- Input de número: `type="tel"`, `inputmode="tel"`, `autocomplete="tel"`; error con `aria-live`/`role="alert"` debajo del campo.
- Chips de plantilla: grupo tipo radio, navegable por teclado, estado activo visible (no solo color).
- Color nunca como único indicador: el canal lleva ícono + texto "WhatsApp"; la nota de WhatsApp lleva ícono + prefijo textual.
- Contraste verificado (botón ≥4.5:1, glifo ≥3:1). Focus visible en todos los controles.

---

## 7. Errores y bordes

- **Sin teléfono:** solo CTA "Vincular WhatsApp"; chips/botón ocultos hasta cargar número válido.
- **Número inválido:** 422 → mensaje inline; no se persiste.
- **Sin email:** `{email}` → "(sin email registrado)"; la plantilla Identificación sigue usable.
- **Sin nombre de cliente:** `{cliente}` vacío → saludo degrada a "Hola 👋".
- **Popup bloqueado:** al ser `<a target="_blank">` con click directo, el navegador lo permite; el `fetch` de log corre igual.
- **Fallo del fetch de log:** no bloquea abrir WhatsApp; muestra aviso suave ("No se pudo registrar la actividad").

---

## 8. Testing

**Unit**
- `WhatsappTest::normalize()` — casos AR: `"11 1234-5678"` + cc 54 → `5491112345678`; `"+54 9 11 1234 5678"`; `"0351 15 678-1234"`; internacional `"00598 99 123 456"`; basura/corto → `null`.
- `Whatsapp::link()` — codifica el texto (espacios, saltos, `*`, emojis) correctamente.
- `WhatsappTemplateServiceTest` — sustitución de variables y fallback de email.

**Feature**
- `updateNumber` actualiza `customer.phone`, valida (rechaza inválido con 422), autoriza (Agente permitido, Cliente prohibido).
- `log` crea entrada de actividad `whatsapp_contacted`; con `save_note=true` crea un `TicketNote`; sin él, no crea nota; respeta la policy.

---

## 9. Archivos

**Nuevos**
- `config/whatsapp.php`
- `app/Support/Whatsapp.php`
- `app/Services/WhatsappTemplateService.php`
- `app/Http/Controllers/Admin/TicketWhatsappController.php`
- `app/Http/Requests/UpdateWhatsappNumberRequest.php`
- `app/Http/Requests/LogWhatsappRequest.php`
- `resources/views/components/icon/whatsapp.blade.php`
- `resources/views/components/ticket/whatsapp-panel.blade.php`
- `tests/Unit/WhatsappTest.php`, `tests/Unit/WhatsappTemplateServiceTest.php`
- `tests/Feature/TicketWhatsappTest.php`

**Editados**
- `app/Services/TicketActivityLoggerService.php` (+`whatsappContacted`)
- `routes/web.php` (2 rutas)
- `app/Http/Controllers/Admin/TicketController.php` (`@show` payload `whatsapp`)
- `resources/views/admin/tickets/show.blade.php` (bloque contacto + separadores/agrupación + icono actividad)
- `resources/views/components/ticket/message.blade.php` (agrupación + pulido burbujas)
- `.env.example` (+`WHATSAPP_COUNTRY=54`)

---

## 10. Verificaciones previas a implementar

1. Confirmar que `TicketPolicy::notifyCustomer` autoriza a Agente (si no, ajustar gobierno del bloque).
2. Confirmar el mecanismo de CSRF/flash que usa hoy el script de chat en tiempo real para reutilizarlo en los `fetch`.
3. Confirmar `route('public.track.form')` como destino de `{link}` (verificado: existe en `routes/web.php`).
