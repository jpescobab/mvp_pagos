## Context

`storage/app/private/sgf-documentos/{sgfId}/` es donde el scraper SGF (`services/sgf-playwright/sgf-scraper.js`) guarda cada PDF que descarga. Normalmente cada archivo vuelve reportado en el array `documentos` de la respuesta al microservicio, y `ConectorSgfPlaywrightService::vincularDocumento()` crea su `Documento`/`VersionDocumento`/`VinculoDocumento`. Pero en `descargarDocumentosDeFila()` el `writeFile()` ocurre ANTES del `push()` al array de retorno — si algo falla en ese intervalo (ej. `popup.close()` lanza), el archivo queda escrito en disco pero nunca llega a Laravel, y por lo tanto nunca se crea su `Documento`. El usuario pidió que la app le facilite encontrar y vincular esos archivos sin buscarlos a mano en el servidor.

Un navegador no puede abrir el Explorador de Windows desde una página web (se descartó explícitamente con el usuario) — la solución real es que la propia aplicación liste esos archivos sueltos y ofrezca vincularlos con un clic, mismo patrón que el checklist ya usa para "documentos huérfanos" (documentos que existen en BD pero no calzan con ningún ítem del checklist) y "documentos revinculables" (vínculo inactivo).

## Goals / Non-Goals

**Goals:**
- Detectar, para un `CasoPagoProveedor` con `sgf_id`, qué archivos de `sgf-documentos/{sgfId}/` no tienen todavía una fila `VersionDocumento` correspondiente.
- Permitir vincular uno de esos archivos a un ítem del checklist con un clic, sin volver a subirlo — calculando su hash desde el archivo ya existente en disco (mismo nivel de trazabilidad que un archivo subido a mano).
- Revalidar en el backend, en cada vinculación, que la ruta pedida es realmente uno de los archivos sueltos vigentes de ese caso — nunca confiar en una ruta arbitraria enviada por el cliente.

**Non-Goals:**
- No se automatiza la vinculación (sigue siendo una acción manual del usuario, con el mismo criterio de "workflow antes que CRUD" — esto no toca ningún estado).
- No se corrige la causa raíz del archivo huérfano en `sgf-scraper.js` (el `push()` después del `writeFile()`) — eso es un fix aparte del conector, no de este change, que se enfoca en dar una salida a los huérfanos ya existentes o que puedan volver a aparecer.
- No se generaliza a otros dominios de `procesos/{proceso}/documentos` (Contratos, CDP, Informes) — la detección de "archivos sueltos" es específica de la convención de carpetas SGF; el endpoint de vincular sí es genérico a nivel de `Proceso`, pero nada más de este change es SGF-específico salvo la detección.

## Decisions

### 1. La detección de archivos sueltos vive en un servicio nuevo del dominio SGF, no en `GestorDocumentoProceso`
`GestorDocumentoProceso` es genérico a cualquier `Proceso` (Contratos, CDP, Pago de Proveedores…). La convención de carpeta `sgf-documentos/{sgfId}` es específica de SGF/Pago de Proveedores. Se crea `app/Services/Sgf/ArchivoSgfSueltoResolver.php` con un único método `disponibles(CasoPagoProveedor $caso): array` que:
1. Si `$caso->sgf_id` es null, devuelve `[]`.
2. Lista `Storage::disk('local')->files("sgf-documentos/{$caso->sgf_id}")`.
3. Excluye las rutas que ya existen en `versiones_documento.ruta_archivo` (consulta `whereIn`).
4. Devuelve `[{ruta_archivo, nombre_archivo}]` para el resto.

### 2. Vincular un archivo existente es un método genérico nuevo en `GestorDocumentoProceso`, reutilizando el mismo endpoint `procesos/{proceso}/documentos/*`
`vincularArchivoExistente(Proceso $proceso, string $rutaArchivo, TipoDocumento $tipoDocumento, User $usuario): VinculoDocumento` hace exactamente lo mismo que `subirYVincular()` pero sin `UploadedFile`: usa `Storage::disk('local')->size()`/`mimeType()` para los metadatos y `hash_file('sha256', Storage::disk('local')->path($rutaArchivo))` para el hash — el archivo no se mueve ni se duplica, `VersionDocumento::ruta_archivo` apunta directo a donde ya está. Vive en `GestorDocumentoProceso` (no en el resolver SGF) porque la operación en sí —crear `Documento`/`VersionDocumento`/`VinculoDocumento`— es idéntica sea cual sea el origen del archivo, y así queda disponible para cualquier `Proceso` que en el futuro tenga el mismo problema (no solo SGF).

### 3. Revalidación server-side obligatoria de la ruta (control de seguridad, no solo de negocio)
El endpoint `POST procesos/{proceso}/documentos/vincular-existente` NUNCA usa la `ruta_archivo` recibida del cliente tal cual contra el filesystem: primero vuelve a calcular `ArchivoSgfSueltoResolver::disponibles($proceso->sujeto)` y exige que la ruta recibida esté en ese conjunto. Esto cierra cualquier posibilidad de que un usuario autenticado pida vincular una ruta arbitraria fuera de la carpeta de su propio caso (path traversal / acceso a archivos de otro caso). Si `$proceso->sujeto` no es un `CasoPagoProveedor` (u otro dominio sin `sgf_id`), el conjunto de disponibles es vacío y la operación se rechaza.

### 4. El checklist ofrece los archivos sueltos en el mismo `Select` que ya usa para huérfanos/revinculables
Se evita agregar una sección de UI nueva: `ChecklistDocumentalCard` ya tiene, por cada ítem pendiente, un selector "vincula uno ya importado". Se le agrega una tercera fuente (`archivosSueltos`), distinguible por usar la `ruta_archivo` (string) como `value` en vez del `documento_id` (numérico) — el handler de "Vincular" en `casos/show.tsx` ya decide hoy entre `reactivarDocumento`/`vincularHuerfano` según el valor elegido; se le agrega una tercera rama `vincularArchivoSuelto` cuando el valor coincide con una ruta de la lista de sueltos.

## Risks / Trade-offs

- [Riesgo] Si dos casos distintos comparten por error el mismo `sgf_id` (no debería pasar, es `unique`), la revalidación seguiría siendo segura porque se calcula sobre `$proceso->sujeto` específico, no sobre un `sgf_id` suelto → sin mitigación adicional necesaria, ya cubierto por la unicidad existente de `casos_pago_proveedor.sgf_id`.
- [Riesgo] Un archivo suelto podría no ser en realidad un documento válido (ej. un archivo temporal o parcialmente descargado que quedó a medio escribir) → Mitigación: aceptado, el usuario ve el nombre del archivo antes de vincularlo y puede optar por no hacerlo; no se intenta validar contenido/integridad más allá de lo que ya hace el flujo de subida manual.
- [Riesgo] No se corrige la causa raíz en `sgf-scraper.js`, así que seguirán apareciendo archivos sueltos nuevos → aceptado como no-goal explícito; este change da una salida al problema ya recurrente sin tocar el scraper (cambio de mayor riesgo, ver `CALIBRACION.md`).

## Migration Plan

Sin migraciones de base de datos — el change es puramente de código (servicio nuevo, método nuevo, endpoint nuevo, UI extendida). Aplica de inmediato a los archivos huérfanos que ya existan hoy en cualquier carpeta `sgf-documentos/{sgfId}/`.

## Open Questions

- ¿El nombre `ArchivoSgfSueltoResolver` sigue la convención de sufijos del proyecto (Resolver = pregunta de negocio)? Se confirma en `/opsx:apply` contra el criterio ya usado (`CfinancieroPorDefectoResolver`, `ResolutorChecklistDocumentalProceso`) — puede terminar llamándose `ArchivosSgfSueltosResolver` en plural si calza mejor con lo que ya existe.
