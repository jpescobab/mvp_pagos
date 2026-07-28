## Context

El dominio Informes Razonados ya tiene armado el flujo de elaboración de contenido para **secciones**, **excepciones** y **narrativas**: cada uno con su controlador liviano, rutas anidadas bajo `informes-razonados/ejecuciones/{ejecucion}`, Form Request, policy (`informes.elaborar` + `estaEnElaboracion()`), bloque de UI en `ejecuciones/show.tsx` y feature tests `Elaborar*InformeRazonadoTest`. La lógica de negocio vive en `InformeRazonadoService`.

Ese mismo service **ya implementa y prueba (unit)** `agregarMetrica`, `agregarGrafico` y `exportar`, pero esas tres capacidades quedaron sin capa HTTP ni UI de edición. El detalle (`EjecucionInformeRazonadoResource` + `show.tsx`) ya *muestra* métricas y gráficos en solo lectura, y el requirement "Mostrar el detalle completo" ya los contempla. Las tablas `metricas_informe_razonado`, `graficos_informe_razonado` y `exportaciones_informe_razonado` ya existen. Nada genera hoy el archivo físico de una exportación: `exportar()` recibe la ruta ya construida y solo registra la evidencia.

Este change cierra el hueco replicando el patrón existente, sin inventar arquitectura nueva.

## Goals / Non-Goals

**Goals:**
- Exponer crear/editar/eliminar de **métricas** y **gráficos** vía HTTP, gated por `informes.elaborar` y solo en `en_elaboracion`, con la misma forma que secciones/excepciones/narrativas.
- Exponer una acción de **exportar** que genera un archivo **HTML** del informe (desde el contenido ensamblado) y registra la `ExportacionInformeRazonado` como evidencia inmutable, gated por `informes.exportar`.
- UI en `ejecuciones/show.tsx`: formularios de métricas y gráficos, botón/selector de exportar, listado de exportaciones con enlace de descarga.
- Mantener controladores livianos: toda la escritura pasa por services.

**Non-Goals:**
- Generar formatos **PDF, Word o Excel** reales (y sus dependencias PhpWord/Dompdf/PhpSpreadsheet). La interfaz del exportador queda extensible, pero solo se implementa HTML.
- Cambiar el modelo de datos: no hay migraciones nuevas.
- Tocar el flujo de workflow de la ejecución (transiciones, aprobación, publicación) — fuera de alcance.
- Editar/eliminar exportaciones: son evidencia inmutable por diseño.

## Decisions

### 1. Un controlador liviano por sub-recurso, replicando el patrón del dominio
`MetricaInformeRazonadoController` y `GraficoInformeRazonadoController` con `store`/`update`/`destroy`, e `ExportacionInformeRazonadoController` con `store` (y opcionalmente `show`/download). Rutas anidadas en `routes/informes-razonados.php` siguiendo el bloque existente de secciones:
```
POST   ejecuciones/{ejecucion}/metricas
PATCH  metricas/{metrica}
DELETE metricas/{metrica}
POST   ejecuciones/{ejecucion}/graficos
PATCH  graficos/{grafico}
DELETE graficos/{grafico}
POST   ejecuciones/{ejecucion}/exportaciones
GET    exportaciones/{exportacion}/descargar   (stream del archivo privado)
```
**Por qué:** consistencia total con secciones/excepciones/narrativas; nada nuevo que aprender ni mantener. Alternativa descartada: un único `ContenidoInformeRazonadoController` polimórfico — rompería el patrón y mezclaría validaciones heterogéneas.

### 2. Autorización: reutilizar `informes.elaborar` para contenido, permiso nuevo `informes.exportar` para exportar
Métricas y gráficos son contenido de elaboración, idénticos en semántica a secciones: usan `informes.elaborar` + `estaEnElaboracion()`, con `MetricaInformeRazonadoPolicy` y `GraficoInformeRazonadoPolicy` calcadas de `SeccionInformeRazonadoPolicy`. Exportar es distinto: produce un entregable y tiene sentido sobre informes ya publicados (no solo `en_elaboracion`), así que se separa como `informes.exportar` (convención `modulo.verbo`) sobre `EjecucionInformeRazonadoPolicy@exportar`, sin exigir estado de elaboración.
**Por qué un permiso nuevo para exportar:** generar/descargar un documento oficial del informe es una capacidad distinta de redactar su contenido; un revisor podría exportar sin poder elaborar. Alternativa descartada: reutilizar `informes.elaborar` — ataría la exportación al estado `en_elaboracion` y a un rol de redacción, cuando lo natural es exportar el informe terminado.
Las policies nuevas se registran a mano en `AppServiceProvider::configureAuthorization()` (no hay auto-discovery). El permiso `informes.exportar` se agrega al seeder del dominio; si `RolesAndPermissionsSeederTest` afirma la lista exacta, se actualiza el test.

### 3. `ExportadorInformeRazonadoService` genera el archivo; `InformeRazonadoService::exportar()` registra la evidencia
Se separa la **generación** del archivo (armar el HTML desde el contenido ensamblado, escribirlo en `storage/app/private/informes-razonados/`) de la **persistencia** de la evidencia (ya existente en `exportar()`). El controlador orquesta: valida formato → `ExportadorInformeRazonadoService::exportar(ejecucion, 'html')` devuelve la ruta relativa del archivo → `InformeRazonadoService::exportar(ejecucion, 'html', $ruta, $usuario)` registra la fila.
**Por qué:** `exportar()` ya tiene su contrato y su test unit; no se toca su firma. El exportador es una pieza nueva y aislada, fácil de extender con `pdf`/`word`/`excel` sin reabrir el resto. El HTML se arma reutilizando `ensamblarContenido()` (ya existente, privado → se expone o se replica su ensamblado en el exportador vía una vista Blade dedicada).
**Interfaz extensible:** el exportador decide el generador por formato (hoy solo `html`); un formato no soportado lanza una excepción de validación traducida a 422, coherente con el rechazo declarado en la spec.

### 4. Almacenamiento privado, descarga por ruta controlada
El HTML se guarda en el disco privado (`storage/app/private/informes-razonados/{ejecucion}/...`), nunca en la BD, consistente con `storage/app/private/{documentos,sgf-documentos}` del resto del sistema. La descarga pasa por `exportaciones/{exportacion}/descargar`, autorizada, que hace stream del archivo — nunca se expone una URL pública directa al filesystem.

### 5. Frontend: extender `show.tsx`, no crear páginas nuevas
Se agregan a `ejecuciones/show.tsx` los formularios de métricas y gráficos (mismo estilo que el bloque de secciones ya presente) y una acción de exportar con selección de formato (solo `html` habilitado) más el listado de exportaciones con enlace de descarga. El `EjecucionInformeRazonadoResource` ya expone métricas/gráficos/exportaciones; se verifica que incluya los campos editables (`codigo`, `orden`, `seccion_informe_razonado_id`, `datos` del gráfico) y la URL de descarga de cada exportación. Las rutas se consumen vía helpers de Wayfinder (import con nombre), regenerados tras agregar rutas. React solo renderiza y envía; ninguna regla de negocio (tipos de gráfico, estados editables) se hardcodea: viene del backend/Resource.

## Risks / Trade-offs

- **[HTML como único formato deja el requirement de "Word/PDF/Excel" parcialmente cubierto]** → La spec de este change acota explícitamente a `html` y rechaza otros formatos por validación; el requirement 7 de `reportabilidad-informes-razonados` ("registrar evidencia... Word, PDF, Excel o HTML") se satisface para HTML y la interfaz queda lista para el resto. Se documenta como trabajo futuro, no como deuda oculta.
- **[`ensamblarContenido()` es privado]** → Se expone como método público del service o se arma el HTML en una vista Blade dedicada que recibe la ejecución con sus relaciones ya cargadas; se elige lo que menos acople el exportador a la estructura interna.
- **[El `datos` del gráfico es JSON arbitrario y podría llegar malformado desde el frontend]** → El Form Request valida que `datos` sea un array/JSON válido y `tipo` esté en el conjunto permitido; el modelo ya castea `datos` a array. No se interpreta el contenido de `datos` en este change (solo se persiste y se renderiza tal cual en el HTML).
- **[Agregar `informes.exportar` puede romper `RolesAndPermissionsSeederTest`]** → Se actualiza el test en el mismo change; es aditivo e idempotente (`givePermissionTo`), y se invalida la caché de permisos del store `database` tras sembrar para que la UI lo refleje.
- **[Exportar en cualquier estado podría permitir exportar un borrador incompleto]** → Es intencional y de bajo riesgo: la exportación es evidencia fechada de un contenido puntual, no una publicación; el nombre/fecha del archivo deja claro el momento del corte. No se mezcla con el flujo de publicación del workflow.

## Migration Plan

Sin migraciones de base de datos (las tablas existen). Despliegue estándar: agregar rutas/controladores/requests/policies/service, sembrar el permiso `informes.exportar` (`db:seed --class=RolesAndPermissionsSeeder`, idempotente) e invalidar la caché de permisos, regenerar Wayfinder en build. Rollback: revertir el commit; no hay estado persistente nuevo que limpiar salvo archivos HTML generados en el disco privado (inertes).

## Open Questions

- Ninguna bloqueante. La extensión a PDF/Word/Excel y la posible plantilla institucional del HTML quedan como trabajo futuro fuera de este change.
