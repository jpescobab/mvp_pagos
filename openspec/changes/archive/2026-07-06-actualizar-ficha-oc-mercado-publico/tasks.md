## 1. Backend: conservar fecha y hora reales

- [x] 1.1 En `app/Services/Adquisiciones/OrdenCompraMercadoPublicoService.php`, quitar el `substr(..., 0, 10)` de `cronogramaDesdeFechas()`, conservando el string de fecha/hora tal como lo entrega Mercado Público. `fecha_emision` queda sin cambios (su columna es `date`, Postgres truncaría la hora igual).
- [x] 1.2 Actualizar los fixtures de `tests/Feature/Adquisiciones/OrdenCompraMercadoPublicoServiceTest.php` y `ApiOrdenesCompraMercadoPublicoTest.php` para incluir hora en `Fechas.*` (ej. `2026-04-20 09:15:00`) y ajustar las aserciones del cronograma que comparaban contra fechas truncadas.
- [x] 1.3 Revisar `calcularDiferencias()`: confirmar que la comparación de `cronograma` sigue funcionando con el nuevo formato (comparación de arrays/strings sin asumir `Y-m-d`).

## 2. Backend: exponer snapshot para "Ver JSON"

- [x] 2.1 En `app/Http/Resources/Adquisiciones/OrdenCompraMercadoPublicoResource.php`, agregar el `payload_crudo` del snapshot vinculado (relación `snapshot`) cuando exista, usando `whenLoaded` para no forzar una carga adicional si no se pidió.
- [x] 2.2 Cargar la relación `snapshot` en `OrdenCompraMercadoPublicoController@show` (y en `buscarLocal`/`verificar`) junto a `items`/`proveedor`/`procesoAdquisicion`.
- [x] 2.3 Actualizar/crear tests de feature que verifiquen que el JSON crudo del snapshot llega en la prop de Inertia cuando la OC tiene snapshot vinculado, y que no rompe cuando no lo tiene.

## 3. Frontend: rediseño del cronograma con iconos

- [x] 3.1 En `resources/js/components/mercado-publico/ficha-consulta.tsx`, rediseñar `CronogramaTimeline` como línea de tiempo horizontal: ícono circular por etapa (check relleno verde si está completada, círculo vacío si no) conectados por una línea, con nombre de etapa, fecha/hora formateada y "Completado" debajo de cada ícono. Usar `lucide-react` (`Check`/`Circle` o equivalente) para los iconos, consistente con el resto de la app.
- [x] 3.2 Formatear fecha/hora en el frontend (no en el backend): si el valor trae hora, mostrar fecha y hora; si solo trae fecha, mostrar solo fecha sin inventar `00:00`.
- [x] 3.3 Verificar que el badge/ícono de estado ya existente en el encabezado (`Badge` con `estado_mercado_publico`) no se pierde ni se duplica al integrar el nuevo diseño.

## 4. Frontend: encabezado con monto destacado y acciones

- [x] 4.1 Extender `EncabezadoFichaConsulta`/`FichaConsultaMercadoPublico` para aceptar una zona de monto destacado (monto total) junto al título.
- [x] 4.2 Agregar las acciones "Ver JSON" (abre un diálogo/modal con el `payload_crudo` recibido, deshabilitada si no hay snapshot), "Ver PDF" y "Ver en Mercado Público" (deshabilitadas, con tooltip "Disponible próximamente"), en `show.tsx` y `buscar.tsx`.

## 5. Frontend: sección de desglose financiero

- [x] 5.1 Agregar la sección "Desglose financiero" (monto neto, impuesto = monto total − monto neto, monto total) en `construirSecciones()` de `show.tsx` y `buscar.tsx`, justo antes de "Datos del organismo comprador", usando `Monto` para el formato numérico y `"—"` cuando falte neto o total.

## 6. Validación

- [x] 6.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP tocados.
- [x] 6.2 `npm run lint:check` y `npm run types:check` sobre los archivos TSX tocados.
- [x] 6.3 `php artisan test --compact --filter=OrdenCompraMercadoPublico` (servicio + feature HTTP).
- [x] 6.4 Verificación manual en navegador: cargar una OC guardada (`show.tsx`) y una vista previa (`buscar.tsx`), confirmar que el cronograma queda como segunda sección con iconos y hora real, y que "Ver JSON" muestra el payload crudo.
