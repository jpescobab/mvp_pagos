# CAPJ App Pagos — Harness + OpenSpec v9

Plataforma institucional de gestión, workflow, trazabilidad, expediente documental y reportabilidad (Laravel 13 + Inertia + React sobre PostgreSQL). Complementa sistemas oficiales (SGF, CGU, BancoEstado, SII, CMF, Mercado Público); no los reemplaza. El core (§16 de `HARNESS_IA.md`) y los módulos Pago de Proveedores, Adquisiciones e Informes Razonados ya están implementados.

## Puesta en marcha

```bash
composer install && npm install
cp .env.example .env && php artisan key:generate
php artisan migrate
composer dev   # servidor + queue listener + Vite, en paralelo
```

Comandos de lint/tests/build detallados: ver la sección "Comandos" de [CLAUDE.md](CLAUDE.md).

## Harness y desarrollo asistido por IA

Todo cambio de código pasa por el harness y por OpenSpec — no se escribe a mano por fuera de ese ciclo (`/opsx:propose` → `/opsx:apply` → `/opsx:archive`).

- [AGENTS.md](AGENTS.md): instrucciones rápidas para cualquier agente IA.
- [CLAUDE.md](CLAUDE.md): entrada específica para Claude Code.
- [HARNESS_IA.md](HARNESS_IA.md): documento rector obligatorio — principios, jerarquía institucional, reglas de workflow/snapshot/documentos.
- `openspec/config.yaml`: contexto del harness, inyectado en todo artefacto OpenSpec generado (proposal, design, tasks, specs).
- `openspec/specs/*/spec.md`: requisitos vigentes por dominio (`openspec list --specs`).
- `openspec/changes/archive/`: historial de cambios ya implementados.
- `tasks/01..10`: las tareas core originales, ya implementadas; funcionalidad nueva se propone ad-hoc vía `/opsx:propose`, sin numeración fija.

## Otros directorios de referencia

- `docs/*.md`: decisiones de diseño y tablas de referencia (indicadores económicos, orden de implementación, SGF como origen).
- `config-templates/*.yaml`: semillas/configuración sugerida para indicadores económicos y módulos del sistema.
- `scripts/`: utilidades de conversión de seeders y verificación básica de estructura.
