## Why

La tarea 10 (`reportabilidad-informes-razonados`) ya construyó `CorteReportabilidadService` e `InformeRazonadoService` con su lógica completa y testeada: abrir período, crear corte, publicarlo (con permiso), iniciar una ejecución de informe sobre un corte publicado, moverla por su propio workflow (`en_elaboracion` → `en_revision` → `aprobado`/`rechazado` → `publicado`, con snapshot inmutable al publicar) y exportarla. Sin embargo, ningún controlador invoca estos servicios ni expone esos datos: hoy es imposible abrir un período, crear o publicar un corte, o generar y aprobar un informe razonado sin escribir código a mano. El módulo tiene el 90% de su lógica lista y 0% de interfaz.

## What Changes

- **Reportabilidad (períodos y cortes)**: exponer listar/abrir períodos, listar/crear cortes dentro de un período, ver el detalle de un corte (cantidad de items y snapshots) y publicarlo (permiso `reportabilidad.publicar_corte`, ya seedeado).
- **Informes razonados (definiciones y ejecuciones)**: exponer listar/crear definiciones de informe, listar/iniciar ejecuciones sobre un corte publicado, ver el detalle de una ejecución (secciones, métricas, gráficos, narrativas, excepciones, snapshots, aprobaciones, exportaciones, estado de workflow) y ejecutar sus transiciones (`enviar_a_revision`, `aprobar`, `rechazar`, `publicar`) a través de `InformeRazonadoService` (no directamente `TransicionWorkflowService`, para conservar los efectos secundarios de aprobación/snapshot que el servicio ya implementa).
- **Fuera de alcance explícito**: no se construye UI genérica para `agregarItem`/`capturarSnapshot` (corte) ni `agregarSeccion`/`agregarMetrica`/`agregarGrafico`/`agregarNarrativa`/`agregarExcepcion` (informe) — son primitivos pensados para que cada módulo funcional (pago de proveedores, adquisiciones, etc.) los invoque programáticamente cuando genere su propio contenido, no una entrada de datos libre desde un formulario administrativo genérico. Por eso el contenido de una ejecución puede aparecer vacío hasta que un módulo funcional lo alimente; esta tarea solo construye el ciclo de vida (crear, revisar, aprobar/rechazar, publicar, exportar) y la visualización de lo que exista.

## Capabilities

### New Capabilities
- `gestionar-periodos-cortes-reportabilidad`: abrir períodos de reportabilidad, crear cortes dentro de un período y publicarlos.
- `gestionar-informes-razonados`: crear definiciones de informe razonado, iniciar ejecuciones sobre un corte publicado y moverlas por su workflow de revisión/aprobación/publicación.

## Impact

- Nuevos: `App\Http\Controllers\Reportabilidad\{PeriodoReportabilidadController,CorteReportabilidadController}`, `App\Http\Controllers\InformesRazonados\{DefinicionInformeRazonadoController,EjecucionInformeRazonadoController,TransicionEjecucionInformeRazonadoController}`, sus Form Requests y Resources, `routes/reportabilidad.php`, `routes/informes-razonados.php`, páginas React bajo `resources/js/pages/reportabilidad/` e `informes-razonados/`.
- Modificados: `routes/web.php`, `resources/js/components/app-sidebar.tsx`.
- Reutiliza `App\Http\Resources\PagoProveedores\ProcesoResource` para el `proceso` de cada ejecución (mismo patrón ya usado por `ProcesoAdquisicionResource`), sin checklist ni documentos (esta definición de workflow no los exige).
- Sin cambios de esquema — todos los modelos, migraciones, permisos y la definición de workflow `informes_razonados` ya existen desde la tarea 10.
