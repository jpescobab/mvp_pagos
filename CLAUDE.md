# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Qué es este proyecto

**CAPJ App Pagos** es una plataforma institucional de gestión, workflow, trazabilidad, expediente documental y reportabilidad. No reemplaza sistemas oficiales (SGF, CGU, BancoEstado, SII, CMF, Mercado Público): los complementa como capa de control, coordinación y evidencia.

El repositorio parte de `laravel/react-starter-kit` (auth Fortify + Inertia + React ya andando) y se construye encima siguiendo un harness y especificaciones OpenSpec obligatorios. **Hoy solo existe el scaffolding del starter kit** (auth, perfil, settings, dashboard vacío); todo el dominio institucional (CAPJ, workflow, SGF, pagos, indicadores económicos) descrito abajo está por implementarse según `tasks/`.

## Flujo de trabajo obligatorio (antes de tocar código)

El harness (`HARNESS_IA.md`, `AGENTS.md`, este archivo) es el marco de reglas — no negociable. **La implementación de cada tarea se ejecuta a través de OpenSpec** (`/opsx:propose` → `/opsx:apply` → `/opsx:archive`), no escribiendo código a mano por fuera de ese ciclo.

1. Lee [HARNESS_IA.md](HARNESS_IA.md) — documento rector.
2. El contexto institucional (jerarquía CAPJ, SGF como origen, workflow antes que CRUD, snapshot obligatorio, stack, etc.) vive en la sección `context:` de [openspec/config.yaml](openspec/config.yaml) y se inyecta automáticamente en todo artefacto que genere OpenSpec (`proposal`, `design`, `tasks`, `specs`) — no hace falta leerlo a mano salvo para auditarlo.
3. Para cada tarea numerada en `tasks/NN_*.md` (orden 01 → 10), úsala como brief de `/opsx:propose "<contenido de la tarea>"`. Esto genera en `openspec/changes/<nombre>/` un `proposal.md`, `design.md`, una spec delta (formato estructurado OpenSpec) y un `tasks.md`.
4. Revisa el proposal/design/tasks generado — este es el punto de aprobación humana antes de implementar (equivalente a "proponer un plan breve").
5. `/opsx:apply` implementa según ese `tasks.md` (migraciones, modelos, services, policies, resources, tests, documentación).
6. Ejecuta validaciones (`composer test`, `npm run lint:check`, `npm run types:check`) y reporta cambios.
7. `/opsx:archive` fusiona la spec delta en `openspec/specs/<dominio>/spec.md` (queda en formato estructurado, validable con `openspec validate`) y archiva el change en `openspec/changes/archive/`.

Dominios de specs disponibles en `openspec/specs/`: `core-institucional-capj`, `tablas-maestras-institucionales`, `seguridad-auditoria`, `indicadores-economicos-cmf-sii`, `workflow-core`, `documentos-expediente-variable`, `sgf-origen-snapshot`, `pago-proveedores-sgf`, `integraciones-api-browser-automation`, `reportabilidad-informes-razonados`. Cada uno corresponde 1:1 a una tarea en `tasks/`. A medida que se archive el change de cada tarea, su `spec.md` pasa de prosa libre al formato estructurado del CLI.

## CLI de OpenSpec y slash commands `/opsx:*`

El CLI `openspec` (paquete `@fission-ai/openspec`, instalado como devDependency) está inicializado en este repo solo para Claude Code (`openspec init --tools claude`). Agregó en `.claude/`:

- Skills: `openspec-propose`, `openspec-explore`, `openspec-apply-change`, `openspec-sync-specs`, `openspec-archive-change`.
- Slash commands: `/opsx:propose`, `/opsx:explore`, `/opsx:apply`, `/opsx:sync`, `/opsx:archive`.
- `openspec/changes/archive/`, donde se archivan los changes una vez completados.

Notas importantes:

- Los `openspec/specs/*/spec.md` que todavía no pasaron por un change archivado siguen en prosa libre (no el formato estructurado que espera el CLI) — `openspec list --specs` los reconoce por nombre pero muestra `requirements 0` hasta que se archiven. Siguen siendo válidos como fuente de verdad mientras tanto.
- `openspec/project.md` y `openspec/principles.md` se retiraron — su contenido vive ahora en la sección `context:` de `openspec/config.yaml`.
- `/opsx:explore` además sirve para pensar/investigar antes de proponer, tanto para el trabajo numerado de `tasks/` como para cambios ad-hoc no contemplados en `tasks/01..10`.

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
