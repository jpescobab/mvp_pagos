## Context

`api-pago-proveedores` ya expone 6 rutas con sus Resources, pero ningún componente `.tsx` existe todavía — `Inertia::render('pago-proveedores/casos/index', ...)` apunta a un archivo inexistente. La fundación visual (`fundacion-visual-layout`) ya está aplicada (tema, branding, sidebar), así que estas páginas son las primeras en consumir ese tema fuera del scaffolding del starter kit.

Tres referencias de diseño (exports HTML del Design tool de Claude, fuera del repo, ya analizadas en conversación) informan los patrones de UI:
- **"Registrar Proveedor"**: formulario en grilla (`col-4`/`col-6`), inputs con ícono prefijo, campos monoespaciados (`JetBrains Mono`) para identificadores tipo RUT, asterisco en campos requeridos.
- **"Proveedores"**: tabla con columnas, badges de estado por color (verde/rojo/ámbar), chips de resumen, búsqueda, y un drawer de detalle con secciones (Identificación, Contacto, Comercial, Historial de actividad).
- **Dashboard**: sidebar tipo riel de íconos con tooltip (ya implementado en `fundacion-visual-layout`); contenido de KPIs/gráficos del archivo era una plantilla genérica no aplicable a este dominio.

El proyecto ya tiene primitives shadcn/ui instalados: `badge`, `card`, `dialog`, `select`, `checkbox`, `input`, `label`, `button`. No existe un componente `table` genérico — no se crea uno nuevo (no hay sorting/filtering que lo justifique); las tablas se marcan con HTML semántico (`<table>`) y Tailwind.

## Goals / Non-Goals

**Goals:**
- Las 4 páginas (`casos/index`, `casos/show`, `egresos-cgu/index`, `egresos-cgu/crear`) renderizan con datos reales de los Resources ya existentes, sin inventar campos ni lógica de negocio nueva.
- El detalle del caso (`casos/show`) permite ejecutar transiciones de workflow desde la UI, delegando 100% en el endpoint genérico ya construido — ningún chequeo de permiso ni de transición válida se duplica en React.
- El checklist documental se renderiza dinámicamente desde lo que el backend entrega (`proceso.checklist.items`), nunca hardcodeado — regla no negociable del harness.

**Non-Goals:**
- No se agregan filtros, búsqueda ni ordenamiento en los listados — el backend no los soporta hoy; agregarlos en el frontend sin soporte real de paginación/filtrado en el controlador sería simulación, no funcionalidad.
- No se oculta en el frontend ninguna transición por falta de permiso del usuario actual — el `ProcesoResource` no expone permisos del usuario y no se le va a agregar esa responsabilidad; la UI muestra todas las `transiciones_disponibles` y confía en que el backend rechace con mensaje claro (`back()->withErrors(['transicion' => ...])`) si el usuario no puede.
- No se implementa ninguna regla de "qué casos son elegibles para un egreso" — el formulario de creación permite elegir cualquier combinación de casos, igual que ya lo ejercita `CasoPagoProveedorImporterTest`.
- No se construye una página de detalle de egreso CGU (`egresos-cgu/show`) — no existe esa ruta en `api-pago-proveedores`.

## Decisions

1. **Brecha cerrada en `ProcesoResource`: agregar `checklist`.** El controlador `CasoPagoProveedorController::show()` ya hace `proceso.checklist.items` en su eager-load (tarea 5.2 de `api-pago-proveedores`), pero el Resource nunca lo serializaba — sin esto, `casos/show` no podría mostrar el expediente documental, que es una pieza central del dominio ("expediente documental variable", regla no negociable). Forma: `'checklist' => $this->checklist ? ['items' => [...]] : null` (un caso sin checklist generado aún muestra `null`, la página maneja ese estado vacío).

2. **Brecha cerrada en `EgresoCguController::create()`: pasar `casos` disponibles.** Sin una lista de casos para elegir, el formulario de creación no podría funcionar — no es una mejora opcional, es un requisito mínimo para que la página exista. Se reutiliza `CasoPagoProveedorResource::collection(CasoPagoProveedor::with('proveedor')->get())`; no se pagina (volumen esperado bajo para una tarea de selección manual) ni se filtra por elegibilidad (ver Non-Goals).

3. **Selector de casos en el formulario: lista con checkbox + input de monto por fila, no un `<select multiple>`.** Cada caso elegido necesita además un monto editable (`casos.*.monto` en `CrearEgresoCguRequest`); un `<select>` no permite eso. Se usa `useState` local con un arreglo de `{caso_pago_proveedor_id, monto}` que se serializa al payload del form de Inertia.

4. **Transiciones con `requiere_comentario`: `Dialog` de shadcn/ui antes de enviar.** Si la transición elegida tiene `requiere_comentario: true`, se abre un diálogo pidiendo el comentario antes de hacer `POST`; si no lo requiere, el botón envía directo. Esto evita un POST que el backend rechazaría con `comentarioRequerido()` solo para mostrar el error después — UX directa sin duplicar la regla (el backend sigue siendo quien valida realmente).

5. **Badge de estado reutilizable (`EstadoBadge`), no una librería de mapeo de colores nueva.** Un componente pequeño en `resources/js/components/pago-proveedores/estado-badge.tsx` mapea `codigo` de estado a una variante de color (usando las variantes ya soportadas por `badge.tsx`: default/secondary/destructive/outline, más clases Tailwind puntuales para verde/ámbar donde el primitive no alcance). Se usa en `casos/index`, `casos/show` y `egresos-cgu/index`.

6. **Historial de transiciones como lista cronológica simple, no un timeline visual complejo.** El diseño de referencia muestra un timeline con línea vertical en el drawer de "Proveedores"; se replica la estructura de datos (fecha, transición, usuario, comentario) pero con marcado simple (lista + `Separator`), evitando construir un componente de timeline genérico para un solo uso.

7. **Tipos TypeScript de las páginas se escriben a mano (no generados), espejando los Resources de Laravel.** El proyecto no tiene un generador de tipos PHP→TS; cada página define un `type` mínimo que refleja exactamente las claves que su Resource correspondiente produce (verificado contra el código PHP, no inferido).

## Risks / Trade-offs

- **[Riesgo] `checklist: null` cuando el proceso no tiene checklist generado todavía** (no todo `Proceso` tiene un `ChecklistDocumentalProceso` creado) → **Mitigación**: la página renderiza un estado vacío explícito ("Sin checklist generado aún"), nunca asume la forma del dato.
- **[Riesgo] Lista completa de casos en el formulario de creación de egreso puede crecer sin paginar** → **Mitigación**: aceptado por ahora (Non-Goal de filtros); si el volumen real lo amerita, es una mejora incremental posterior sin romper el contrato actual.
- **[Riesgo] Mostrar transiciones que el usuario no puede ejecutar puede confundir** → **Mitigación**: aceptado explícitamente (Non-Goals); el mensaje de error del backend ya es claro y específico.

## Migration Plan

Sin migraciones de base de datos. Cambios de código de presentación (React) y dos ajustes menores de Resource/controlador ya existentes. Requiere que Wayfinder regenere `resources/js/routes/pago-proveedores/...` (automático en `npm run dev`/`build`) antes de que las páginas puedan importar las funciones de ruta tipadas.

## Open Questions

Ninguna pendiente — alcance acotado explícitamente con el usuario (sin filtros, sin ocultar transiciones por permiso, sin reglas de elegibilidad de casos).
