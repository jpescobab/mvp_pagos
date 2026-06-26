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

===

<laravel-boost-guidelines>
=== foundation rules ===

# Laravel Boost Guidelines

The Laravel Boost guidelines are specifically curated by Laravel maintainers for this application. These guidelines should be followed closely to ensure the best experience when building Laravel applications.

## Foundational Context

This application is a Laravel application and its main Laravel ecosystems package & versions are below. You are an expert with them all. Ensure you abide by these specific packages & versions.

- php - 8.5
- inertiajs/inertia-laravel (INERTIA_LARAVEL) - v3
- laravel/fortify (FORTIFY) - v1
- laravel/framework (LARAVEL) - v13
- laravel/prompts (PROMPTS) - v0
- laravel/wayfinder (WAYFINDER) - v0
- larastan/larastan (LARASTAN) - v3
- laravel/boost (BOOST) - v2
- laravel/mcp (MCP) - v0
- laravel/pail (PAIL) - v1
- laravel/pint (PINT) - v1
- laravel/sail (SAIL) - v1
- pestphp/pest (PEST) - v4
- phpunit/phpunit (PHPUNIT) - v12
- @inertiajs/react (INERTIA_REACT) - v3
- react (REACT) - v19
- tailwindcss (TAILWINDCSS) - v4
- @laravel/vite-plugin-wayfinder (WAYFINDER_VITE) - v0
- eslint (ESLINT) - v9
- prettier (PRETTIER) - v3

## Skills Activation

This project has domain-specific skills available in `**/skills/**`. You MUST activate the relevant skill whenever you work in that domain—don't wait until you're stuck.

## Conventions

- You must follow all existing code conventions used in this application. When creating or editing a file, check sibling files for the correct structure, approach, and naming.
- Use descriptive names for variables and methods. For example, `isRegisteredForDiscounts`, not `discount()`.
- Check for existing components to reuse before writing a new one.

## Verification Scripts

- Do not create verification scripts or tinker when tests cover that functionality and prove they work. Unit and feature tests are more important.

## Application Structure & Architecture

- Stick to existing directory structure; don't create new base folders without approval.
- Do not change the application's dependencies without approval.

## Frontend Bundling

- If the user doesn't see a frontend change reflected in the UI, it could mean they need to run `npm run build`, `npm run dev`, or `composer run dev`. Ask them.

## Documentation Files

- You must only create documentation files if explicitly requested by the user.

## Replies

- Be concise in your explanations - focus on what's important rather than explaining obvious details.

=== boost rules ===

# Laravel Boost

## Tools

- Laravel Boost is an MCP server with tools designed specifically for this application. Prefer Boost tools over manual alternatives like shell commands or file reads.
- Use `database-query` to run read-only queries against the database instead of writing raw SQL in tinker.
- Use `database-schema` to inspect table structure before writing migrations or models.
- Use `get-absolute-url` to resolve the correct scheme, domain, and port for project URLs. Always use this before sharing a URL with the user.
- Use `browser-logs` to read browser logs, errors, and exceptions. Only recent logs are useful, ignore old entries.

## Searching Documentation (IMPORTANT)

- Always use `search-docs` before making code changes. Do not skip this step. It returns version-specific docs based on installed packages automatically.
- Pass a `packages` array to scope results when you know which packages are relevant.
- Use multiple broad, topic-based queries: `['rate limiting', 'routing rate limiting', 'routing']`. Expect the most relevant results first.
- Do not add package names to queries because package info is already shared. Use `test resource table`, not `filament 4 test resource table`.

### Search Syntax

1. Use words for auto-stemmed AND logic: `rate limit` matches both "rate" AND "limit".
2. Use `"quoted phrases"` for exact position matching: `"infinite scroll"` requires adjacent words in order.
3. Combine words and phrases for mixed queries: `middleware "rate limit"`.
4. Use multiple queries for OR logic: `queries=["authentication", "middleware"]`.

## Artisan

- Run Artisan commands directly via the command line (e.g., `php artisan route:list`). Use `php artisan list` to discover available commands and `php artisan [command] --help` to check parameters.
- Inspect routes with `php artisan route:list`. Filter with: `--method=GET`, `--name=users`, `--path=api`, `--except-vendor`, `--only-vendor`.
- Read configuration values using dot notation: `php artisan config:show app.name`, `php artisan config:show database.default`. Or read config files directly from the `config/` directory.

## Tinker

- Execute PHP in app context for debugging and testing code. Do not create models without user approval, prefer tests with factories instead. Prefer existing Artisan commands over custom tinker code.
- Always use single quotes to prevent shell expansion: `php artisan tinker --execute 'Your::code();'`
  - Double quotes for PHP strings inside: `php artisan tinker --execute 'User::where("active", true)->count();'`

=== php rules ===

# PHP

- Always use curly braces for control structures, even for single-line bodies.
- Use PHP 8 constructor property promotion: `public function __construct(public GitHub $github) { }`. Do not leave empty zero-parameter `__construct()` methods unless the constructor is private.
- Use explicit return type declarations and type hints for all method parameters: `function isAccessible(User $user, ?string $path = null): bool`
- Use TitleCase for Enum keys: `FavoritePerson`, `BestLake`, `Monthly`.
- Prefer PHPDoc blocks over inline comments. Only add inline comments for exceptionally complex logic.
- Use array shape type definitions in PHPDoc blocks.

=== deployments rules ===

# Deployment

- Laravel can be deployed using [Laravel Cloud](https://cloud.laravel.com/), which is the fastest way to deploy and scale production Laravel applications.

=== tests rules ===

# Test Enforcement

- Every change must be programmatically tested. Write a new test or update an existing test, then run the affected tests to make sure they pass.
- Run the minimum number of tests needed to ensure code quality and speed. Use `php artisan test --compact` with a specific filename or filter.

=== inertia-laravel/core rules ===

# Inertia

- Inertia creates fully client-side rendered SPAs without modern SPA complexity, leveraging existing server-side patterns.
- Components live in `resources/js/pages` (unless specified in `vite.config.js`). Use `Inertia::render()` for server-side routing instead of Blade views.
- ALWAYS use `search-docs` tool for version-specific Inertia documentation and updated code examples.
- IMPORTANT: Activate `inertia-react-development` when working with Inertia client-side patterns.

# Inertia v3

- Use all Inertia features from v1, v2, and v3. Check the documentation before making changes to ensure the correct approach.
- New v3 features: standalone HTTP requests (`useHttp` hook), optimistic updates with automatic rollback, layout props (`useLayoutProps` hook), instant visits, simplified SSR via `@inertiajs/vite` plugin, custom exception handling for error pages.
- Carried over from v2: deferred props, infinite scroll, merging props, polling, prefetching, once props, flash data.
- When using deferred props, add an empty state with a pulsing or animated skeleton.
- Axios has been removed. Use the built-in XHR client with interceptors, or install Axios separately if needed.
- `Inertia::lazy()` / `LazyProp` has been removed. Use `Inertia::optional()` instead.
- Prop types (`Inertia::optional()`, `Inertia::defer()`, `Inertia::merge()`) work inside nested arrays with dot-notation paths.
- SSR works automatically in Vite dev mode with `@inertiajs/vite` - no separate Node.js server needed during development.
- Event renames: `invalid` is now `httpException`, `exception` is now `networkError`.
- `router.cancel()` replaced by `router.cancelAll()`.
- The `future` configuration namespace has been removed - all v2 future options are now always enabled.

=== laravel/core rules ===

# Do Things the Laravel Way

- Use `php artisan make:` commands to create new files (i.e. migrations, controllers, models, etc.). You can list available Artisan commands using `php artisan list` and check their parameters with `php artisan [command] --help`.
- If you're creating a generic PHP class, use `php artisan make:class`.
- Pass `--no-interaction` to all Artisan commands to ensure they work without user input. You should also pass the correct `--options` to ensure correct behavior.

### Model Creation

- When creating new models, create useful factories and seeders for them too. Ask the user if they need any other things, using `php artisan make:model --help` to check the available options.

## APIs & Eloquent Resources

- For APIs, default to using Eloquent API Resources and API versioning unless existing API routes do not, then you should follow existing application convention.

## URL Generation

- When generating links to other pages, prefer named routes and the `route()` function.

## Testing

- When creating models for tests, use the factories for the models. Check if the factory has custom states that can be used before manually setting up the model.
- Faker: Use methods such as `$this->faker->word()` or `fake()->randomDigit()`. Follow existing conventions whether to use `$this->faker` or `fake()`.
- When creating tests, make use of `php artisan make:test [options] {name}` to create a feature test, and pass `--unit` to create a unit test. Most tests should be feature tests.

## Vite Error

- If you receive an "Illuminate\Foundation\ViteException: Unable to locate file in Vite manifest" error, you can run `npm run build` or ask the user to run `npm run dev` or `composer run dev`.

=== wayfinder/core rules ===

# Laravel Wayfinder

Use Wayfinder to generate TypeScript functions for Laravel routes. Import from `@/actions/` (controllers) or `@/routes/` (named routes).

=== pint/core rules ===

# Laravel Pint Code Formatter

- If you have modified any PHP files, you must run `vendor/bin/pint --dirty --format agent` before finalizing changes to ensure your code matches the project's expected style.
- Do not run `vendor/bin/pint --test --format agent`, simply run `vendor/bin/pint --format agent` to fix any formatting issues.

=== pest/core rules ===

## Pest

- This project uses Pest for testing. Create tests: `php artisan make:test --pest {name}`.
- The `{name}` argument should not include the test suite directory. Use `php artisan make:test --pest SomeFeatureTest` instead of `php artisan make:test --pest Feature/SomeFeatureTest`.
- Run tests: `php artisan test --compact` or filter: `php artisan test --compact --filter=testName`.
- Do NOT delete tests without approval.

=== inertia-react/core rules ===

# Inertia + React

- IMPORTANT: Activate `inertia-react-development` when working with Inertia React client-side patterns.

</laravel-boost-guidelines>
