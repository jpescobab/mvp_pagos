## 1. Backend — filtro de estado

- [x] 1.1 En `app/Http/Controllers/Sgf/ImportacionSgfController.php@index()`, leer el parámetro `estado` del request igual que `q` (string, trim, `''` → `null`).
- [x] 1.2 En el mismo método, agregar `->when($estado === null, fn ($query) => $query->where('estado', '!=', 'completado'))` y `->when($estado !== null && $estado !== 'todos', fn ($query) => $query->where('estado', $estado))` al query existente de `TrabajoIntegracion`, inline en el controlador (sin crear un Service nuevo — ver design.md, Decisión 1: es una columna propia, sin `whereHas`/joins).
- [x] 1.3 Agregar `filtroEstado` (el valor de `$estado`: string o `null`) al array de props de `Inertia::render('sgf/importaciones/index', [...])`.

## 2. Frontend — ícono en el menú de acciones

- [x] 2.1 En `resources/js/pages/sgf/importaciones/index.tsx`, agregar `Eye` al import de `lucide-react` (junto a `MoreHorizontal`).
- [x] 2.2 Agregar `<Eye className="size-3.5" />` antes del texto "Ver detalle" dentro del `DropdownMenuItem` existente (líneas ~222-230), sin cambiar su destino (`importaciones.show.url(importacion.id)`).

## 3. Frontend — filtro de estado

- [x] 3.1 Agregar `filtroEstado: string | null` a `PageProps` y desestructurarlo desde `usePage<PageProps>().props`.
- [x] 3.2 Importar `Select`, `SelectContent`, `SelectItem`, `SelectSeparator`, `SelectTrigger`, `SelectValue` de `@/components/ui/select`.
- [x] 3.3 Definir las constantes `FILTRO_NO_COMPLETADAS = 'no_completadas'` y `FILTRO_TODOS = 'todos'` a nivel de módulo (sentinels de UI, nunca se envían como query param igual al valor `FILTRO_NO_COMPLETADAS` — ver design.md, Decisión 2).
- [x] 3.4 Actualizar el `useEffect` de debounce de búsqueda (líneas ~36-51) para incluir `estado: filtroEstado` en los parámetros de `router.get` cuando `filtroEstado` no sea `null`, preservando el filtro de estado vigente al buscar por texto.
- [x] 3.5 Agregar la función `cambiarFiltroEstado(valor: string)` que navega con `router.get`, incluyendo `q` (si `termino !== ''`) y `estado` (si `valor !== FILTRO_NO_COMPLETADAS`), preservando el término de búsqueda vigente al cambiar el filtro de estado.
- [x] 3.6 Agregar `<Select value={filtroEstado ?? FILTRO_NO_COMPLETADAS} onValueChange={cambiarFiltroEstado}>` en el header, junto al `<Input>` de búsqueda, con las opciones: "No completadas" (`FILTRO_NO_COMPLETADAS`), "Todos los estados" (`FILTRO_TODOS`), separador, y las 4 opciones concretas seleccionables explícitamente ("En progreso" = `en_progreso`, "Completado" = `completado`, "Error" = `error`, "Huérfano" = `huerfano`).

## 4. Tests — ajustar los que se rompen con el nuevo default

- [x] 4.1 En `tests/Feature/Sgf/ConsultarImportacionesSgfTest.php`, agregar `'estado' => 'todos'` a la llamada `route('sgf.importaciones.index')` del test "un usuario autenticado puede listar las importaciones SGF ordenadas de la más reciente a la más antigua" (línea ~109).
- [x] 4.2 Agregar `'estado' => 'todos'` a ambas llamadas `route('sgf.importaciones.index', ['q' => ...])` del test "el listado se puede filtrar por un término de búsqueda que coincide con el tipo o el usuario que la inició" (líneas ~150 y ~156).
- [x] 4.3 Agregar `'estado' => 'todos'` a la llamada del test "el listado queda vacío sin error cuando el término de búsqueda no coincide con nada" (línea ~174), para aislar ese caso del nuevo filtro por defecto.

## 5. Tests — cobertura nueva

- [x] 5.1 Test: por defecto (sin parámetro `estado`) el listado excluye los trabajos en estado `completado` — crear un `TrabajoIntegracion` de cada uno de los 4 estados (`en_progreso`, `completado`, `error`, `huerfano`) y verificar que el listado devuelve solo los 3 no completados.
- [x] 5.2 Test: con `estado=todos` el listado incluye también los `completado` — mismos 4 trabajos, verificar que devuelve los 4.
- [x] 5.3 Test: con `estado=error` (u otro valor puntual) el listado devuelve únicamente los trabajos en ese estado exacto.
- [x] 5.4 Test: el filtro por defecto se combina con `q` mediante AND — crear un trabajo `completado` y uno `en_progreso` que coincidan con el mismo término de búsqueda, consultar sin pasar `estado`, y verificar que solo aparece el `en_progreso`.

## 6. Validación

- [x] 6.1 Ejecutar `php artisan test --compact --filter=ConsultarImportacionesSgfTest` y confirmar que todos los tests (ajustados y nuevos) pasan.
- [x] 6.2 Ejecutar `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados.
- [x] 6.3 Ejecutar `composer test` (config:clear + lint:check + types:check + suite completa) y confirmar que no hay regresiones.
- [ ] 6.4 Verificación manual en navegador: abrir "Importaciones SGF", confirmar que por defecto no aparecen filas `completado`, cambiar el filtro a "Todos los estados" y a un estado puntual, confirmar que el ícono de ojo aparece junto a "Ver detalle" y sigue navegando al detalle, y que buscar por texto conserva el filtro de estado elegido (y viceversa). **No realizada**: el Browser pane no respondió (timeout en `preview_start`/`navigate`); el usuario decidió avanzar sin esta verificación, apoyado en la cobertura de tests (19/19) y lint/types/pint/phpstan limpios. Pendiente de confirmación visual manual por el usuario.
