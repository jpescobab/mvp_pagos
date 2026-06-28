## Context

`vinculos_documento` ya es polimórfico en la base de datos (`vinculable_type`/`vinculable_id`) y `EgresoCgu::vinculosDocumento()` ya existe — pero toda la capa HTTP de documentos (`DocumentoProcesoController`, `ValidacionDocumentoController`, `GestorDocumentoProceso::subirYVincular()`) está tipada explícitamente a `Proceso`. `EgresoCgu` tampoco tiene página de detalle: el `design.md` de `paginas-pago-proveedores` lo dejó fuera explícitamente ("no existe esa ruta en `api-pago-proveedores`").

## Goals / Non-Goals

**Goals:**
- Página de detalle de un egreso CGU (`egresos-cgu/{id}`).
- Permitir subir, descargar y desvincular documentos (comprobantes) sobre un `EgresoCgu`, reutilizando `GestorDocumentoProceso` en vez de duplicar su lógica.
- Mantener el patrón de autorización por Policy (no `Gate::authorize('permission.string')` plano) para que `Gate::after` siga registrando `acceso_denegado` correctamente.

**Non-Goals:**
- Validar/rechazar documentos de un egreso CGU, o subir nuevas versiones — se deja para un change posterior si se necesita, igual de incremental que como se hizo para `Proceso` (subir/vincular → validar → versionar, en tres changes separados).
- Generalizar el modelo documental a un mecanismo de rutas verdaderamente polimórfico (`{vinculable_type}/{vinculable_id}/documentos`). Exponer el tipo de modelo en la URL como string sería una superficie de ataque innecesaria (permitiría intentar vincular documentos a cualquier Eloquent model). Se prefieren rutas explícitas por entidad (`egresos-cgu/{egresoCgu}/documentos`, como ya existe `procesos/{proceso}/documentos`), cada una con su propio Policy check.
- Tocar el checklist documental — `EgresoCgu` no tiene `requisitos_documentales`; sus documentos son comprobantes libres, no checklist obligatorio.

## Decisions

**`GestorDocumentoProceso::subirYVincular()` acepta `Proceso|EgresoCgu` (union type), no una interfaz nueva.** Ambos modelos ya exponen `vinculosDocumento(): MorphMany` con la misma firma; el cuerpo del método solo llama a `$vinculable->vinculosDocumento()->create(...)`. Un union type de PHP puro es más simple que introducir un contrato (`interface Vinculable`) para dos implementaciones — evita abstracción prematura. Si aparece una tercera entidad vinculable, ahí sí se justifica la interfaz.

**Controlador nuevo (`DocumentoEgresoCguController`), no ensanchar `DocumentoProcesoController`.** Laravel resuelve route model binding implícito por el tipo concreto del parámetro del método; no hay forma limpia de que un mismo método sirva `{proceso}` y `{egresoCgu}` con binding automático sin arriesgar comportamiento no probado en union types de binding. Cada controlador es delgado (4 métodos, ~30 líneas) y delega toda la lógica real a `GestorDocumentoProceso`, que es donde vive la duplicación evitada.

**`EgresoCguPolicy::gestionarDocumentos()` delega en el permiso `documentos.gestionar`**, exactamente como `ProcesoPolicy::gestionarDocumentos()`. Mismo permiso transversal para ambas entidades — no se crea un permiso `egresos_cgu.gestionar_documentos` separado porque el dominio (documentos del expediente) es el mismo, solo la entidad vinculada cambia.

**`EgresoCguResource` gana `id` y un mapeo de `documentos` análogo al de `ProcesoResource::mapDocumentosVinculados()`** (sin el historial de validaciones, porque no hay validación de documentos de egreso en este change). Se reutiliza el mismo patrón de `array_values(...->all())` para satisfacer Larastan.

**La página `show.tsx` reutiliza el bloque de UI de documentos de `casos/show.tsx`** (subir, descargar, desvincular) sin el bloque de checklist ni el de validaciones, que no aplican a un egreso CGU.

## Risks / Trade-offs

- **[Riesgo] Dos controladores casi idénticos (`DocumentoProcesoController` vs `DocumentoEgresoCguController`)** → **Mitigación**: aceptado; ambos son intencionalmente delgados y delegan a `GestorDocumentoProceso`. Si aparece una tercera entidad vinculable, es la señal real para extraer un controlador base o un trait, no antes.
- **[Riesgo] `documentos.gestionar` ahora autoriza dos tipos de entidad distintos** → **Mitigación**: aceptado; es el mismo dominio de negocio (gestión documental del expediente), y separar el permiso por entidad no aporta control real hoy.

## Migration Plan

Sin migración de datos — solo nuevas rutas, un controlador nuevo, una ampliación de tipo en un método de servicio existente, y una página nueva. Sin cambios de esquema.
