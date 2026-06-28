## Why

`IndicadorEconomico` (UF/USD/UTM/UTA/IPC) ya tiene importador (`IndicadorEconomicoImporter`), selector para cálculos (`IndicadorEconomicoSelector`) y job mensual/diario (`ImportarIndicadoresMensualesJob`) completamente implementados y testeados, pero no existe ningún controlador, ruta ni página que los expongan: hoy no hay forma de ver qué valores se han importado. Son datos de referencia institucional (`Core no desactivable` según el harness) usados en cálculos en todo el sistema, e invisibles para cualquier usuario.

## What Changes

- Página de consulta de indicadores económicos, de solo lectura: lista paginada de `indicadores_economicos`, filtrable por `tipo` (UF, USD, UTM, UTA, IPC).
- Endpoint HTTP autenticado (sin permiso adicional — son datos de referencia, no datos sensibles ni ligados a la jerarquía institucional) que expone esa lista.
- Enlace en el sidebar (nivel raíz, junto a "Dashboard", ya que no pertenece a ningún módulo funcional específico).

No incluye en este change: disparar una importación manualmente desde la UI, ni editar/eliminar un indicador (estos siguen siendo responsabilidad exclusiva del job/importador, que conserva snapshot y trazabilidad — no tiene sentido permitir mutación manual de un dato cuya fuente de verdad es la CMF).

## Capabilities

### New Capabilities

- `consulta-indicadores-economicos`: ruta y página HTTP/Inertia de solo lectura para listar los indicadores económicos importados, filtrables por tipo.

### Modified Capabilities

(ninguna — `indicadores-economicos-cmf-sii` no cambia su comportamiento de importación/selección, solo se le agrega visibilidad vía una capability nueva)

## Impact

- Backend: nuevo `App\Http\Controllers\Indicadores\IndicadorEconomicoController`, nuevo `App\Http\Resources\Indicadores\IndicadorEconomicoResource`, nueva ruta `routes/indicadores.php`.
- Frontend: nueva página `resources/js/pages/indicadores-economicos/index.tsx`, nuevo tipo en `resources/js/types/`, entrada nueva en `resources/js/components/app-sidebar.tsx`.
- Tests: feature test para el listado y el filtro por tipo.
