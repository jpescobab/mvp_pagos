#!/usr/bin/env bash
set -euo pipefail
required=(
  "AGENTS.md"
  "CLAUDE.md"
  "HARNESS_IA.md"
  "openspec/project.md"
  "openspec/principles.md"
  "docs/DECISIONES_TOMADAS_v9.md"
)
for f in "${required[@]}"; do
  if [ ! -f "$f" ]; then
    echo "Falta archivo requerido: $f"
    exit 1
  fi
done
count=$(find openspec/specs -name spec.md | wc -l | tr -d ' ')
if [ "$count" -lt 8 ]; then
  echo "Se esperaban al menos 8 specs, encontrados: $count"
  exit 1
fi
echo "Estructura OpenSpec/Harness OK"
