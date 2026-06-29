## Why

`DefinicionWorkflow`, `EstadoWorkflow` y `TransicionWorkflow` ya tienen datos reales y completos para tres módulos (`pago_proveedores`: 13 estados/13 transiciones, `adquisiciones`: 8/8, `informes_razonados`: 5/4), con sus permisos requeridos, documentos obligatorios y flags de comentario obligatorio. Sin embargo, ningún controlador expone esa definición: hoy la única forma de saber qué transiciones existen, qué permiso exige cada una o qué documentos bloquean un cambio de estado es leer el seeder en PHP. Esto contradice el espíritu de "Workflow antes que CRUD" del harness — las reglas que gobiernan cada módulo deberían ser consultables, no solo ejecutables a ciegas desde un botón.

## What Changes

- Exponer un listado de las `DefinicionWorkflow` existentes (código, nombre, activo, cantidad de estados y transiciones).
- Exponer el detalle de una definición: todos sus `estados` (con `es_inicial`/`es_final`) y todas sus `transiciones` (estado origen → destino, permiso requerido, documentos requeridos, si exige comentario).
- Es de solo lectura, abierto a cualquier usuario autenticado — mismo criterio ya usado para `indicadores-economicos` (referencia institucional, sin dato sensible de ningún caso ni proceso concreto).

## Capabilities

### New Capabilities
- `consulta-definiciones-workflow`: listar y ver el detalle de las definiciones de workflow (estados, transiciones, permisos, documentos requeridos).

## Impact

- Nuevos: `App\Http\Controllers\Workflow\DefinicionWorkflowController`, `App\Http\Resources\Workflow\DefinicionWorkflowResource`, `routes/workflow.php`, páginas `resources/js/pages/workflow/definiciones/{index,show}.tsx`.
- Modificados: `routes/web.php` (require del nuevo archivo), `resources/js/components/app-sidebar.tsx` (nuevo ítem de navegación).
- Sin cambios de esquema ni de permisos.
