## Why

El chunk `wayfinder-*.js` es el JavaScript más pesado del build (316 KB / ~96 KB gzip, más grande que el propio `app-*.js`), y se descarga en **todas** las páginas porque `app-sidebar.tsx` lo arrastra al grafo de imports estático de `app.tsx` vía `AppLayout`. La causa raíz: `app-sidebar.tsx` importa 18 módulos de rutas generados por Wayfinder (uno por dominio del menú) usando el export por defecto (`import proveedores from '@/routes/maestros/proveedores'`), que es un objeto armado con `Object.assign` que agrupa **todos** los métodos del controlador (`index`, `create`, `store`, `show`, `edit`, `update`, `destroy` y sus variantes `.form`). Rollup no puede hacer tree-shaking de propiedades no usadas de un objeto así, aunque el sidebar solo llame `.index()` en los 18 casos — verificado uno por uno. El resto del código base (topbar, menú de usuario, layouts de auth/settings) ya usa el patrón correcto de import con nombre (`import { dashboard } from '@/routes'`), que sí es tree-shakeable; solo `app-sidebar.tsx` es la excepción.

## What Changes

- Cambiar los 18 imports de módulos de ruta en `app-sidebar.tsx` de import por defecto (objeto completo) a import con nombre de únicamente `index` (la única función que el sidebar usa de cada módulo), siguiendo el mismo patrón ya usado en `app-header.tsx` y `user-menu-content.tsx`.
- Ningún archivo generado por Wayfinder se modifica ni se regenera; el cambio es exclusivamente en cómo se importa desde `app-sidebar.tsx`.
- Sin cambio de comportamiento observable: los mismos 18 `href` del menú deben apuntar exactamente a las mismas URLs.

## Capabilities

### New Capabilities
(ninguna)

### Modified Capabilities
- `tema-visual-layout`: agrega un requirement nuevo sobre cómo el sidebar debe importar las funciones de ruta de Wayfinder (con nombre, no el objeto completo), para que quede documentado y verificable que la navegación principal no debe volver a arrastrar métodos no usados al bundle universal. No cambia ningún requirement existente de ese spec (la agrupación, el estado activo, etc. siguen igual).

## Impact

- `resources/js/components/app-sidebar.tsx`: 18 imports cambian de `import X from '@/routes/...'` a `import { index as X } from '@/routes/...'`, y las 18 llamadas `X.index()` pasan a `X()`.
- Ningún cambio de backend, rutas Laravel, ni de otros componentes React.

## Resultado medido (post-implementación)

La hipótesis de reducción de bundle **no se confirmó**: tras el cambio, `wayfinder-DT4hJ1Ck.js` quedó exactamente igual (316.12 kB / gzip 99.69 kB, mismo hash de contenido) y `app-*.js` prácticamente igual (228.40 → 228.28 kB). Dos correcciones al diagnóstico original:

1. `wayfinder-*.js` no contiene los módulos de rutas de Wayfinder — contiene el runtime de Inertia.js/React (cadenas como `X-Inertia`, `data-inertia`), código de framework inevitable en todas las páginas.
2. Los módulos de rutas de Wayfinder sí viven en `app-*.js`, pero no se pudieron tree-shakear solo cambiando el import del sidebar: cada archivo generado ata sus métodos (`create`, `store`, etc.) al objeto exportado vía asignaciones a nivel de módulo, y en cuanto **cualquier** otro consumidor en la app (p. ej. una página `create.tsx` que usa `proveedores.store()`) importa algo de ese mismo archivo, Rollup conserva el archivo completo — sin importar cómo lo importe el sidebar.

Se mantiene el cambio de todas formas por ser más consistente con el patrón de import ya usado en `app-header.tsx`/`user-menu-content.tsx` y no tener ningún efecto negativo, pero **no debe presentarse como una optimización de rendimiento lograda**. Reducir el tamaño real de `app-*.js` requeriría tocar cómo Wayfinder genera el código (fuera de este change) o un helper de rutas manual solo para el sidebar (contradice la convención del proyecto).
