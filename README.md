# CAPJ App Pagos — Harness + OpenSpec optimizado v9

Paquete listo para subir a la raíz del proyecto Laravel.

## Archivos principales

- `AGENTS.md`: instrucciones rápidas para agentes IA.
- `CLAUDE.md`: instrucciones específicas para Claude Code.
- `HARNESS_IA.md`: documento rector obligatorio.
- `openspec/project.md`: contexto general del proyecto.
- `openspec/principles.md`: principios transversales.
- `openspec/specs/*/spec.md`: requisitos OpenSpec por dominio.
- `tasks/*.md`: tareas implementables en orden recomendado.
- `docs/*.md`: decisiones, tablas y criterios de diseño.
- `config-templates/*.yaml`: semillas/configuración sugerida.
- `scripts/check.sh`: verificación básica de estructura.

## Uso recomendado

1. Copiar este contenido a la raíz del proyecto Laravel.
2. Entregar a Claude Code/Codex el archivo `AGENTS.md` como instrucción inicial.
3. Implementar por tareas en orden: `tasks/01_*` en adelante.
4. No generar código que contradiga `HARNESS_IA.md`.
5. Mantener OpenSpec actualizado ante cualquier cambio de decisión.
