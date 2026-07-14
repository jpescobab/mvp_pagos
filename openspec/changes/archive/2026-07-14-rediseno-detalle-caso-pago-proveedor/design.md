## Context

`resources/js/pages/pago-proveedores/casos/show.tsx` (~1620 líneas) renderiza el detalle de un `caso_pago_proveedor`: transiciones de workflow, vínculo a proceso de adquisición, tipo de proceso de pago, checklist documental, documentos vinculados, registro contable CGU (Traspaso), pago bancario, facturas, historial de transiciones y egresos CGU asociados. Todas las secciones comparten el mismo tratamiento visual (`rounded-xl border p-4`), en una sola columna, en el orden en que se fueron agregando a lo largo de varios changes anteriores.

El criterio "listo para Asignar Egreso" ya existe y se calcula en el backend en dos lugares — `ListoParaEgresoResolver` (usado por `ImportacionSgfResource` y `EgresoCguController::create()`) — pero el detalle del caso individual no lo muestra: el usuario debe inferirlo leyendo cuatro secciones distintas de la página.

El mockup de referencia (Artifact `caso_pago_proveedor_redesign`, aprobado por el usuario) ya define la dirección visual: panel de preparación al inicio, 3 grupos con encabezado, checklist con íconos de estado, barra lateral sticky para transiciones y datos de resumen.

## Goals / Non-Goals

**Goals:**
- Reorganizar visualmente la página sin tocar ningún endpoint, prop, permiso ni comportamiento funcional existente.
- Mostrar el criterio "listo para Asignar Egreso" derivado en el cliente, reutilizando datos que Inertia ya envía en `caso`.
- Mantener el archivo mantenible extrayendo subcomponentes cohesivos en vez de dejar todo en un único archivo de 1600+ líneas.
- Conservar el comportamiento en modo claro/oscuro y responsive usando exclusivamente los tokens ya definidos en `resources/css/app.css` (vía clases Tailwind existentes: `bg-success-soft`, `text-warning`, `rounded-xl`, etc.).

**Non-Goals:**
- No se cambia el criterio de negocio "listo para Asignar Egreso" — se replica en el cliente exactamente la misma lógica de `ListoParaEgresoResolver` (fuente de verdad sigue siendo el backend; esto es solo una vista derivada, no una nueva regla).
- No se agregan campos nuevos a `ProcesoResource`/`CasoPagoProveedorResource` ni a `resources/js/types/pago-proveedores.ts`.
- No se toca el flujo de subida/vinculación de documentos, transiciones, registro CGU/pago/factura, ni sus policies o Form Requests.
- No se pagina ni se difiere (`Inertia::defer`) ninguna sección — el volumen de datos de un caso individual es acotado.

## Decisions

**1. El panel "Preparación para Asignar Egreso" se calcula en el cliente con un `useMemo`, no con una prop nueva del backend.**
Los 4 datos ya están disponibles en `caso` tal como llega hoy: `caso.proceso.tipo_proceso_pago_id`, `caso.registros_contables_cgu`, `caso.proceso.checklist?.items`, `caso.proveedor`. Replicar la comparación (`tipo_requisito === 'obligatorio' && documento_id !== null` para cada ítem) en un helper puro de TypeScript evita una prop nueva y mantiene el acoplamiento mínimo; si en el futuro diverge del criterio del backend, es una señal explícita para entonces sí exponerlo como prop. Alternativa descartada: agregar `listo_para_egreso` a `ProcesoResource` — se descarta porque duplicaría en el backend algo que ya se muestra en dos vistas distintas (importación SGF, formulario de Egreso) y agregaría una query/cálculo más al `show()` del caso sin necesidad, cuando el dato ya está todo presente en el payload actual.

**2. Extracción de 3 subcomponentes de presentación en `resources/js/components/pago-proveedores/`, sin mover lógica de estado.**
`preparacion-egreso-card.tsx` (recibe `caso` y renderiza el panel, sin `useState` propio), `checklist-documental-card.tsx` (recibe los mismos props/callbacks que hoy usa la sección de checklist: `puedeGestionarDocumentos`, `documentosHuerfanos`, `huerfanoSeleccionado`, `subirDocumento`, `vincularHuerfano`, etc.) y `transiciones-sidebar-card.tsx`. El resto de las secciones (Documentos, Traspaso, Pago bancario, Facturas, Historial, Egresos CGU) permanecen inline en `show.tsx`, agrupadas visualmente con un componente de layout simple (`<SeccionGrupo titulo="...">`), porque no ganan legibilidad relevante al extraerse — son bloques cortos y ya están claramente delimitados. Alternativa descartada: extraer todo a un componente por sección — se descarta por ser una reestructuración más grande de la necesaria para el objetivo de este change (jerarquía visual), no una limpieza general del archivo.

**3. Layout de dos columnas con CSS Grid, sidebar `sticky`, colapsando a una columna con `grid-template-columns` responsive (sin JS de breakpoint).**
Mismo patrón que ya usa `resources/js/pages/pago-proveedores/egresos-cgu/crear.tsx` para su layout (grid + `sticky` en el footer). Se usa `lg:grid-cols-[1fr_300px]` con la columna base en una sola columna, evitando cualquier lógica de `matchMedia` en React.

**4. El ícono de estado del checklist (`ChecklistItemRow`) es puramente visual: verde si `estado_cumplimiento !== 'pendiente'`, gris si `pendiente` — no introduce un tercer estado.**
El backend ya puede devolver `pendiente`, `cargado`, `valido`, `rechazado` como `estado_cumplimiento` (ver `ResolutorChecklistDocumentalProceso::estadoCumplimiento()`); el ícono verde cubre todo lo que no es `pendiente` para no duplicar esa lógica de estados en el frontend — el texto exacto del estado se sigue mostrando igual que hoy, el ícono es un refuerzo visual, no un reemplazo de la información.

## Risks / Trade-offs

- [Riesgo] Duplicar en el cliente la lógica de "listo para Asignar Egreso" (Decisión 1) puede divergir silenciosamente del backend si `ListoParaEgresoResolver` cambia en un change futuro sin actualizar este helper. → Mitigación: el helper se nombra y comenta explícitamente citando `ListoParaEgresoResolver` como fuente de verdad, y se agrega un test de React (si el proyecto tiene suite de componentes) o, a falta de eso, se documenta en el propio archivo para que el próximo change que toque el criterio lo recuerde revisar.
- [Riesgo] Extraer subcomponentes puede introducir una regresión sutil de props (ej. un callback mal pasado) que rompa un flujo existente sin que TypeScript lo detecte si algún tipo queda demasiado laxo. → Mitigación: mantener los mismos tipos ya definidos en `pago-proveedores.ts` para cada prop, y verificación manual en navegador de los 8 flujos interactivos de la página antes de dar el change por completo (listados en `proposal.md`, Impact).

## Migration Plan

1. Sin migración de esquema ni cambios de backend — deploy normal vía `npm run build`.
2. Rollback: revertir el commit deja `show.tsx` en su versión anterior; no hay datos ni estado persistente involucrado.
