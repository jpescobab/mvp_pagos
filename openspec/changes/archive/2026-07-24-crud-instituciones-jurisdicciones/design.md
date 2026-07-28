## Context

La jerarquía `instituciones -> jurisdicciones -> cfinancieros -> ccostos` ya está modelada en base de datos desde el change de core institucional: tres migraciones consecutivas (`2026_06_25_2232*`), con `codigo` único en las tres tablas, `activo` booleano por defecto en `true` y claves foráneas `restrictOnDelete` entre niveles. Los modelos `Institucion` y `Jurisdiccion` existen con sus relaciones (`hasMany`/`belongsTo`) y se siembran desde `CoreInstitucionalSeeder` (la institución CAPJ + Coyhaique) y `JurisdiccionesSeeder` (las 20 jurisdicciones nacionales).

Lo que falta es exclusivamente la capa de aplicación. `CfinancieroController` y `CcostoController` establecieron el patrón completo hace varios changes: controlador CRUD 1:1, `Gate::authorize` por método, Form Requests para validación, `JsonResource` hacia React, cuatro páginas Inertia (`index`/`show`/`create`/`edit`), policy registrada a mano en `AppServiceProvider::configureAuthorization()` y tests por operación en `tests/Feature/Maestros/`. Este change replica ese patrón dos niveles más arriba; no inventa nada nuevo.

Volumen real hoy: 1 institución, 20 jurisdicciones, 6 centros financieros (todos bajo Coyhaique). El techo realista a nivel nacional son ~20 jurisdicciones y decenas de centros financieros por jurisdicción.

Restricción de partida: el trait `RegistraAuditoria` —que este change necesita para que las mutaciones queden auditadas— vive en la rama `feature/auditar-crud-tablas-maestras` (PR #27), aún sin fusionar a `master`.

## Goals / Non-Goals

**Goals:**

- Que los dos niveles superiores de la jerarquía sean administrables desde la aplicación con el mismo permiso, el mismo patrón de código y la misma densidad visual que los dos inferiores.
- Que la jerarquía sea navegable en ambos sentidos: desde una institución bajar a sus jurisdicciones, desde una jurisdicción subir a su institución y bajar a sus centros financieros.
- Que eliminar un nodo con dependencias falle con un mensaje entendible, no con un error de restricción de clave foránea.
- Que toda mutación de estos dos niveles quede en `audit_logs` con usuario, entidad y diff.

**Non-Goals:**

- **No** se cambia el esquema: ninguna migración, ningún índice, ninguna columna nueva. La forma de la jerarquía (cuatro niveles, en ese orden) es fija por harness y no está en discusión.
- **No** se introducen permisos nuevos ni se modifica ningún seeder de roles.
- **No** se agrega filtrado por jurisdicción al listado de centros financieros (existe hoy solo búsqueda por código/nombre); el detalle de la jurisdicción cubre la necesidad de ver "qué cuelga de acá".
- **No** se implementa soft delete en estas dos tablas, ni reordenamiento, ni importación masiva, ni activación/desactivación en cascada hacia los hijos.
- **No** se toca el flujo de creación de centros financieros (su `<select>` de jurisdicción sigue igual).

## Decisions

### Rutas planas por entidad, no anidadas bajo el padre

`maestros/jurisdicciones` con un `<select>` de institución en los formularios, en vez de `maestros/instituciones/{institucion}/jurisdicciones`.

*Por qué*: es exactamente lo que hace `cfinancieros` respecto de su jurisdicción y `ccostos` respecto de su centro financiero. Anidar solo el nivel 2 rompería la simetría de los otros tres, obligaría a un breadcrumb distinto por nivel y complicaría el sidebar (un ítem "Jurisdicciones" necesita una URL propia que no dependa de qué institución esté seleccionada). Con una sola institución real, anidar además agregaría un salto de navegación sin información.

*Alternativa descartada*: rutas anidadas con `shallow` (index/create anidados, show/edit/update/destroy planos). Más idiomático en REST puro, pero introduce dos convenciones de ruta distintas dentro del mismo grupo `maestros.`, que hoy es uniforme.

### El código de jurisdicción sigue siendo único a nivel global

La validación usa `unique:jurisdicciones,codigo` (ignorando la propia al editar), no una unicidad compuesta `(institucion_id, codigo)`.

*Por qué*: es lo que declara la migración existente (`$table->string('codigo')->unique()`). Cambiarlo a unicidad compuesta sería más correcto en un mundo multi-institución, pero exige modificar una migración ya aplicada en los entornos de trabajo y no resuelve ningún problema real: hay una sola institución y los códigos de jurisdicción son la numeración nacional CAPJ (`00`–`18`, `99`), que es global por naturaleza. Validar en la aplicación algo distinto de lo que impone la base sería peor que ambas opciones.

*Alternativa descartada*: unicidad compuesta + migración. Se puede hacer más adelante si aparece una segunda institución; hoy sería complejidad sin caso de uso.

### Eliminación física con bloqueo previo por dependencias, resuelto en un método privado del controlador

`destroy()` consulta `->jurisdicciones()->exists()` (o `->cfinancieros()->exists()`) antes de borrar; si hay hijos, hace flash de error y `back()` sin tocar nada.

*Por qué en el controlador y no en un Service*: la regla de controladores livianos del harness exige extraer cuando hay `DB::transaction`, dos o más ramas condicionales de negocio, `whereHas` anidados o `app(Clase::class)`. Acá es una sola relación, un solo `exists()`, una sola rama — idéntico en forma a `CfinancieroController::relacionQueImpideEliminar()`, que ya es la referencia del dominio. Crear un `Resolver` para dos `if` divergiría del patrón vecino sin ganar nada; si en el futuro el bloqueo pasa a depender de varias relaciones o de reglas de estado, ahí corresponde extraerlo (y habrá que hacerlo también en los otros dos controladores, en un change propio).

*Por qué bloqueo previo y no capturar la excepción de la base*: `restrictOnDelete` produce un `QueryException` con texto de PostgreSQL que no se le puede mostrar a un usuario, y su forma difiere entre PostgreSQL (producción/local) y SQLite (tests). El chequeo explícito da un mensaje en español y es verificable igual en ambos motores.

*Por qué físico y no soft delete*: `instituciones` y `jurisdicciones` no tienen `deleted_at` y agregarlo es un cambio de esquema, explícitamente fuera de alcance. Además el bloqueo por dependencias ya impide el escenario peligroso: no se puede borrar un nodo del que cuelgue algo.

### Conteo de hijos en el listado, listado completo de hijos en el detalle

El índice de instituciones usa `withCount('jurisdicciones')` (una consulta agregada, no N+1). El detalle carga los hijos completos ordenados por código, sin paginar.

*Por qué sin paginar*: el techo real es ~20 jurisdicciones por institución y decenas de centros financieros por jurisdicción. Paginar dentro de una página de detalle agrega estado de URL y un componente más para un caso que no existe. Queda anotado como riesgo, con umbral concreto.

### Auditoría por trait, no por llamadas en el controlador

`Institucion` y `Jurisdiccion` usan `RegistraAuditoria`, igual que las otras nueve tablas maestras.

*Por qué*: el trait ya engancha `created`/`updated`/`deleted` de Eloquent, arma el diff con `getOriginal()`/`getChanges()`, respeta la convención `<verbo>_<entidad>` y no audita sin usuario autenticado — así los seeders (`JurisdiccionesSeeder` siembra 20 filas) no inundan `audit_logs`. Agregar dos modelos al trait es una línea por modelo y los controladores quedan CRUD puro.

*Consecuencia sobre la rama base*: este change se implementa sobre una rama que contenga `RegistraAuditoria`, es decir a partir de `feature/auditar-crud-tablas-maestras` o de `master` una vez fusionado el PR #27. Implementarlo sobre `master` tal como está hoy dejaría los dos modelos sin auditar y las tareas correspondientes sin poder completarse.

### Listado denso, igual que cfinancieros

Ambos índices siguen el requirement "Listados tabulares densos" de `tema-visual-layout`, con `resources/js/pages/maestros/cfinancieros/index.tsx` como implementación de referencia: búsqueda con debounce de 300 ms, badge de estado con tokens `success`/`danger`, columna de entidad relacionada (institución en jurisdicciones; cantidad de jurisdicciones en instituciones), fallback `"—"` en la descripción nula y menú de acciones en dropdown.

## Risks / Trade-offs

- **Una jurisdicción con muchos centros financieros hace pesado su detalle** → el techo real son decenas de filas; si alguna jurisdicción supera ~100 centros financieros, corresponde paginar esa sección o reemplazarla por un enlace al listado de centros financieros filtrado por jurisdicción (filtro que hoy no existe y quedaría como change aparte).

- **Poder editar el código de una institución o jurisdicción ya sembrada** → los códigos son la numeración institucional oficial y cambiarlos altera el significado de datos históricos. Mitigación: toda edición queda auditada con diff en `audit_logs`, que es exactamente el mecanismo que el harness define para esto; no se agrega un bloqueo adicional porque corregir un código mal cargado es un caso de uso legítimo.

- **Desactivar (`activo = false`) una institución o jurisdicción no propaga nada hacia abajo** → sus centros financieros y centros de costo siguen activos y utilizables. Es coherente con cómo se comporta hoy `activo` en el resto de las tablas maestras (es una marca informativa, no una regla que filtre operaciones), pero puede sorprender a quien espere una desactivación en cascada. Fuera de alcance por diseño; si se quiere que `activo` gobierne selectores y filtros, es un change transversal propio sobre los cuatro niveles.

- **Dependencia de un PR sin fusionar** → si el PR #27 se revierte o cambia el nombre del trait, las dos tareas de auditoría de este change quedan sin base. Mitigación: son dos tareas aisladas al final del plan; el resto del change (CRUD, pantallas, tests) no depende de ellas.

## Migration Plan

No hay migración de datos ni de esquema. El despliegue es código de aplicación más `php artisan wayfinder:generate --with-form` y `npm run build`. El rollback es revertir el commit: las tablas y sus datos quedan intactos porque nunca se tocan; solo desaparecen las rutas y pantallas nuevas.

## Open Questions

Ninguna bloqueante. Queda anotado para el futuro, sin decisión pendiente en este change: si alguna vez existe una segunda institución, habrá que revisar la unicidad global del código de jurisdicción y evaluar el filtro por institución en los listados de niveles inferiores.
