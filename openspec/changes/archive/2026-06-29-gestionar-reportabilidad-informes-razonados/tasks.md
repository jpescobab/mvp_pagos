## 1. Reportabilidad: backend

- [x] 1.1 Crear `App\Http\Requests\Reportabilidad\AbrirPeriodoReportabilidadRequest` (`codigo` required string, `fecha_inicio`/`fecha_fin` required date).
- [x] 1.2 ~~Crear `CrearCorteReportabilidadRequest`~~ — omitido: `CorteReportabilidadController::store()` no recibe campos propios (el período viene de la ruta), no hay nada que validar.
- [x] 1.3 Crear `App\Http\Controllers\Reportabilidad\PeriodoReportabilidadController::index()` (lista períodos con `withCount('cortesReportabilidad')`, orden por `fecha_inicio` desc) y `::store()` (llama `CorteReportabilidadService::abrirPeriodo`).
- [x] 1.4 Crear `App\Http\Controllers\Reportabilidad\CorteReportabilidadController::index()` (cortes de un período), `::show()` (detalle con counts de items/snapshots/ejecuciones), `::store()` (llama `crearCorte`), `::publicar()` (llama `publicarCorte`, captura `CorteReportabilidadException` y retorna `back()->withErrors(...)`).
- [x] 1.5 Crear `App\Http\Resources\Reportabilidad\PeriodoReportabilidadResource` y `CorteReportabilidadResource`.
- [x] 1.6 Crear `routes/reportabilidad.php`: `GET reportabilidad/periodos`, `POST reportabilidad/periodos`, `POST reportabilidad/periodos/{periodo}/cortes`, `GET reportabilidad/cortes/{corte}`, `POST reportabilidad/cortes/{corte}/publicar`. Requerir desde `routes/web.php`.

## 2. Reportabilidad: frontend

- [x] 2.1 Agregar tipos `PeriodoReportabilidad`, `CorteReportabilidad` en `resources/js/types/reportabilidad.ts`.
- [x] 2.2 Página `resources/js/pages/reportabilidad/periodos/index.tsx`: lista períodos, formulario para abrir uno nuevo, por cada período formulario para crear un corte y lista de sus cortes (link al detalle).
- [x] 2.3 Página `resources/js/pages/reportabilidad/cortes/show.tsx`: detalle del corte (período, estado, counts), botón "Publicar" si está en borrador.
- [x] 2.4 Agregar ítems de navegación "Períodos de Reportabilidad" en `resources/js/components/app-sidebar.tsx`.

## 3. Informes razonados: backend

- [x] 3.1 Crear `App\Http\Requests\InformesRazonados\CrearDefinicionInformeRazonadoRequest` (`codigo`, `nombre` required string; `descripcion` nullable).
- [x] 3.2 Crear `App\Http\Requests\InformesRazonados\IniciarEjecucionInformeRazonadoRequest` (`definicion_informe_razonado_id`, `corte_reportabilidad_id` required, deben existir).
- [x] 3.3 Crear `App\Http\Requests\InformesRazonados\EjecutarTransicionInformeRazonadoRequest` (`codigo` required string en `[enviar_a_revision,aprobar,rechazar,publicar]`, `comentario` nullable string).
- [x] 3.4 Crear `App\Http\Controllers\InformesRazonados\DefinicionInformeRazonadoController::index()` y `::store()`.
- [x] 3.5 Crear `App\Http\Controllers\InformesRazonados\EjecucionInformeRazonadoController::index()` (con definición/corte/proceso.estadoActual), `::store()` (llama `InformeRazonadoService::iniciarEjecucion`, captura `CorteReportabilidadException::corteNoPublicado()`), `::show()` (carga todas las relaciones de contenido + proceso).
- [x] 3.6 Crear `App\Http\Controllers\InformesRazonados\TransicionEjecucionInformeRazonadoController::store()`: despacha a `enviarARevision`/`aprobar`/`rechazar`/`publicar` de `InformeRazonadoService` según `codigo`, captura `TransicionWorkflowException`.
- [x] 3.7 Crear `App\Http\Resources\InformesRazonados\DefinicionInformeRazonadoResource` y `EjecucionInformeRazonadoResource` (definición, corte, `generado_por`, `generado_en`, `proceso` reusando `App\Http\Resources\PagoProveedores\ProcesoResource`, y mapeos de secciones/métricas/gráficos/narrativas/excepciones/snapshots/aprobaciones/exportaciones vía `whenLoaded`).
- [x] 3.8 Crear `routes/informes-razonados.php`: `GET informes-razonados/definiciones`, `POST informes-razonados/definiciones`, `GET informes-razonados/ejecuciones`, `POST informes-razonados/ejecuciones`, `GET informes-razonados/ejecuciones/{ejecucion}`, `POST informes-razonados/ejecuciones/{ejecucion}/transiciones`. Requerir desde `routes/web.php`.

## 4. Informes razonados: frontend

- [x] 4.1 Agregar tipos en `resources/js/types/informes-razonados.ts` (`DefinicionInformeRazonado`, `EjecucionInformeRazonado` con su contenido).
- [x] 4.2 Página `resources/js/pages/informes-razonados/definiciones/index.tsx`: lista + formulario de creación.
- [x] 4.3 Página `resources/js/pages/informes-razonados/ejecuciones/index.tsx`: lista de ejecuciones (definición, corte, estado) + formulario para iniciar una nueva (select definición + select corte publicado).
- [x] 4.4 Página `resources/js/pages/informes-razonados/ejecuciones/show.tsx`: detalle completo (secciones/métricas/gráficos/narrativas/excepciones/snapshots/aprobaciones/exportaciones, con estado vacío si no hay datos) y botones de transición disponibles según `proceso.transiciones_disponibles`.
- [x] 4.5 Agregar ítems de navegación "Definiciones de Informes" y "Ejecuciones de Informes" en `resources/js/components/app-sidebar.tsx`.

## 5. Tests

- [x] 5.1 Feature test: abrir un período y crear un corte vía HTTP persiste los registros esperados.
- [x] 5.2 Feature test: publicar un corte con permiso lo marca publicado; sin permiso lo bloquea y el corte sigue en borrador.
- [x] 5.3 Feature test: iniciar una ejecución sobre un corte publicado la crea con su `Proceso` en `en_elaboracion`; sobre un corte en borrador la rechaza.
- [x] 5.4 Feature test: recorrer el ciclo completo enviar_a_revision → aprobar (con permiso) → publicar (con permiso) crea la `aprobacion_informe_razonado` y el `snapshot_informe_razonado` esperados.
- [x] 5.5 Feature test: aprobar/publicar sin el permiso correspondiente bloquea la transición.
- [x] 5.6 Feature test: el detalle de una ejecución expone todas sus relaciones de contenido (vacías o no) y las transiciones disponibles.

## 6. Validación

- [x] 6.1 Ejecutar `vendor/bin/pint --dirty --format agent`, `npm run lint:check`, `npm run types:check` y `php artisan test` (suite completa, dado el alcance transversal del cambio). Verificado además en navegador real: ciclo completo abrir período → crear corte → publicar → crear definición → iniciar ejecución → enviar a revisión, todo funcionando correctamente end-to-end.
