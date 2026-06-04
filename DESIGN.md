# DESIGN.md — Osole Helpdesk

Design system for the helpdesk. Tokens live in `resources/css/app.css` (Tailwind v4 `@theme`). React primitives live in `resources/js/Components/ui`.

## Register & strategy
- **Register**: product. The UI serves the task.
- **Theme**: light content surface, dark elegant sidebar (the app's frame). Long workdays, bright offices, dense tables → light reduces fatigue and reads trustworthy; the dark sidebar gives a confident premium frame.
- **Color strategy**: Restrained. Cool-tinted slate neutrals + a single indigo brand accent for primary actions / current selection. Semantic status & priority colors are data-viz with meaning, never decoration.

## Color tokens
Neutrals: Tailwind `slate` (cool, blue-tinted — already tinted toward the brand hue). Never pure `#000`/`#fff`.

| Role | Token | Value |
|---|---|---|
| Canvas (content bg) | `bg-canvas` | `#f8fafc` |
| Surface (cards/panels) | `bg-surface` | `#ffffff` |
| Sidebar | `bg-sidebar` | `#0b1220` |
| Sidebar hover | `bg-sidebar-hover` | `#16203a` |
| Brand (primary) | `brand-600` | `#4f46e5` (indigo) |
| Brand scale | `brand-50…900` | indigo ramp |

### Semantic (status / priority) — meaning only
- Abierto `#3b82f6` · En proceso `#6366f1` · Esperando cliente `#f59e0b` · Resuelto `#10b981` · Cerrado `#64748b` · Cancelado `#ef4444`
- Prioridad: Baja `#3b82f6` · Media `#f59e0b` · Alta `#f97316` · Urgente `#dc2626`
- Atrasado / SLA breach: `rose-600`
Colors come from the DB (each status/priority carries its `color`); the UI renders soft tinted chips (`bg`/`text`/`ring`), not full-saturation fills on inactive elements.

## Typography
- One family: **Inter** (loaded via bunny fonts). System sans fallback.
- Fixed rem scale, ratio ~1.2. Weights 400/500/600/700. Hierarchy via size + weight, not color.
- Data/tables can run dense; prose capped ~70ch.

## Elevation & shape
- Cards: `rounded-2xl border border-slate-200 bg-surface shadow-sm`. One elevation level; no nested cards.
- Inputs/buttons: `rounded-lg`. Chips/badges: `rounded-full`.
- Focus: visible `ring-2 ring-brand-500 ring-offset-2`. Never remove outlines without replacement.

## Motion
- 150–250ms, ease-out (`[0.22,1,0.36,1]`). Conveys state (enter, status change, panel open), not decoration.
- Dashboard first paint: subtle fade+slide with small stagger (≤60ms) as a single premium "moment". Everything respects `prefers-reduced-motion` (globally neutralized in app.css).
- Never animate layout props; transform/opacity only.

## Component vocabulary
Every interactive element ships default / hover / focus / active / disabled / loading / error. Loading = skeletons, not spinners in content. Empty states teach the next action. Shared primitives: `MetricCard`, `Badge` (`StatusBadge`/`PriorityBadge`), `Card`/`ChartCard`, `Button`, `EmptyState`, `Skeleton*`, `SlideOver`, `Modal`, `Tabs`, `Avatar`, `DataTable`. Reuse them; do not re-style ad hoc.
