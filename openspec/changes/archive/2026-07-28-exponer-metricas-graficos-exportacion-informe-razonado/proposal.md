## Why

El `InformeRazonadoService` ya implementa y prueba (unit) `agregarMetrica`, `agregarGrafico` y `exportar`, pero esas tres capacidades no tienen capa HTTP ni UI de elaboración: el usuario puede crear secciones, excepciones y narrativas de una ejecución de informe razonado, pero no puede agregar/editar/eliminar sus métricas ni sus gráficos, ni generar una exportación del informe. La pantalla de detalle (`ejecuciones/show.tsx`) ya *muestra* métricas y gráficos en solo lectura, dejando el flujo a medio camino. Además, el requirement 7 de la spec `reportabilidad-informes-razonados` ("Registrar evidencia de cada exportación de informe") no es operable: no hay forma de disparar una exportación desde la aplicación.

## What Changes

- **Elaborar métricas**: exponer crear, editar y eliminar métricas de una ejecución de informe razonado (`MetricaInformeRazonadoController` + rutas anidadas bajo `informes-razonados/ejecuciones/{ejecucion}`), con Form Requests, policy y feature tests HTTP, replicando el patrón ya probado de secciones/excepciones/narrativas. Solo permitido mientras la ejecución esté editable (`Proceso` en `en_elaboracion`).
- **Elaborar gráficos**: idéntico para gráficos (`GraficoInformeRazonadoController` + rutas + requests + policy + tests), incluyendo la configuración del gráfico (tipo, título, orden, datos/series).
- **Exportar informe**: nueva acción `ExportacionInformeRazonadoController@store` que genera el archivo del informe en formato **HTML** a partir del contenido ya ensamblado por el service y registra la `ExportacionInformeRazonado` (formato, ruta del archivo, responsable) vía `InformeRazonadoService::exportar()`. Un `ExportadorInformeRazonadoService` dedicado encapsula la generación del archivo, con una interfaz extensible para PDF/Word/Excel en el futuro (fuera de alcance de este change).
- **UI de elaboración**: en `ejecuciones/show.tsx`, agregar formularios de crear/editar/eliminar para métricas y gráficos (hoy solo lectura) y un botón de "Exportar" con selección de formato, más el listado de exportaciones registradas con enlace de descarga. React solo renderiza y envía; ninguna regla de negocio se hardcodea en el frontend.
- Regenerar los helpers tipados de Wayfinder tras agregar las rutas nuevas.
- **No** se agregan dependencias de terceros ni se generan formatos PDF/Word/Excel reales en este change.

## Capabilities

### New Capabilities
<!-- Ninguna: se extiende una capability existente -->

### Modified Capabilities
- `gestionar-informes-razonados`: se agregan requirements para **elaborar las métricas**, **elaborar los gráficos** y **generar una exportación** de una ejecución de informe razonado desde la aplicación (crear/editar/eliminar métricas y gráficos solo con la ejecución editable; exportación que genera archivo HTML y registra evidencia inmutable con formato, ruta y responsable, gated por permiso).

## Impact

- **Rutas**: `routes/informes-razonados.php` — nuevas rutas anidadas de métricas, gráficos y exportación bajo `ejecuciones/{ejecucion}`.
- **Controladores** (nuevos, livianos): `MetricaInformeRazonadoController`, `GraficoInformeRazonadoController`, `ExportacionInformeRazonadoController` en `app/Http/Controllers/InformesRazonados/`.
- **Form Requests** (nuevos): validación de métricas, gráficos y exportación en `app/Http/Requests/InformesRazonados/`.
- **Services**: se reutiliza `InformeRazonadoService` (`agregarMetrica`/`agregarGrafico`/`exportar`) y se agregan `editarMetrica`/`eliminarMetrica`/`editarGrafico`/`eliminarGrafico`; nuevo `ExportadorInformeRazonadoService` para la generación del archivo HTML.
- **Policies** (nuevas): `MetricaInformeRazonadoPolicy` y `GraficoInformeRazonadoPolicy`, replicando `SeccionInformeRazonadoPolicy` (`informes.elaborar` + `estaEnElaboracion()`); la exportación se autoriza con el permiso `informes.exportar` sobre `EjecucionInformeRazonadoPolicy`. Registro manual de las policies nuevas en `AppServiceProvider::configureAuthorization()` (no hay auto-discovery).
- **Permisos**: métricas y gráficos reutilizan el permiso existente `informes.elaborar` (son contenido, igual que secciones/excepciones/narrativas). Se agrega un permiso nuevo `informes.exportar` en el seeder de roles/permisos del dominio; si `RolesAndPermissionsSeederTest` afirma la lista exacta, actualizarlo.
- **Frontend**: `resources/js/pages/informes-razonados/ejecuciones/show.tsx` y su Resource `EjecucionInformeRazonadoResource` (asegurar que expone la data editable de métricas/gráficos y la lista de exportaciones con URL de descarga).
- **Storage**: los archivos HTML exportados se guardan en `storage/app/private/informes-razonados/` (no en la BD), consistente con el manejo de documentos del sistema.
- **Tests**: nuevos feature tests HTTP `ElaborarMetricasInformeRazonadoTest`, `ElaborarGraficosInformeRazonadoTest`, `ExportarInformeRazonadoTest`.
- **Sin migraciones**: las tablas `metricas_informe_razonado`, `graficos_informe_razonado` y `exportaciones_informe_razonado` ya existen.
