## Context

`CorteReportabilidadService` e `InformeRazonadoService` (tarea 10) ya implementan toda la lógica de negocio con tests propios (`tests/Feature/Reportabilidad/CorteReportabilidadServiceTest.php`, `tests/Feature/InformesRazonados/InformeRazonadoServiceTest.php`). `WorkflowInformesRazonadosSeeder` ya siembra la definición de workflow `informes_razonados` (5 estados, 4 transiciones) y los permisos `reportabilidad.publicar_corte`, `informes.aprobar`, `informes.publicar`. No existe ningún controlador, ruta ni página que invoque estos servicios.

## Goals / Non-Goals

**Goals:**
- Ciclo de vida completo y usable desde la UI: abrir período → crear corte → publicar corte → crear definición de informe → iniciar ejecución sobre el corte publicado → enviar a revisión → aprobar/rechazar → publicar → exportar.
- Reusar `TransicionWorkflowService` exclusivamente a través de `InformeRazonadoService` (nunca directo desde el controlador), para no perder los efectos secundarios (`AprobacionInformeRazonado`, `SnapshotInformeRazonado`) que el servicio ya implementa.

**Non-Goals:**
- No se construye un editor de contenido (secciones/métricas/gráficos/narrativas/excepciones) — son APIs internas para que cada módulo funcional las invoque al generar su propio informe. Esta tarea solo lee y muestra lo que ya exista.
- No se automatiza la creación de períodos/cortes por scheduler — sigue siendo una acción humana explícita, consistente con "informes razonados nacen de cortes... siempre terminan con revisión humana".
- No se implementa la exportación real a PDF/Excel (`ExportacionInformeRazonado.ruta_archivo` ya existe como campo, pero generar el archivo es trabajo de otra tarea); aquí solo se expone el registro de exportaciones ya creadas vía servicio si existieran.

## Decisions

1. **Reusar `App\Http\Resources\PagoProveedores\ProcesoResource`** para el `proceso` de cada `EjecucionInformeRazonado`, igual que ya hace `ProcesoAdquisicionResource` — es el resource genérico de `Proceso` del proyecto, no exclusivo de pago de proveedores a pesar del namespace. No se carga `checklist` ni `vinculosDocumento` porque la definición de workflow `informes_razonados` no los usa.
2. **Las transiciones de la ejecución de informe se ejecutan vía `InformeRazonadoService`**, no vía un controlador genérico de transiciones: `TransicionEjecucionInformeRazonadoController::store()` despacha según el código (`enviar_a_revision` → `enviarARevision()`, `aprobar` → `aprobar()`, `rechazar` → `rechazar()`, `publicar` → `publicar()`), capturando `TransicionWorkflowException` igual que `TransicionCasoPagoProveedorController`.
3. **Publicar un corte captura `CorteReportabilidadException`** (igual patrón) en vez de un `Gate::authorize` adicional — el chequeo de permiso ya vive dentro de `CorteReportabilidadService::publicarCorte()`.
4. **Sin Policy nueva**: el resto de acciones (abrir período, crear corte, crear definición, iniciar ejecución, enviar a revisión) no llevan permiso específico en el seeder — están abiertas a cualquier usuario autenticado, igual que crear un caso de pago o un proceso de adquisición no requiere permiso especial más allá de las transiciones sensibles.
5. **Iniciar una ejecución exige que el corte esté publicado**: la validación ya existe en el servicio (`CorteReportabilidadException::corteNoPublicado()`); el controlador la captura igual que las demás excepciones del dominio.
6. **Páginas**: `reportabilidad/periodos/index.tsx` lista períodos con sus cortes anidados (crear corte, publicar); `informes-razonados/definiciones/index.tsx` lista/crea definiciones; `informes-razonados/ejecuciones/index.tsx` lista ejecuciones e inicia una nueva (elige definición + corte publicado); `informes-razonados/ejecuciones/show.tsx` muestra el detalle completo y las acciones de transición disponibles.

## Risks / Trade-offs

- [Riesgo] El contenido (secciones/métricas) estará vacío para toda ejecución creada desde esta UI, porque nada lo genera todavía. → Mitigación: documentado explícitamente como fuera de alcance; la página de detalle maneja el estado vacío sin romperse, y el ciclo de vida (revisión/aprobación/publicación/auditoría) es igualmente válido y demostrable sin contenido.
- [Riesgo] Permitir crear definiciones de informe libremente desde la UI (sin seeder institucional previo) podría divergir del patrón de `DefinicionWorkflow`, que sí se siembra. → Mitigación: `DefinicionInformeRazonado` es un catálogo simple (código/nombre/descripción) sin estructura de estados/transiciones propia que gestionar; crear uno desde la UI es de bajo riesgo y no requiere coordinación previa como sí la requiere una `DefinicionWorkflow`.

## Migration Plan

- Sin migraciones nuevas. Todo el esquema, seeders de workflow y permisos ya existen desde la tarea 10.
