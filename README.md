# Osole Tickets

Sistema web de mesa de ayuda para registrar, asignar, responder y monitorear tickets de soporte. Incluye portal público, panel administrativo, seguimiento por código y email, notificaciones por correo, reportes, SLA y gestión de agentes.

Desarrollado por **ING. ABASTO ORTEGA SANTIAGO ALFREDO**.

## Stack

- PHP 8.3+
- Laravel 13
- MySQL o SQLite para desarrollo
- Tailwind CSS 4
- Laravel Reverb opcional para eventos en tiempo real
- DomPDF para reportes PDF
- Spatie Permission para roles y permisos

## Instalación Local

```bash
composer install
npm install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
npm run build:css
php artisan serve
```

La aplicación local queda disponible en:

```text
http://localhost:8000
```

## Variables De Entorno

El archivo `.env` contiene credenciales y no debe subirse a Git. Usar `.env.example` como plantilla.

Variables principales:

```env
APP_NAME="Osole Tickets"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tickets.osole.com.ar

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=
DB_USERNAME=
DB_PASSWORD=

MAIL_MAILER=smtp
MAIL_HOST=cp003.servidoresph.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=soporte@tickets.osole.com.ar
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="soporte@tickets.osole.com.ar"
MAIL_FROM_NAME="Osole Soporte"

QUEUE_CONNECTION=sync
WHATSAPP_COUNTRY=54
```

Para producción generar una clave propia:

```bash
php artisan key:generate
```

## Administrador Inicial

El seeder del Super Admin usa variables de entorno para evitar contraseñas hardcodeadas:

```env
SUPER_ADMIN_NAME="Nombre Apellido"
SUPER_ADMIN_EMAIL=admin@example.com
SUPER_ADMIN_PASSWORD=
```

Completar esos valores antes de ejecutar:

```bash
php artisan migrate --seed
```

## Producción En cPanel

El código Laravel puede vivir fuera de `public_html`, por ejemplo:

```text
/home/ticketsosolecom/TICKETS_OSOLE
```

El `public_html/index.php` debe apuntar a esa carpeta:

```php
$laravelPath = '/home/ticketsosolecom/TICKETS_OSOLE';
require $laravelPath.'/vendor/autoload.php';
$app = require_once $laravelPath.'/bootstrap/app.php';
```

Luego ejecutar:

```bash
composer install --no-dev --optimize-autoloader
php artisan migrate --force
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Si se usan adjuntos o avatares, crear el enlace público:

```bash
php artisan storage:link
```

En cPanel algunos hostings requieren crear manualmente el symlink:

```bash
ln -s /home/ticketsosolecom/TICKETS_OSOLE/storage/app/public /home/ticketsosolecom/public_html/storage
```

## Correo

Para recuperar contraseña y notificaciones, crear la cuenta en cPanel y usar SMTP:

```env
MAIL_MAILER=smtp
MAIL_HOST=cp003.servidoresph.com
MAIL_PORT=465
MAIL_SCHEME=smtps
MAIL_USERNAME=soporte@tickets.osole.com.ar
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="soporte@tickets.osole.com.ar"
```

Revisar en cPanel:

- **Track Delivery / Monitorizar el envío** para confirmar entrega.
- **Email Deliverability** para configurar SPF, DKIM y DMARC.
- Si DNS está en Cloudflare, copiar los TXT indicados por cPanel dentro de Cloudflare.

## Seguridad

- No subir `.env`, bases SQLite, logs, backups ni credenciales.
- No hardcodear contraseñas en seeders ni código.
- Cambiar `APP_KEY` y claves de producción por ambiente.
- Mantener `APP_DEBUG=false` en producción.
- Usar `QUEUE_CONNECTION=sync` en cPanel si no hay worker de cola.

## Comandos Útiles

```bash
php artisan optimize:clear
php artisan migrate --force
php artisan db:seed --force
npm run build:css
php artisan test
```

## Repositorio

Repositorio GitHub:

```text
https://github.com/santiagoAbasto/tickets-sole.git
```
