# Principios transversales

## P1. Core no desactivable
Autenticación, usuarios, roles, permisos, estructura CAPJ, workflow, auditoría, documentos, indicadores, integraciones y reportabilidad base son core.

## P2. Jerarquía CAPJ
La estructura institucional se modela como CAPJ -> Jurisdicciones -> Centros financieros -> Centros de costos.

## P3. ID interno + código institucional
Las tablas maestras usan `id` como PK y código institucional como `unique`.

## P4. SGF como origen
SGF aporta datos, documentos y contexto. Nuestro sistema define gestión, workflow, estados, unidades y responsables.

## P5. Snapshot obligatorio
Todo dato/documento externo relevante conserva fuente, payload, hash, fecha, método y responsable de captura.

## P6. API primero
Usar API oficial. Playwright solo como respaldo autorizado.

## P7. Workflow antes que CRUD
Los procesos institucionales requieren estados, transiciones, tareas, responsables, documentos y auditoría.

## P8. Informes desde cortes
Los informes razonados nacen de cortes y snapshots, no de datos vivos cambiantes.

## P9. IA supervisada
La IA puede apoyar, pero toda decisión sensible requiere revisión humana.

## P10. Indicadores con vigencia
UF, USD, UTM, UTA e IPC tienen reglas distintas de publicación, vigencia y selección para cálculos.
