# Deploy a cPanel — Osole Helpdesk (versión Blade, sin build en el servidor)

La interfaz ahora es 100% **Blade + Alpine.js**. El servidor **no necesita Node**: el CSS de Tailwind se compila una vez en tu máquina a un archivo estático, y Alpine/Chart.js/Lucide se cargan por CDN. cPanel solo corre PHP + MySQL.

---

## 1. En tu máquina (una vez, antes de subir)

```bash
# 1. Dependencias PHP de producción (se sube la carpeta vendor/)
composer install --no-dev --optimize-autoloader

# 2. Compilar el CSS estático -> public/css/app.css
npm install            # solo para tener tailwindcss localmente
npm run build:css
```

> `public/css/app.css` queda compilado. **Subí ese archivo.** No necesitás `npm` en cPanel.

No hace falta subir: `node_modules/`, `resources/js/` (React viejo), `public/build/` (React viejo). El front Blade no los usa.

---

## 2. Subir a cPanel

### Estructura (app separado de la web — más seguro)

```
/home/ticketsosolecom/
├── Sistema-Tickets/   ← Laravel completo MENOS public/  (app, bootstrap, config, database,
│                         resources, routes, storage, vendor, artisan, composer.json, .env)
└── public_html/       ← SOLO el contenido de public/  (index.php, .htaccess, css/, img/,
                          favicon/, favicon.ico, robots.txt)
```

> El **`.env` va en `Sistema-Tickets/`** (NUNCA en `public_html` — no debe ser accesible por web).
> Activá **"Mostrar archivos ocultos (dotfiles)"** en *Configuración* del File Manager para ver/subir `.htaccess` y `.env`.

### Editar `public_html/index.php`

Como `public_html` y `Sistema-Tickets` son carpetas **hermanas**, cambiá las **3** rutas
`__DIR__.'/../...'` por `__DIR__.'/../Sistema-Tickets/...'`:

```php
if (file_exists($maintenance = __DIR__.'/../Sistema-Tickets/storage/framework/maintenance.php')) {
    require $maintenance;
}

require __DIR__.'/../Sistema-Tickets/vendor/autoload.php';

/** @var Application $app */
$app = require_once __DIR__.'/../Sistema-Tickets/bootstrap/app.php';

$app->handleRequest(Request::capture());
```

---

## 3. Configurar `.env` en el servidor

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tickets.osole.com.ar
APP_KEY=            # php artisan key:generate (o copialo)

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_DATABASE=tu_base
DB_USERNAME=tu_user
DB_PASSWORD=tu_pass

# Mails/notifs se envían en el mismo request → NO hace falta worker de cola
QUEUE_CONNECTION=sync

# Email (SMTP de cPanel) — ver la sección "Email" más abajo para el paso a paso
MAIL_MAILER=smtp
MAIL_HOST=cp003.servidoresph.com      # host SMTP que muestra cPanel
MAIL_PORT=465                         # 465=SSL (MAIL_SCHEME=smtps) · 587=TLS (MAIL_SCHEME=smtp)
MAIL_SCHEME=smtps
MAIL_USERNAME=soporte@tickets.osole.com.ar
MAIL_PASSWORD=
MAIL_FROM_ADDRESS="soporte@tickets.osole.com.ar"
MAIL_FROM_NAME="Osole Soporte"

SUPER_ADMIN_NAME="Nombre Apellido"
SUPER_ADMIN_EMAIL=admin@example.com
SUPER_ADMIN_PASSWORD=

BROADCAST_CONNECTION=log   # ver nota de "tiempo real" abajo
```

---

## 4. Inicializar (cPanel → "Terminal")

```bash
cd /home/ticketsosolecom/TICKETS_OSOLE

php artisan key:generate
php artisan migrate --force --seed     # 1ra vez: crea catálogos + el Super Admin configurado en .env

# Symlink de storage → public_html para adjuntos y avatares.
# storage:link NO sirve acá porque el public está separado; lo creamos a mano:
rm -f /home/ticketsosolecom/public_html/storage
ln -s /home/ticketsosolecom/TICKETS_OSOLE/storage/app/public /home/ticketsosolecom/public_html/storage

php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Permisos de escritura: `storage/` y `bootstrap/cache/` (755/775).

> **Sin SSH:** muchos cPanel tienen "Terminal". Si no, corré estos comandos con la herramienta "Cron Jobs" (un cron de una sola vez) o pedímelos como ruta temporal protegida.

---

## 5. Cron Jobs (en cPanel → "Trabajos Cron")

```bash
# Scheduler de Laravel (dispara tickets:check-sla cada 15 min, etc.) — cada minuto
* * * * * cd /home/USUARIO/ruta-al-proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Si usás `QUEUE_CONNECTION=database` en vez de `sync`, agregá también:
```bash
* * * * * cd /home/USUARIO/ruta-al-proyecto && php artisan queue:work --stop-when-empty >> /dev/null 2>&1
```

---

## 📧 Email — "Avisar al cliente" y "Recuperar contraseña"

La app **envía los correos en el mismo request** (no necesita worker de cola). Con
configurar el SMTP en el `.env` ya funcionan los dos flujos:
- **"Avisar al cliente"** → manda el código + el link de seguimiento por email.
- **"Olvidé mi contraseña"** (en el login) → manda el link de recuperación.

**Paso a paso en cPanel:**

1. **cPanel → "Cuentas de correo" → Crear.** Creá una casilla, ej. `soporte@osole.com.ar`, con su contraseña.
2. En esa cuenta entrá a **"Conectar dispositivos"** (o "Configurar cliente de correo") y mirá los datos del **servidor SALIENTE (SMTP)**:
   - **Servidor (host):** normalmente `mail.tu-dominio.com.ar` (lo muestra cPanel).
   - **Puerto:** `465` (SSL) o `587` (TLS).
   - **Usuario:** el correo completo (`soporte@tickets.osole.com.ar`).
   - **Contraseña:** la de esa casilla.
3. Poné esos datos en el **`.env`** del servidor (ejemplo con puerto 465):
   ```env
   MAIL_MAILER=smtp
   MAIL_HOST=cp003.servidoresph.com
   MAIL_PORT=465
   MAIL_SCHEME=smtps
   MAIL_USERNAME=soporte@tickets.osole.com.ar
   MAIL_PASSWORD=
   MAIL_FROM_ADDRESS="soporte@tickets.osole.com.ar"
   MAIL_FROM_NAME="Osole Soporte"
   ```
   > Con puerto **587** usá `MAIL_PORT=587` y `MAIL_SCHEME=smtp`.
   > `MAIL_FROM_ADDRESS` **tiene que ser una casilla de tu dominio** (si no, el correo cae en spam o lo rechaza el servidor).
4. **`APP_URL` = tu dominio real con https** (ej. `https://tickets.osole.com.ar`). El link de "recuperar contraseña" se arma con esa URL.
5. Si tenés la config cacheada, refrescala:
   ```bash
   php artisan config:clear && php artisan config:cache
   ```
6. **Probar:** en el login → **"Olvidé mi contraseña"** → email de un usuario real → te debe llegar el correo. Y desde un ticket, tocá **"Avisar al cliente"**.

> Si no llega: revisá host/puerto/usuario/contraseña, que el `MAIL_FROM_ADDRESS` sea del dominio, y mirá `storage/logs/laravel.log` para ver el error de SMTP. En desarrollo, `MAIL_MAILER=log` escribe los correos en ese mismo log sin enviarlos.

---

## 6. Tiempo real (WebSockets)

En **hosting compartido no corre Reverb** (necesita un proceso y puerto propios). Opciones:

- **Sin tiempo real (recomendado para cPanel):** dejá `BROADCAST_CONNECTION=log`. La app funciona completa; las conversaciones se ven al refrescar. La versión Blade ya está pensada así (no incluye los listeners de WebSocket que tenía React).
- **Con tiempo real:** contratá **Pusher** (hosted) o un VPS para Reverb, seteá `BROADCAST_CONNECTION=pusher` + claves, y agregá Echo por CDN a los layouts. Te lo dejo armado si lo necesitás.

---

## 7. Actualizaciones futuras

Cada vez que cambies **estilos** (clases nuevas en Blade), regenerá el CSS local y subí `public/css/app.css`:
```bash
npm run build:css
```
Cambios de PHP/Blade: subí los archivos y corré `php artisan optimize:clear` (o `view:cache` de nuevo).

---

## Cuenta inicial (tras `--seed`)

La cuenta inicial se define en el `.env` antes de correr migraciones:

```env
SUPER_ADMIN_NAME="Nombre Apellido"
SUPER_ADMIN_EMAIL=admin@example.com
SUPER_ADMIN_PASSWORD=
```

> Cambiá esta contraseña al entrar. Desde **Usuarios** creás el resto del equipo
> (programadores = *Agente*, diseñadoras industriales = *Diseñadora industrial*, y Admins).
