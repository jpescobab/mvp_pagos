## Context

`app-sidebar.tsx` importa 18 módulos generados por Wayfinder (uno por dominio del menú lateral) con el export por defecto:

```ts
import proveedores from '@/routes/maestros/proveedores';
// ...
href: proveedores.index()
```

Cada módulo generado exporta, además de las funciones con nombre (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy`), un default que las agrupa todas en un objeto vía `Object.assign` (ver `resources/js/routes/maestros/proveedores/index.ts:597-604`). Importar ese default y acceder a `.index()` impide que Rollup elimine por tree-shaking las demás propiedades del objeto, aunque nunca se usen desde el sidebar. Como `app-sidebar.tsx` se carga en toda página autenticada (vía `AppLayout`, importado de forma estática por `app.tsx`), los 18 módulos completos — con sus 6-7 métodos cada uno — terminan en el chunk `wayfinder-*.js` que se descarga siempre, sea cual sea la página visitada.

El resto de la base de código ya usa el patrón correcto de import con nombre (`app-header.tsx`, `user-menu-content.tsx`, layouts de auth/settings), que sí es tree-shakeable.

## Goals / Non-Goals

**Goals:**
- Que `app-sidebar.tsx` importe únicamente la función `index` de cada uno de los 18 módulos de ruta, permitiendo que Rollup elimine el resto de métodos no usados del bundle universal.
- Mantener exactamente los mismos 18 `href` del menú (misma URL, mismo comportamiento).

**Non-Goals:**
- No se modifican los archivos generados por Wayfinder (`resources/js/routes/**`, `resources/js/actions/**|`); ese es el output de `php artisan wayfinder:generate` y no debe tocarse a mano.
- No se cambia la lógica de permisos ni qué ítems del menú se muestran a cada usuario.
- No se mide ni se compromete un porcentaje exacto de reducción de bundle; se verifica con el build real tras el cambio (ver Risks).

## Decisions

1. **Import con nombre de solo `index`, no un helper `.url()` alternativo.** Se usa `import { index as proveedoresIndex } from '@/routes/maestros/proveedores'` y luego `proveedoresIndex()` en vez de introducir una abstracción nueva (p. ej. un helper que solo extraiga la URL). Razón: es exactamente el mismo patrón que ya usan `app-header.tsx` y `user-menu-content.tsx`, consistente con la convención de Wayfinder del proyecto (CLAUDE.md: "usar Wayfinder... en vez de hardcodear URLs"), y no agrega ninguna capa nueva.
2. **No renombrar todas las variables al mismo nombre corto.** Se conserva el nombre local ya usado en el archivo (`proveedores`, `casos`, `ccostos`, etc.) vía `import { index as proveedores } from '...'`, para minimizar el diff y no tocar las 18 líneas de JSX que ya usan esos nombres — solo cambia `proveedores.index()` por `proveedores()` y la línea de import.
3. **No tocar `resources/js/actions/**`.** El sidebar solo construye `href` (GET), nunca envía formularios, así que no hay ninguna razón para importar del árbol de `actions`; se confirma que ningún ítem del menú usa `create`/`store`/`update`/`destroy` hoy.

## Risks / Trade-offs

- **[Riesgo]** El nombre exportado podría no ser literalmente `index` en algún módulo si el controlador correspondiente no expone ese método con ese nombre. → **Mitigación**: se verificó previamente que las 18 líneas de uso en `app-sidebar.tsx` llaman `.index()` en los 18 casos; se confirma de nuevo al aplicar cada import.
- **[Riesgo]** El tamaño real del chunk tras el cambio depende de cómo Rollup decida re-agrupar los chunks restantes (podría seguir habiendo un chunk compartido, solo que más chico). → **Mitigación**: correr `npm run build` tras el cambio y comparar el tamaño de los assets generados contra el build actual, en vez de asumir un número exacto de antemano.
- **[Riesgo]** Cambiar el patrón de import podría romper el tipado si algún otro lugar del archivo usa el objeto completo (p. ej. `proveedores.show()`) sin que se haya detectado en la revisión. → **Mitigación**: `npm run types:check` después del cambio, y grep de cada nombre de variable en todo el archivo antes de tocarlo.

## Migration Plan

Sin migraciones de base de datos. Cambio de código puro en un solo archivo frontend; revertir el commit es suficiente si algo sale mal. Se recomienda correr `npm run build` antes/después para comparar tamaños de bundle como evidencia del efecto.

## Open Questions

Ninguna bloqueante para implementar.
