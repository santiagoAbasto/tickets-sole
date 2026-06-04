# PRODUCT.md — Osole Helpdesk

register: product

## Product purpose
Mesa de ayuda / sistema de tickets premium para Osole (agencia de desarrollo web, hosting y soporte en Argentina). Reemplaza planillas y correo suelto por un panel operativo donde el equipo tría, responde y mide tickets. Pensado para integrarse al panel admin de osole.com.ar y, más adelante, venderse como SaaS (`tickets.osole.com.ar`).

## Users
- **Agente**: vive en la app durante toda la jornada. Necesita ver sus tickets, los atrasados, responder rápido y cambiar estados sin fricción. Densidad y velocidad importan más que decoración.
- **Admin / Coordinador**: supervisa la cola completa, asigna, mide productividad del equipo y vigila SLA.
- **Super Admin**: configura categorías, prioridades, estados y gestiona cuentas.
- **Cliente** (fase 2 portal): crea y sigue sus propios tickets.

## Tone
Profesional, claro, confiable. Un instrumento de trabajo que "desaparece" en la tarea. Nada de marketing dentro del producto. Microtoques de calidad (transiciones de estado, badges legibles, vacíos que enseñan) en momentos puntuales, no en cada pantalla.

## Strategic principles
- **Triage a primera vista**: lo urgente (atrasados, sin asignar, alta prioridad) salta sin tener que buscarlo.
- **El dato manda**: el color tiene significado (estado, prioridad, SLA), no es adorno.
- **Familiaridad ganada**: navegación estándar (sidebar + topbar), tablas densas, patrones que un usuario de Linear/Stripe/Intercom reconoce y confía.
- **Sin fugas**: un cliente nunca ve tickets ajenos ni notas internas.

## Anti-references
- Zendesk recargado y lento.
- Dashboards "hero-metric": número gigante + gradiente + tres stats de relleno.
- Grillas infinitas de tarjetas idénticas icono+título+texto.
- Animaciones largas y coreografiadas que hacen esperar al usuario.
