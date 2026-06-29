## Context

`Proveedor` ya existe con `rutproveedor` único, `nombre`, `correo`, `direccion`, `contacto`, `activo` y soft-deletes, con 977 filas reales sembradas por `ProveedoresSeeder`. El único consumo actual de este modelo es indirecto: `CasoPagoProveedorImporter` lo busca por RUT exacto al importar un caso SGF, y `CasoPagoProveedorResource`/`EgresoCguController::create()` ya exponen `nombre`/`rutproveedor` sin ninguna restricción de permiso. No existe ningún punto de entrada para buscar el catálogo completo.

## Goals / Non-Goals

**Goals:**
- Listar y buscar proveedores por RUT o nombre, paginado.
- Mostrar RUT, nombre, correo, dirección, contacto y si está activo.

**Non-Goals:**
- No se agrega una página de detalle por proveedor — todos sus campos ya caben en la fila del listado, y su única relación (`clientesMedidores`) pertenece a un módulo (Consumo Eléctrico) que todavía no se construye; agregar un "show" vacío sería especular sobre datos que no existen aún.
- No se permite crear, editar ni eliminar proveedores desde esta UI — el catálogo se mantiene hoy vía seeder; un flujo de alta/edición es una tarea distinta y mayor (formulario, validación de RUT, deduplicación), fuera de alcance.
- No se filtra por `activo` en esta primera versión — la mayoría de los 977 proveedores sembrados no tienen el campo `activo` poblado explícitamente con un criterio real; agregar el filtro sin un criterio de "activo" confiable solo agregaría una UI que no filtra nada útil.

## Decisions

1. **Sin Policy, solo middleware `auth`**, igual que `indicadores-economicos`, `consulta-definiciones-workflow` y `consulta-importaciones-sgf`. El RUT y nombre de un proveedor ya son visibles sin restricción adicional en cualquier caso de pago o egreso CGU; no hay un dato más sensible aquí que justifique un permiso nuevo.
2. **Búsqueda server-side vía query string `q`, no fetch-based como `BuscarProcesoAdquisicionController`.** Esa búsqueda es un selector inline dentro de otra página (acotada a pocos resultados, sin paginación); este es un catálogo completo navegable por sí mismo, por lo que pagina y mantiene el término de búsqueda en la URL (`preserveState`), mismo patrón que el filtro de `indicadores-economicos/index.tsx`.
3. **Búsqueda sobre `rutproveedor` y `nombre` con `LIKE`, sin normalizar puntos/guiones del RUT.** Mismo criterio que el riesgo ya documentado en `pago-proveedores-sgf` (`CasoPagoProveedorImporter` empareja por RUT exacto): normalizar formatos de RUT es un problema transversal no resuelto aún en el proyecto: no se resuelve de forma distinta aquí.

## Risks / Trade-offs

- **[Riesgo] Ninguno relevante** — es una exposición de solo lectura sobre datos ya sembrados, sin tocar ningún flujo de escritura ni de importación.
