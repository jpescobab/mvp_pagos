## Context

El panel "Preparación para Asignar Egreso" del detalle de un caso de pago a proveedor (`resources/js/components/pago-proveedores/preparacion-egreso-card.tsx`) calcula sus 4 criterios en el frontend, en una función (`calcularPreparacionEgreso()`) que el propio código admite ser una "réplica manual" de `app/Services/PagoProveedores/ListoParaEgresoResolver.php` (la fuente de verdad real, usada en `ImportacionSgfResource` y `CasosElegiblesEgresoCguService`, pero nunca invocada desde esta página).

Esa réplica diverge del backend en el criterio "Checklist documental": exige `itemsObligatorios.length > 0` además de que todos tengan documento vinculado; el backend usa solo `every(...)` sobre los ítems obligatorios, que en Laravel es verdad vacua sobre una colección vacía — ya estaba correcto, sin ese resguardo extra. Se confirmó contra la base de datos real que el tipo de proceso "Remesa" (ya configurado vía el admin de matriz de requisitos documentales, sin exigir el tipo de documento `TRASPASO_CGU`) resuelve hoy a un checklist con cero ítems obligatorios — y por el bug del frontend, cualquier caso de ese tipo queda atascado mostrando "Sin checklist generado" y oculta el acceso directo para crear un Egreso CGU, aunque el backend ya lo consideraría satisfecho.

## Goals / Non-Goals

**Goals:**
- Eliminar la duplicación de lógica entre frontend y backend para los 4 criterios del panel, de raíz (una sola fuente de verdad), no con un parche puntual.
- Corregir el criterio "Checklist documental" para que un checklist resuelto sin ítems obligatorios cuente como cumplido, igual que ya lo hace `ListoParaEgresoResolver`.
- Que el fix sea general (cualquier tipo de proceso de pago con checklist de cero obligatorios), no un caso especial para "Remesa".

**Non-Goals:**
- No cambiar la configuración de documentos requeridos por tipo de proceso (`RequisitoDocumental`, seeders) — la configuración de Remesa ya es correcta.
- No crear un flujo o `TipoProcesoPago` distinto para remesas — el mecanismo genérico ya soporta esta variación.
- No tocar `ImportacionSgfResource` ni `CasosElegiblesEgresoCguService` — ya usan `ListoParaEgresoResolver` correctamente y se benefician del fix sin cambios propios.

## Decisions

1. **Nuevo `PreparacionEgresoPresenter` como única fuente de los 4 criterios**, siguiendo la convención de nombrado ya establecida en el dominio (`Presenter` = payload de una pantalla, `Resolver` = pregunta booleana). `ListoParaEgresoResolver::resuelve()` pasa a delegar en él (`every(cumplido)` sobre sus 4 criterios) en vez de mantener un cálculo paralelo — es un refactor DRY sin cambio de comportamiento para los casos que el Resolver ya cubre correctamente.

2. **El backend envía los 4 criterios ya resueltos; el frontend deja de recalcularlos.** Alternativa descartada: parchear solo `calcularPreparacionEgreso()` en el frontend para que coincida con el backend hoy. Se descarta porque no resuelve la causa raíz (dos copias de la misma lógica) — el mismo tipo de divergencia podría repetirse en el futuro si cualquiera de las dos copias cambia sin la otra. Moverlo al backend dejando el frontend "tonto" (solo pinta lo que recibe) elimina esa posibilidad estructuralmente.

3. **Exposición opt-in vía wither en `CasoPagoProveedorResource`** (`withPreparacionEgreso()`), no incondicional. El listado paginado de casos no carga las relaciones que este cálculo necesita (`proceso.checklist.items`, `proceso.tipoProcesoPago`, `registrosContablesCgu`); activarlo ahí sin querer introduciría un N+1 por fila. Solo `CasoPagoProveedorController::show()` lo activa.

4. **El criterio "Checklist documental" distingue 3 estados, no 2**: checklist nunca resuelto (`cumplido: false`, "Sin checklist generado" — sin cambios), checklist resuelto sin obligatorios (`cumplido: true`, nuevo texto "Sin ítems obligatorios" — antes indistinguible del caso anterior, ese era el bug), checklist resuelto con obligatorios (sin cambios). Distinguir estos dos últimos casos con detalle textual distinto evita que "cumplido sin nada que mostrar" se lea como "no se generó nada".

## Risks / Trade-offs

- **[Riesgo]** Algún test existente podría depender implícitamente de la forma actual de `caso.preparacion_egreso` (que hoy no existe como prop del backend, solo se computaba en cliente). → Mitigación: es un campo nuevo y aditivo (ninguna clave existente cambia de forma); se revisan explícitamente los tests de `tests/Feature/PagoProveedores/*casos.show*` antes de dar el cambio por completo.
- **[Riesgo]** Cambiar `ListoParaEgresoResolver` para inyectar `PreparacionEgresoPresenter` podría romper algún lugar que lo instancie manualmente con `new`. → Mitigación: verificado que no existe ningún `new ListoParaEgresoResolver(...)` en el código; ambos call sites resuelven vía el contenedor, que autoresuelve la nueva dependencia sin cambios de service provider.
- **[Trade-off]** El slug `criterio` (`tipo_proceso`, `traspaso_cgu`, `checklist_documental`, `proveedor`) es una clave nueva no presente en la implementación actual del frontend (que usaba la etiqueta traducible como key de React). Es una mejora menor incluida de paso, no un cambio de alcance — evita un key inestable si alguna vez cambia el texto de la etiqueta.
