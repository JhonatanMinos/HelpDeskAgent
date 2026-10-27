# HelpdeskAgent

HelpdeskAgent es una aplicación web multi-tenant para gestionar tiendas/sucursales, diagnósticos de red, proyectos y flujos tipo kanban. El proyecto combina Laravel en el backend con React + Inertia en el frontend para ofrecer una experiencia de una sola página sin perder el modelo tradicional de rutas y controladores de Laravel.

## Visión general

La aplicación está organizada alrededor de dos ejes principales:

- Operación de soporte y monitoreo de tiendas/branches.
- Gestión de proyectos y tareas dentro de cada equipo de trabajo.

El contexto de trabajo está vinculado a un equipo (`Team`), por lo que la mayor parte de las rutas y módulos están protegidos por middleware de autenticación y pertenencia al equipo.

## Arquitectura general

```text
Usuario
  └─ Navegador (React + Inertia + Tailwind)
       └─ Laravel Router / Middleware
            └─ Controllers
                 └─ Services / Repositories
                      └─ Eloquent Models
                           └─ Base de datos
                                └─ Jobs / Servicios externos
```

### 1. Frontend (React + Inertia)

El frontend vive en [resources/js](resources/js) y usa Inertia para renderizar páginas desde Laravel sin necesidad de construir un API REST separada.

Componentes principales:

- [resources/js/pages](resources/js/pages): páginas de negocio para dashboard, branches, projects, settings, teams y auth.
- [resources/js/components](resources/js/components): componentes reutilizables de UI.
- [resources/js/layouts](resources/js/layouts): estructura visual de la aplicación (app layout, auth layout, settings layout).
- [resources/js/hooks](resources/js/hooks), [resources/js/lib](resources/js/lib), [resources/js/types](resources/js/types): lógica compartida, utilidades y tipos TypeScript.

La entrada principal del frontend está definida en [resources/js/app.tsx](resources/js/app.tsx), donde se configura Inertia, los layouts y el proveedor de toasts.

### 2. Backend (Laravel)

El corazón del backend está en [app](app).

Capas principales:

- [app/Http/Controllers](app/Http/Controllers): controladores que reciben solicitudes HTTP y delegan al dominio.
- [app/Http/Middleware](app/Http/Middleware): middleware de seguridad y contexto de equipo, por ejemplo [app/Http/Middleware/EnsureTeamMembership.php](app/Http/Middleware/EnsureTeamMembership.php).
- [app/Services](app/Services): lógica de negocio más compleja.
- [app/Repositories](app/Repositories): acceso a datos y consultas específicas.
- [app/Models](app/Models): modelos Eloquent que representan el dominio.
- [app/Jobs](app/Jobs): tareas en segundo plano, como actualización de estatus de tiendas.
- [app/Providers](app/Providers): registro de servicios y configuración de la aplicación.

### 3. Rutas y contexto de equipo

Las rutas web están definidas en [routes/web.php](routes/web.php) y están agrupadas por el concepto de `current_team`.

Esto permite que la aplicación sea multi-tenant y que las operaciones estén siempre asociadas a un equipo activo. La mayoría de las vistas y acciones dependen de ese contexto.

## Dominios del negocio

### Equipos y membresías

- [app/Models/Team.php](app/Models/Team.php): representa un equipo de trabajo.
- [app/Models/Membership.php](app/Models/Membership.php): relación entre usuarios y equipos.
- [app/Models/TeamInvitation.php](app/Models/TeamInvitation.php): invitaciones para entrar a equipos.

### Tiendas / sucursales

- [app/Models/Store.php](app/Models/Store.php): almacena la información de cada sucursal.
- [app/Models/Device.php](app/Models/Device.php): dispositivos asociados a una tienda.
- [app/Models/PingLog.php](app/Models/PingLog.php): historial de monitoreo / diagnóstico.

El flujo de tiendas se apoya en:

- [app/Http/Controllers/StoreController.php](app/Http/Controllers/StoreController.php)
- [app/Services/StoreService.php](app/Services/StoreService.php)
- [app/Repositories/StoreRepository.php](app/Repositories/StoreRepository.php)

Este módulo es responsable de listar sucursales, mostrar dashboards y ejecutar diagnósticos de red.

### Proyectos y Kanban

- [app/Models/Project.php](app/Models/Project.php): proyecto de trabajo.
- [app/Models/BoardColumn.php](app/Models/BoardColumn.php): columnas del tablero.
- [app/Models/Task.php](app/Models/Task.php): tareas dentro del tablero.

El flujo de proyectos se gestiona con:

- [app/Http/Controllers/ProjectController.php](app/Http/Controllers/ProjectController.php)
- [app/Http/Controllers/KanbanController.php](app/Http/Controllers/KanbanController.php)

Permite crear proyectos, columnas y tareas, y mover tareas entre columnas con un comportamiento tipo kanban.

Los miembros autenticados del equipo pueden cambiar el estado de sus proyectos mediante
`PATCH /{current_team}/dev/projects/{project}/status`, enviando JSON como `{"status":"activo"}`.
Los valores permitidos son `propuesto`, `activo`, `pausado` y `completado`.
La acción actualiza únicamente el estado y redirige al detalle del proyecto con un mensaje de confirmación.
Un estado ausente o inválido devuelve `422`; un proyecto de otro equipo devuelve `404`,
sin modificar sus datos. No se agregan reglas de transición ni se modifican columnas o tareas.

### Integración con Telegram

Telegram está **deshabilitado por defecto** mediante `TELEGRAM_ENABLED=false`.
Se conserva el código, pero sus servicios de IA y escalamiento todavía están incompletos:

- [app/Services/TelegramService.php](app/Services/TelegramService.php)
- [app/Services/TelegramBotOrchestrator.php](app/Services/TelegramBotOrchestrator.php)
- [app/Http/Controllers/TelegramWebhookController.php](app/Http/Controllers/TelegramWebhookController.php)

Mientras esté deshabilitado, el webhook devuelve `404` antes de resolver el orquestador,
`TelegramService` no hace llamadas externas y `php artisan telegram:set-webhook`
termina con un mensaje de deshabilitación y código de salida `1`.

Las credenciales se leen desde `config/services.php` (`TELEGRAM_BOT_TOKEN` y
`TELEGRAM_WEBHOOK_SECRET`). No habilite Telegram hasta completar y verificar sus dependencias.
Al desplegar este cambio, actualice las cachés de configuración y rutas; en desarrollo:

```bash
php artisan config:clear
php artisan route:clear
```

Esto no borra conversaciones ni desregistra un webhook previamente configurado en Telegram:
no se realiza ninguna operación remota.

## Persistencia y base de datos

La capa de datos se basa en Eloquent y en migraciones ubicadas en [database/migrations](database/migrations).

Migraciones clave:

- `users`, `teams`, `stores`, `devices`, `projects`, `boards_columns`, `tasks`, `ping_logs`.
- El proyecto está preparado para trabajar con SQLite en desarrollo, aunque Laravel permite cambiar fácilmente el driver de base de datos.

## Flujo de una petición

1. El usuario navega a una ruta protegida.
2. El middleware valida autenticación y pertenencia al equipo.
3. El controlador recibe la solicitud.
4. El controlador usa un service o repository para implementar la lógica de negocio.
5. El modelo Eloquent interactúa con la base de datos.
6. La respuesta se devuelve a una página Inertia, que renderiza el componente React correspondiente.

## Estructura de carpetas principal

```text
app/
  Http/
    Controllers/
    Middleware/
    Requests/
  Models/
  Services/
  Repositories/
  Jobs/
  Providers/
config/
database/
resources/
  js/
    components/
    layouts/
    pages/
routes/
tests/
```

## Stack tecnológico

- Backend: PHP 8.3, Laravel 13, Eloquent, Fortify, Sanctum, Inertia Laravel.
- Frontend: React 19, TypeScript, Vite, Tailwind CSS 4, Radix UI.
- Calidad: Pint, Biome, PHPStan, Pest.

## Desarrollo rápido

Requisitos:

- PHP 8.3+
- Composer
- Bun o Node.js

Comandos típicos:

```bash
composer install
bun install
cp .env.example .env
php artisan key:generate
php artisan migrate
bun run dev
```

Para ejecutar pruebas:

```bash
php artisan test
```

## Resumen de diseño

La arquitectura actual favorece un enfoque pragmático y modular:

- Laravel como capa de aplicación y negocio.
- Inertia para conectar backend y frontend sin duplicar el modelo de rutas.
- Eloquent como capa de persistencia.
- Services y Repositories para separar lógica y consultas.
- Team-based multi-tenancy como eje transversal de la aplicación.
