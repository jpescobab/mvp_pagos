## 1. Backend: búsqueda en el listado

- [x] 1.1 Modificar `ImportacionSgfController::index` para aceptar un parámetro `q` opcional y filtrar `trabajos_integracion` del sistema `SGF` por `tipo` (coincidencia exacta o `like`) o por el nombre del usuario relacionado (`iniciadoPor.name`, `like`), pasando también `q` como prop a la vista.
- [x] 1.2 Actualizar/agregar tests en `tests/Feature/Sgf/ConsultarImportacionesSgfTest.php` cubriendo: listado sin filtro (comportamiento actual intacto), filtro con coincidencias, filtro sin resultados.

## 2. Frontend: badge de estado

- [x] 2.1 Crear `resources/js/components/sgf/importacion-estado-badge.tsx` que mapee `completado`→token semántico `success`, `error`→`danger`, `en_progreso`→variante neutra/ámbar, siguiendo el mismo patrón de `OrdenCompraEstadoBadge`.

## 3. Frontend: reescritura de `sgf/importaciones/index.tsx`

- [x] 3.1 Convertir la tabla a `table-fixed` con columnas de ancho fijo (Tipo, Iniciado por, Iniciado en, Finalizado en, Total elementos, Estado, acciones), replicando las clases y densidad de `ordenes-compra-mercado-publico/index.tsx`.
- [x] 3.2 Agregar `Avatar`+`AvatarFallback` con iniciales (via `useInitials`) junto a "Iniciado por", con fallback "Sistema" cuando `iniciado_por` es `null`.
- [x] 3.3 Reemplazar el texto plano de "Estado" por `ImportacionEstadoBadge`.
- [x] 3.4 Truncar con `title` (tooltip) las columnas secundarias (fechas, total de elementos) y ocultarlas progresivamente en viewports angostos (`hidden md:table-cell` / `lg:table-cell`, según corresponda).
- [x] 3.5 Agregar campo de búsqueda (`Input`) con debounce 300ms y `router.get(importaciones.index().url, { q } , { preserveState: true, preserveScroll: true })`, siguiendo el patrón de `ordenes-compra-mercado-publico/index.tsx`.
- [x] 3.6 Agregar menú de acciones desplegable (`DropdownMenu`) al final de cada fila con la opción "Ver detalle" (enlaza a `importaciones.show`), conservando la fila completa como clicable.
- [x] 3.7 Aplicar fallback `"—"` en `finalizado_en` nulo (ya existe) y verificar que se mantenga tras la reescritura.
- [x] 3.8 Mantener el botón "Importar pendientes de SGF" y su comportamiento (`router.post`) sin cambios funcionales.

## 4. Verificación

- [x] 4.1 `composer test` (backend) y `npm run lint:check` + `npm run types:check` (frontend).
- [x] 4.2 Verificación manual en navegador: cargar el listado, probar la búsqueda con y sin resultados, abrir el menú de acciones, confirmar que el botón de importar pendientes sigue funcionando.
