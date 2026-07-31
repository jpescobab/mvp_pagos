## Context

`procesos_adquisicion` está implementado y en uso: no es una tabla vacía, así que el diseño evita romper filas existentes. El usuario definió el formulario objetivo mediante un mockup HTML ("Solicitud de Compra") y cerró explícitamente, en la fase de propuesta, seis decisiones de arquitectura (ver `proposal.md`): este formulario reemplaza `procesos/crear.tsx`, es específico para compras < 1.000 UTM, el código se autogenera, la aprobación es una transición de workflow real (no texto libre), Convenio Marco deriva la modalidad, y `objeto` se mantiene sincronizado internamente.

Quedaban tres puntos técnicos abiertos que este documento resuelve con evidencia real del propio repo: el formato del correlativo, qué rol ejecuta la aprobación, y el mecanismo de exportación a PDF.

## Goals / Non-Goals

**Goals:**
- Capturar en `procesos_adquisicion` los antecedentes reales de una solicitud de compra menor a 1.000 UTM, con el funcionario requirente identificado (no texto libre).
- Derivar la modalidad desde una verificación de negocio (Convenio Marco) en vez de una elección manual desconectada de la realidad del proceso.
- Que la aprobación de la jefatura quede auditada por el motor de workflow ya existente, no por un campo de texto sin control.
- No romper `procesos_adquisicion` existentes ni sus consumidores (`ProcesoAdquisicionResource`, listados, tests, el vínculo con CDP y Mercado Público).

**Non-Goals:**
- Licitación Pública / Licitación Privada (≥ 1.000 UTM) — este cambio no toca ese flujo.
- Autorización de la aprobación *por unidad requirente específica* (que solo la jefatura de la unidad que solicitó pueda aprobar esa solicitud puntual). Ver "Decisión: alcance del permiso de aprobación" abajo.
- Scoping de `ccosto`/`funcionarios` por jerarquía institucional del usuario autenticado (nada en el repo lo hace hoy; no se introduce aquí).
- Eliminar `objeto` de la tabla.

## Decisions

### Formato del correlativo `codigo`: seguir el patrón ya usado por el CDP
`CrearBorradorCertificadoDisponibilidadService` (`app/Services/Presupuesto/CrearBorradorCertificadoDisponibilidadService.php:45-69`) ya resolvió este mismo problema para `certificados_disponibilidad_presupuestaria.folio`, con una lección documentada: un `SELECT MAX()+1` no serializa de forma segura contra inserciones concurrentes bajo `READ COMMITTED` en PostgreSQL (produjo folios duplicados en pruebas reales). Su solución: insertar con un valor temporal único (`'TEMP-'.Str::uuid()`), y una vez conocido el `id` autoincremental (único y atómico de forma nativa), fijar el código definitivo con un `update()` dentro de la misma transacción.

Se reutiliza exactamente ese patrón para `procesos_adquisicion.codigo`, con formato `sprintf('SPC-%03d-%d', $proceso->id, $anio)` (ej. `SPC-001-2026`, pedido explícitamente por el usuario) — mismo estilo que `CDP %03d-%d`. `$anio` se toma de `fecha_inicio`. Vive en `ProcesoAdquisicionService::crear()`, dentro de la transacción existente.

**Alternativas consideradas:** un contador separado (tabla de secuencias) — descartado por agregar una tabla y un punto de fallo nuevo cuando el `id` autoincremental ya resuelve la atomicidad de forma nativa, tal como probó el CDP.

### Alcance del permiso de aprobación: reusar `adquisiciones.publicar`, sin scoping por unidad
El mockup dice "la jefatura responsable de la Unidad Requirente aprueba", pero la "unidad requirente" es cualquiera de los centros de costo que pueden solicitar una compra (no solo Adquisiciones) — hoy no existe en el repo ningún mecanismo que identifique "la jefatura de tal `ccosto` específico" como identidad autorizable (`Funcionario` tiene `cargo` como texto libre, sin flag de jefatura ni FK a un rol por unidad). Construir eso es un cambio de modelo de autorización más grande que el alcance de este formulario.

Decisión: la aprobación reusa la transición `publicar` (`en_revision` → `publicada`) y su permiso ya existente `adquisiciones.publicar` (`database/seeders/WorkflowAdquisicionesSeeder.php`), sin scoping por unidad — cualquier usuario con ese permiso puede aprobar cualquier solicitud, igual que hoy nada en el repo escapa por jerarquía institucional (confirmado: `Ccosto::all()` sin filtro en los selects existentes). Se otorga `adquisiciones.publicar` también al rol `administrativo_adquisiciones`, no solo a `admin`: la nómina real ya sembrada (`database/seeders/FuncionariosCapjSeeder.php:39`) mapea el cargo real `JEFE SECCION ADQUISICIONES Y MANTENIMIENTO` a ese rol, así que sin este otorgamiento la jefatura real de Adquisiciones no podría aprobar nada hoy.

**Alternativas consideradas:**
- Nuevo rol `jefe_unidad_requirente` con scoping por `ccosto_id` — descartado para este cambio: requiere modelar "jefatura por unidad" desde cero (columna nueva en `Funcionario` o tabla de asignación), que es trabajo real no pedido explícitamente; queda como Open Question para una iteración futura si el usuario lo requiere.
- Campo de texto libre / selector informativo sin gate real — descartado explícitamente por el usuario en la fase de propuesta.

### Exportación a PDF: nuevo service con dompdf, sin nueva dependencia
`ExportadorInformeRazonadoService` (dominio Informes Razonados) ya usa `barryvdh/laravel-dompdf` para generar PDFs desde una vista Blade — es la única vía de generación ofimática del proyecto (CLAUDE.md). Se crea `app/Services/Adquisiciones/ExportadorSolicitudCompraPdfService.php`, que renderiza una vista Blade nueva (`resources/views/adquisiciones/solicitud-compra-pdf.blade.php`) con los antecedentes generales del `proceso_adquisicion` y su estado actual, y la convierte a PDF con dompdf — mismo mecanismo, sin agregar librerías. Se expone bajo demanda vía una ruta `GET` (no bloquea el `store()`), análogo a `ExportacionInformeRazonadoController::descargar()`.

**Alternativas consideradas:** generar el PDF de forma síncrona dentro de `store()` y devolverlo en la misma respuesta — descartado: acopla la creación (que debe ser rápida y resiliente) a la renderización de PDF; si el render falla, no debe impedir que la solicitud quede creada.

### `funcionario_requirente_id` valida pertenencia a la unidad elegida
La regla "el requirente pertenece a la unidad requirente" se valida en `CrearProcesoAdquisicionRequest`/`ActualizarProcesoAdquisicionRequest` con una regla `Rule::exists('funcionarios', 'id')->where('ccosto_id', $ccostoIdEnviado)`, evaluada en el `withValidator()` del FormRequest (necesita leer ambos campos del payload). Evita guardar una combinación inconsistente sin necesidad de un evento/observer nuevo.

## Risks / Trade-offs

- [Cualquier usuario con `adquisiciones.publicar` aprueba cualquier solicitud, no solo las de su propia unidad] → Mitigación: es el mismo nivel de control que ya existe hoy en el resto del sistema (ningún módulo escapa por jerarquía institucional); si se vuelve un problema real, es una iteración futura acotada (agregar `ccosto_id` a la verificación de permiso).
- [Migración sobre una tabla en uso: columnas nuevas nullable a nivel BD, `objeto` deja de ser NOT NULL] → Mitigación: ninguna columna nueva es `NOT NULL` a nivel de base de datos; lo requerido se exige solo en `FormRequest` para registros nuevos, así que filas existentes no se rompen ni necesitan backfill.
- [Rename `monto` → `monto_estimado` toca varios consumidores (`Resource`, `types/adquisiciones.ts`, `index.tsx`, `show.tsx`, tests)] → Mitigación: es un rename mecánico, se hace en una sola migración + un solo commit revisable; `composer ci:check` lo detecta si queda algo sin actualizar.
- [El informe de justificación (`INFORME_JUSTIFICACION_TRATO_DIRECTO`) se agrega a TODOS los procesos `trato_directo`, incluyendo los ya existentes creados antes de este cambio] → Mitigación: el checklist se resuelve en el momento de ver el detalle, no retroactivamente contra datos ya cerrados; procesos `trato_directo` ya `cerrada`/`contratada` simplemente mostrarán ese ítem como pendiente en su checklist histórico, sin bloquear nada (el checklist no gatea transiciones ya ejecutadas).

## Migration Plan

1. Migración aditiva sobre `procesos_adquisicion` (columnas nuevas nullable + rename `monto`→`monto_estimado` + `objeto` nullable). Reversible con `down()`.
2. Seeder: nuevo `TipoDocumento` `INFORME_JUSTIFICACION_TRATO_DIRECTO` + fila en `RequisitosDocumentalesAdquisicionesSeeder` para `trato_directo`; otorgar `adquisiciones.publicar` a `administrativo_adquisiciones` en `WorkflowAdquisicionesSeeder`.
3. Backend: modelo, FormRequests, Service (generación de código + derivación de modalidad + validación UTM), Controller, Resource.
4. Frontend: rediseño de `crear.tsx`/`editar.tsx`, tipos.
5. PDF: service + vista Blade + ruta de descarga + botón en `show.tsx`.
6. Tests actualizados y nuevos (ver `proposal.md` § Impact).

Rollback: la migración es aditiva y no destruye datos (ningún `DROP COLUMN` sobre datos que ya existían salvo el rename, reversible); si es necesario revertir, basta con revertir el deploy de código — el código anterior no referencia las columnas nuevas y sigue funcionando contra la tabla migrada.

## Open Questions

- Si más adelante se requiere que la aprobación quede restringida a la jefatura real de la unidad requirente específica (no solo el permiso genérico `adquisiciones.publicar`), habrá que modelar "jefatura por `ccosto`" — no está resuelto en este cambio, ver "Alcance del permiso de aprobación" arriba.
- Contenido exacto/diseño visual de la plantilla PDF (más allá de qué datos incluye) queda a criterio de implementación durante `/opsx:apply`, siguiendo el estilo ya usado por los PDF de `ExportadorInformeRazonadoService`.

### Decisión añadida durante la verificación manual: moneda y paridad del monto estimado

El usuario, probando el formulario ya implementado, pidió que el monto estimado siguiera exactamente la misma lógica de moneda/paridad que ya existe para el CDP (`presupuesto-certificado-disponibilidad`): elegir moneda (CLP/UF/USD); en CLP no hay paridad; en UF/USD se pide fecha de paridad y se resuelve contra `indicadores_economicos` (mismo `IndicadorEconomicoSelector::paraFecha()`); el monto final en CLP es paridad × monto solicitado.

Se replicó **exactamente** el patrón de `CrearBorradorCertificadoDisponibilidadService::resolverParidadYMonto()`: mismos nombres de columna (`moneda_compra`, `fecha_paridad`, `paridad`), mismo mecanismo de rechazo (`sinIndicadorParaFecha`), mismo endpoint de previsualización en vivo (`ParidadCdpController` → `ParidadAdquisicionController`, idéntico salvo el permiso gateado), mismo componente de UI (`cdp-form.tsx` → `campo-moneda-monto.tsx`, extraído como componente compartido entre `crear.tsx`/`editar.tsx`). Única diferencia de nombre: `total_moneda_compra` (CDP) se llamó `monto_estimado_solicitado` aquí, más claro en este contexto ya que `monto_estimado` (el resultado en CLP) ya existía con ese nombre desde antes en `procesos_adquisicion`.

No se tocó ningún archivo del dominio Presupuesto/CDP — el endpoint de previsualización se duplicó en vez de reutilizarse porque el gate de autorización difiere (`adquisiciones.crear_proceso` vs el permiso de CDP), evitando acoplar los dos dominios.
