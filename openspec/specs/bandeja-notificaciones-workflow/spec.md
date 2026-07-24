# bandeja-notificaciones-workflow Specification

## Purpose
Bandeja de notificaciones de workflow del usuario: conteo de no leídas compartido al frontend para el badge, listado bajo demanda al abrir la campana, marcar todas como leídas, y el contrato de que la notificación de transición lleve un payload legible (estados con nombre, descripción del proceso) y navegable (URL al detalle del sujeto), capturado como snapshot en el momento de la transición.
## Requirements
### Requirement: Exponer el conteo de notificaciones no leídas al frontend
El sistema SHALL compartir en cada request, para el usuario autenticado, el número de sus notificaciones no leídas, de modo que la interfaz pueda mostrar un indicador sin consultar la lista completa. El sistema SHALL NOT incluir la lista de notificaciones en los datos compartidos en cada request. El conteo SHALL reflejar de inmediato los cambios (nuevas notificaciones o marcado como leídas), sin caché intermedia.

#### Scenario: Usuario con notificaciones no leídas
- **WHEN** un usuario autenticado con notificaciones no leídas carga cualquier página
- **THEN** los datos compartidos incluyen el número de sus notificaciones no leídas

#### Scenario: Usuario sin notificaciones
- **WHEN** un usuario autenticado sin notificaciones no leídas carga cualquier página
- **THEN** el conteo compartido es cero

#### Scenario: El conteo no incluye notificaciones de otros usuarios
- **WHEN** existen notificaciones no leídas de varios usuarios
- **THEN** el conteo compartido a un usuario cuenta únicamente las suyas

### Requirement: Listar las notificaciones del usuario bajo demanda
El sistema SHALL exponer un endpoint autenticado que devuelva las notificaciones del usuario autenticado, ordenadas de la más reciente a la más antigua, acotadas a las más recientes. El endpoint SHALL devolver únicamente las notificaciones del usuario que lo solicita.

#### Scenario: Listar las notificaciones propias
- **WHEN** un usuario autenticado solicita su lista de notificaciones
- **THEN** el sistema devuelve sus notificaciones más recientes, de la más nueva a la más antigua, cada una con su contenido legible, si fue leída, y su fecha

#### Scenario: No se exponen notificaciones ajenas
- **WHEN** un usuario autenticado solicita su lista de notificaciones
- **THEN** la respuesta no incluye ninguna notificación de otro usuario

### Requirement: Marcar las notificaciones como leídas
El sistema SHALL permitir al usuario autenticado marcar como leídas todas sus notificaciones no leídas mediante un endpoint autenticado. La operación SHALL afectar únicamente las notificaciones del usuario que la solicita, y tras ella el conteo de no leídas de ese usuario SHALL ser cero.

#### Scenario: Marcar todas como leídas
- **WHEN** un usuario autenticado con notificaciones no leídas solicita marcarlas como leídas
- **THEN** todas sus notificaciones quedan marcadas como leídas
- **AND** su conteo de no leídas pasa a cero

#### Scenario: No afecta notificaciones de otros usuarios
- **WHEN** un usuario marca sus notificaciones como leídas
- **THEN** las notificaciones no leídas de otros usuarios permanecen sin cambios

### Requirement: La notificación de transición lleva un payload legible y navegable
La notificación de una transición de workflow SHALL guardar, en el momento de la transición, un contenido autocontenido: el nombre legible del estado anterior y del nuevo (no solo sus códigos), una descripción del proceso afectado y la URL de destino al detalle de su sujeto cuando exista. Ese contenido SHALL renderizarse y navegarse sin consultas adicionales y SHALL permanecer válido aunque el proceso cambie de estado después.

#### Scenario: Contenido legible de una transición
- **WHEN** ocurre una transición de workflow que notifica a un responsable
- **THEN** la notificación guardada incluye el nombre legible del estado nuevo, una descripción del proceso y, si el sujeto tiene detalle, la URL para abrirlo

#### Scenario: Navegar al proceso desde la notificación
- **WHEN** un usuario abre una notificación de transición cuyo proceso tiene un detalle
- **THEN** la interfaz ofrece navegar al detalle del sujeto de ese proceso

#### Scenario: Proceso sin detalle navegable
- **WHEN** el sujeto de un proceso notificado no tiene una página de detalle
- **THEN** la notificación se muestra igualmente con su descripción, sin enlace, sin error

### Requirement: Campana de notificaciones en el encabezado
El sistema SHALL mostrar en el encabezado de la aplicación un indicador de notificaciones que muestre el número de no leídas y, al activarse, un panel con las notificaciones recientes del usuario. Al abrirse el panel, el sistema SHALL marcar como leídas las notificaciones del usuario. El panel SHALL mostrar un estado vacío cuando el usuario no tiene notificaciones.

#### Scenario: Indicador con no leídas
- **WHEN** un usuario autenticado tiene notificaciones no leídas
- **THEN** el encabezado muestra la campana con el número de no leídas

#### Scenario: Abrir el panel marca como leídas
- **WHEN** el usuario abre el panel de notificaciones
- **THEN** el panel muestra sus notificaciones recientes
- **AND** el indicador de no leídas pasa a cero

#### Scenario: Sin notificaciones
- **WHEN** un usuario sin notificaciones abre el panel
- **THEN** el panel muestra un estado vacío
