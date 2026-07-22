## ADDED Requirements

### Requirement: Re-vincular un documento previamente desvinculado del checklist
La página de detalle de un caso SHALL permitir re-vincular un documento que fue desvinculado (cuyo `VinculoDocumento` quedó `activo=false`) sin exigir volver a subir el archivo. Los documentos con vínculo inactivo del proceso SHALL ofrecerse, junto a los documentos huérfanos, en el control "vincula uno ya importado" de un ítem pendiente del checklist, disponible solo para usuarios con el permiso `documentos.gestionar`. Al elegir uno de esos documentos re-vinculables, el sistema SHALL reactivar su vínculo (`activo=true`) y reclasificarlo al `tipo_documento_id` del ítem elegido, dentro de una operación de negocio del backend (no en el controlador ni en el cliente). El desvincular SHALL seguir siendo un soft-unlink que conserva el documento y su trazabilidad.

#### Scenario: Re-vincular un documento desvinculado lo reactiva
- **WHEN** un usuario con el permiso `documentos.gestionar` re-vincula, desde un ítem pendiente del checklist, un documento del proceso cuyo vínculo estaba `activo=false`
- **THEN** el vínculo de ese documento pasa a `activo=true` y su `tipo_documento` queda reclasificado al del ítem elegido
- **AND** el ítem del checklist deja de estar pendiente y muestra ese documento como su documento vigente

#### Scenario: Un documento desvinculado vuelve a estar disponible para re-vincular
- **WHEN** un usuario desvincula un documento de un ítem del checklist y luego visualiza un ítem pendiente
- **THEN** ese documento desvinculado aparece entre las opciones de "vincula uno ya importado"

#### Scenario: Sin permiso de gestión de documentos no se puede re-vincular
- **WHEN** un usuario sin el permiso `documentos.gestionar` intenta reactivar/re-vincular un documento desvinculado
- **THEN** la acción es denegada y el vínculo permanece inactivo

### Requirement: La vista previa del checklist sigue al documento vigente
La vista previa embebida de documentos en la página de detalle de un caso SHALL reflejar siempre un documento actualmente vinculado al proceso. Cuando el documento que se está previsualizando deja de estar vinculado activamente (por ejemplo, tras desvincularlo), la página SHALL limpiar la vista previa en vez de seguir mostrando ese documento. Al elegir "Ver" sobre un ítem del checklist con documento vinculado, la página SHALL mostrar el documento vigente de ese ítem.

#### Scenario: Desvincular el documento en vista previa limpia el visor
- **WHEN** un usuario tiene un documento en vista previa y desvincula ese mismo documento
- **THEN** la vista previa se limpia y deja de mostrar el documento desvinculado

#### Scenario: Ver un documento re-vinculado muestra el documento vigente
- **WHEN** un usuario re-vincula (o vincula) un documento a un ítem y elige "Ver" sobre ese ítem
- **THEN** la vista previa muestra el documento vigente de ese ítem, no uno previamente desvinculado

### Requirement: El checklist muestra el nombre real del archivo vinculado
Cada ítem del checklist documental con un documento vinculado SHALL mostrar, junto al tipo de documento, el nombre real del archivo del documento vigente. El backend SHALL entregar ese nombre por ítem (cruzando el documento vinculado del ítem con el nombre de archivo de su versión vigente); el frontend solo lo renderiza. Un ítem sin documento vinculado (pendiente) NO SHALL mostrar nombre de archivo.

#### Scenario: Un ítem con documento vinculado muestra el nombre del archivo
- **WHEN** un ítem del checklist tiene un documento vinculado (por ejemplo, `FAE-293819.pdf`)
- **THEN** la página muestra ese nombre de archivo junto al tipo de documento del ítem

#### Scenario: Un ítem pendiente no muestra nombre de archivo
- **WHEN** un ítem del checklist no tiene documento vinculado
- **THEN** el ítem no muestra ningún nombre de archivo

### Requirement: La cabecera del detalle identifica el N° DTE
La cabecera de la página de detalle de un caso SHALL identificar el valor `caso.numero` como el **N° DTE** (Documento Tributario Electrónico), que es lo que ese número representa según el origen SGF. El campo de cabecera que hoy muestra `caso.numero` bajo la etiqueta "Número SGF" SHALL usar la etiqueta "N° DTE"; no se agrega un campo adicional con el mismo valor. Cuando `caso.numero` sea nulo, el campo SHALL usar el fallback explícito de la cabecera.

#### Scenario: La cabecera muestra el N° DTE del caso
- **WHEN** un caso tiene `numero` no nulo (por ejemplo, `293819`)
- **THEN** la cabecera del detalle muestra ese número bajo la etiqueta "N° DTE"

#### Scenario: Un caso sin N° DTE usa el fallback
- **WHEN** un caso tiene `numero` nulo
- **THEN** la cabecera muestra el fallback explícito para "N° DTE" en vez de una celda vacía
