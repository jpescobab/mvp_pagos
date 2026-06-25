# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Qué es este proyecto

**CAPJ App Pagos** es una plataforma institucional de gestión, workflow, trazabilidad, expediente documental y reportabilidad. No reemplaza sistemas oficiales (SGF, CGU, BancoEstado, SII, CMF, Mercado Público): los complementa como capa de control, coordinación y evidencia.

El repositorio parte de `laravel/react-starter-kit` (auth Fortify + Inertia + React ya andando) y se construye encima siguiendo un harness y especificaciones OpenSpec obligatorios. **Hoy solo existe el scaffolding del starter kit** (auth, perfil, settings, dashboard vacío); todo el dominio institucional (CAPJ, workflow, SGF, pagos, indicadores económicos) descrito abajo está por implementarse según `tasks/`.

## Flujo de trabajo obligatorio (antes de tocar código)

1. Lee [HARNESS_IA.md](HARNESS_IA.md) — documento rector, no negociable.
2. Lee [openspec/project.md](openspec/project.md) y [openspec/principles.md](openspec/principles.md).
3. Lee el spec del dominio en `openspec/specs/<dominio>/spec.md` correspondiente a la tarea.
4. Lee la tarea correspondiente en `tasks/NN_*.md` (se implementan en orden numérico, 01 → 10).
5. Propón un plan breve antes de implementar.
6. Implementa migraciones, modelos, services, policies, resources, tests y documentación cuando corresponda.
7. Ejecuta validaciones (`composer test`, `npm run lint:check`, `npm run types:check`) y reporta cambios.

Dominios de specs disponibles en `openspec/specs/`: `core-institucional-capj`, `tablas-maestras-institucionales`, `seguridad-auditoria`, `indicadores-economicos-cmf-sii`, `workflow-core`, `documentos-expediente-variable`, `sgf-origen-snapshot`, `pago-proveedores-sgf`, `integraciones-api-browser-automation`, `reportabilidad-informes-razonados`. Cada uno corresponde 1:1 a una tarea en `tasks/`.

## CLI de OpenSpec y slash commands `/opsx:*`

El CLI `openspec` (paquete `@fission-ai/openspec`, instalado como devDependency) está inicializado en este repo solo para Claude Code (`openspec init --tools claude`). Agregó en `.claude/`:

- Skills: `openspec-propose`, `openspec-explore`, `openspec-apply-change`, `openspec-sync-specs`, `openspec-archive-change`.
- Slash commands: `/opsx:propose`, `/opsx:explore`, `/opsx:apply`, `/opsx:sync`, `/opsx:archive`.
- `openspec/changes/archive/`, donde se archivan los changes una vez completados.

Notas importantes:

- Los 10 `openspec/specs/*/spec.md` del harness son documentos libres, no el formato estructurado de requisitos que espera el CLI (`openspec list --specs` los reconoce por nombre pero muestra `requirements 0`). Siguen siendo la fuente de verdad; el CLI no los reescribe ni los gobierna.
- `openspec/project.md` y `openspec/principles.md` **no** se migraron a `openspec/config.yaml` (ese archivo no se creó porque el init corrió en modo no interactivo). Si más adelante se ejecuta `openspec config` para crearlo, mover el contenido relevante a su sección `context:` antes de considerar borrar `project.md` — nunca borrarlo antes de migrar el contenido.
- `/opsx:propose`, `/opsx:explore`, etc. sirven para cambios ad-hoc no contemplados todavía en `tasks/01..10`. Para el trabajo planificado del harness sigue rigiendo el flujo numerado de `tasks/`; `/opsx:*` no lo reemplaza ni lo reordena.

## Detenerse si

- Una instrucción contradice el harness.
- Se pretende usar SGF como workflow interno (sus estados/grupos no gobiernan nada interno).
- Se intenta eliminar trazabilidad, snapshots o auditoría.
- Se pide automatizar acciones sensibles (pagos, cierres, informes) sin aprobación humana.
- Se pide saltarse `WorkflowTransitionService` para cambiar un estado.
- Se pide usar Playwright para evadir MFA, CAPTCHA o controles de acceso.

## Reglas arquitectónicas críticas (de HARNESS_IA.md)

- **Jerarquía institucional fija**: `instituciones -> jurisdicciones -> cfinancieros -> ccostos`. Gobierna permisos, filtros, reportes y trazabilidad. Las tablas maestras usan `id` interno como PK y código institucional como `unique`.
- **SGF es origen, no gobierno**: SGF entrega evidencia (`sgf_id`, `sgf_status`, payloads crudos). El sistema propio gobierna workflow, estados, responsables y unidades internas — nunca se mezclan.
- **Snapshot obligatorio**: todo dato/documento recibido desde SGF o cualquier API externa relevante debe guardar payload original, fuente, fecha, hash, método de captura y usuario/job responsable, vinculado al caso.
- **Workflow antes que CRUD**: todo cambio de estado pasa exclusivamente por `WorkflowTransitionService::execute()`, que valida módulo activo, transición permitida, permisos, documentos obligatorios, y registra auditoría/notificación/historial. Prohibido cambiar estados desde controladores, jobs, seeders o componentes React.
- **Un `sgf_id` = un `supplier_payment_case` = un proceso workflow individual.** No crear `payment_submissions` ni lotes de envío.
- **Expediente documental variable**: los requisitos documentales dependen de módulo/proceso/modalidad/monto/estado y los entrega el backend. React solo renderiza el checklist recibido; nunca hardcodea requisitos.
- **API primero**: toda integración externa pasa por la capa transversal (`external_systems`, `external_api_requests`, `external_data_snapshots`, `integration_jobs`). Playwright solo como respaldo autorizado cuando no hay API suficiente, y nunca para evadir controles ni guardar credenciales en Git.
- **Informes razonados nacen de cortes y snapshots**, nunca de datos vivos cambiantes; siempre terminan con revisión humana antes de publicarse.
- **Core no desactivable** (auth, roles, estructura CAPJ, workflow, auditoría, documentos, indicadores, integraciones, reportabilidad) vs. **módulos funcionales activables** (Pago de Proveedores, Adquisiciones, Presupuesto, Mantenimiento, RR.HH., Consumo eléctrico, Servicios contratados, Informes razonados) que pueden desactivarse sin borrar datos.

Detalle completo de tablas, indicadores económicos (UF/USD/UTM/UTA/IPC) y reglas de importación: ver [HARNESS_IA.md](HARNESS_IA.md) secciones 5-14 y `docs/*.md`.

## Stack y convenciones Laravel

- Laravel 13 (PHP ^8.3), Inertia 3 + React 19, PostgreSQL en destino (el `.env` local actual usa `sqlite`), Spatie Laravel Permission, Sanctum si corresponde, Queue/Scheduler/Process, Laravel Fortify (auth), Laravel Boost (MCP de desarrollo), OpenSpec, Playwright cuando esté autorizado.
- Controladores livianos; Form Requests para validación; Services para lógica de negocio; Policies/Gates/Middleware para autorización; Jobs para procesos pesados; Events/Listeners para efectos secundarios; Resources/Collections para la API hacia React; transacciones en operaciones críticas.
- Migraciones limpias con índices, foreign keys y nombres consistentes. Prohibido lógica pesada en controladores o queries complejas en React.
- Rutas de auth/perfil/settings ya implementadas en `app/Http/Controllers/Settings/`, `app/Actions/Fortify/`, `app/Providers/FortifyServiceProvider.php` — usar como referencia de estilo, no como límite del dominio.
- Wayfinder genera funciones tipadas en `resources/js/actions/` y `resources/js/routes/` a partir de las rutas/controladores Laravel — regenerar (`php artisan wayfinder:generate` o vía build de Vite) en vez de hardcodear URLs en React.
- Alias de import en frontend: `@/*` → `resources/js/*` (ver `tsconfig.json`, `components.json`). Componentes shadcn/ui en `resources/js/components/ui`.

## Comandos

### Setup
```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
```

### Desarrollo (servidor + queue listener + Vite, en paralelo)
```bash
composer dev
```

### Lint / formato / tipos
```bash
composer lint            # Pint (PHP), aplica fixes
composer lint:check      # Pint --test, solo verifica
npm run lint             # ESLint (JS/TS), aplica fixes
npm run lint:check       # ESLint, solo verifica
npm run format           # Prettier sobre resources/
npm run format:check
npm run types:check      # tsc --noEmit (TS) — composer types:check corre PHPStan/Larastan (PHP)
```

### Tests
```bash
composer test            # config:clear + lint:check + types:check + php artisan test
php artisan test                          # toda la suite Pest
php artisan test --filter=NombreDelTest   # un test puntual
vendor/bin/pest tests/Feature/Settings/   # un directorio/archivo puntual
```

### Validación completa estilo CI
```bash
composer ci:check         # lint:check JS + format:check + types:check + test
```

### Build de producción
```bash
npm run build
npm run build:ssr   # incluye bundle SSR
```
