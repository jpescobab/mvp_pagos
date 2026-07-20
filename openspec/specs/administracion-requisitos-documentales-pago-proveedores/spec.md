# Spec: administracion-requisitos-documentales-pago-proveedores

## Purpose

Permitir administrar, sin tocar código ni correr seeders, los catálogos `TipoProcesoPago` y `TipoDocumento`, y la matriz que asigna la obligatoriedad documental (`RequisitoDocumental`) por tipo de proceso de pago dentro del conjunto de requisitos `pago_proveedores`. Reemplaza la necesidad de editar `RequisitosDocumentalesPagoProveedoresSeeder.php` para ajustes de este tipo.

## Requirements

### Requirement: Administrar el catálogo de tipos de proceso de pago
El sistema SHALL permitir, a un usuario con el permiso `pago_proveedores.administrar_requisitos_documentales`, crear, editar y activar/desactivar registros de `TipoProcesoPago` (código único, nombre, activo, y si requiere Traspaso (CGU)). El sistema SHALL rechazar la eliminación de un `TipoProcesoPago` que tenga `RequisitoDocumental` asociados. Un `TipoProcesoPago` nuevo SHALL crearse con `requiere_traspaso_cgu = true` por defecto, salvo que el usuario lo desmarque explícitamente.

#### Scenario: Crear un tipo de proceso de pago
- **WHEN** un usuario con el permiso requerido crea un `TipoProcesoPago` con un código único y un nombre
- **THEN** el sistema lo persiste con `activo = true` por defecto y queda disponible para clasificar casos y para la matriz de requisitos documentales

#### Scenario: Código duplicado es rechazado
- **WHEN** un usuario intenta crear un `TipoProcesoPago` con un código que ya existe (sin distinguir mayúsculas/minúsculas)
- **THEN** el sistema rechaza la operación con un error de validación

#### Scenario: Desactivar un tipo de proceso de pago en uso
- **WHEN** un usuario desactiva un `TipoProcesoPago` que ya está asignado a casos existentes
- **THEN** el sistema lo marca `activo = false` sin afectar los casos que ya lo tienen asignado, y deja de ofrecerlo como opción para casos nuevos

#### Scenario: Eliminar un tipo de proceso de pago con requisitos asociados es rechazado
- **WHEN** un usuario intenta eliminar un `TipoProcesoPago` que tiene `RequisitoDocumental` asociados
- **THEN** el sistema rechaza la eliminación e informa que existen requisitos documentales relacionados

#### Scenario: Usuario sin permiso no puede administrar tipos de proceso de pago
- **WHEN** un usuario sin el permiso `pago_proveedores.administrar_requisitos_documentales` intenta crear, editar o eliminar un `TipoProcesoPago`
- **THEN** el sistema bloquea la operación

#### Scenario: Un tipo de proceso de pago nuevo requiere Traspaso (CGU) por defecto
- **WHEN** un usuario con el permiso requerido crea un `TipoProcesoPago` sin especificar `requiere_traspaso_cgu`
- **THEN** el sistema lo persiste con `requiere_traspaso_cgu = true`

#### Scenario: Marcar un tipo de proceso de pago como que no requiere Traspaso (CGU)
- **WHEN** un usuario con el permiso requerido crea o edita un `TipoProcesoPago` marcando `requiere_traspaso_cgu` en `false`
- **THEN** el sistema lo persiste así

### Requirement: Administrar el catálogo general de tipos de documento
El sistema SHALL permitir, a un usuario con el permiso `core_institucional.administrar`, crear, editar y activar/desactivar registros de `TipoDocumento` (código único, nombre, descripción opcional, activo). El sistema SHALL rechazar la eliminación de un `TipoDocumento` que tenga `RequisitoDocumental` o `Documento` asociados.

#### Scenario: Crear un tipo de documento
- **WHEN** un usuario con el permiso requerido crea un `TipoDocumento` con un código único y un nombre
- **THEN** el sistema lo persiste con `activo = true` por defecto y queda disponible para clasificar documentos y para la matriz de requisitos documentales de cualquier módulo que use este catálogo

#### Scenario: Eliminar un tipo de documento con documentos vinculados es rechazado
- **WHEN** un usuario intenta eliminar un `TipoDocumento` que tiene `Documento` o `RequisitoDocumental` asociados
- **THEN** el sistema rechaza la eliminación e informa la relación que lo impide

#### Scenario: Usuario sin permiso no puede administrar tipos de documento
- **WHEN** un usuario sin el permiso `core_institucional.administrar` intenta crear, editar o eliminar un `TipoDocumento`
- **THEN** el sistema bloquea la operación

### Requirement: Asignar obligatoriedad documental por tipo de proceso de pago mediante una matriz
El sistema SHALL exponer, a un usuario con el permiso `pago_proveedores.administrar_requisitos_documentales`, una vista de matriz con los `TipoDocumento` activos como filas y los `TipoProcesoPago` activos como columnas (más una columna "Todos los tipos" que representa `tipo_proceso_pago_id = null`), donde cada celda permite fijar el estado obligatorio, opcional, o no aplica para esa combinación, dentro del conjunto de requisitos `pago_proveedores` exclusivamente. Un cambio en una celda SHALL crear, actualizar, o eliminar el `RequisitoDocumental` correspondiente de inmediato, sin afectar filas de otros conjuntos de requisitos documentales (ej. `adquisiciones`) ni las dimensiones `modalidad_id`, `estado_workflow_id`, `monto_desde`/`monto_hasta` (que la matriz siempre deja en `null`). Eliminar un `RequisitoDocumental` (celda marcada "no aplica") SHALL tener éxito incluso si existen `checklist_documental_proceso_items` cacheados que lo referencian, dado que esos items son un caché regenerable de la resolución del checklist, no evidencia.

#### Scenario: Marcar un documento como obligatorio para un tipo de proceso
- **WHEN** un usuario con el permiso requerido fija la celda de un `TipoDocumento` y un `TipoProcesoPago` como "obligatorio"
- **THEN** el sistema crea o actualiza un `RequisitoDocumental` con `tipo_requisito = 'obligatorio'` para esa combinación dentro del conjunto `pago_proveedores`
- **AND** el checklist documental de cualquier caso con ese tipo de proceso, al recargarse, refleja el nuevo requisito sin necesidad de un seeder ni un deploy

#### Scenario: Quitar un requisito marcando "no aplica"
- **WHEN** un usuario fija una celda como "no aplica" sobre una combinación que ya tenía un `RequisitoDocumental`
- **THEN** el sistema elimina esa fila de `requisitos_documentales`
- **AND** la eliminación tiene éxito aunque existan `checklist_documental_proceso_items` de casos ya resueltos que referencien ese `RequisitoDocumental` (se eliminan en cascada junto con la fila)

#### Scenario: Asignar un requisito universal vía la columna "Todos los tipos"
- **WHEN** un usuario fija la celda de un `TipoDocumento` en la columna "Todos los tipos" como "obligatorio"
- **THEN** el sistema crea un `RequisitoDocumental` con `tipo_proceso_pago_id = null`, que aplica a casos de cualquier tipo de proceso clasificado o sin clasificar

#### Scenario: La matriz no expone ni modifica requisitos de Adquisiciones
- **WHEN** un usuario visualiza o edita la matriz de requisitos documentales de Pago de Proveedores
- **THEN** el sistema filtra explícitamente por el conjunto de requisitos `pago_proveedores` y su definición de workflow, sin mostrar ni permitir modificar los `RequisitoDocumental` del conjunto `adquisiciones`

#### Scenario: Usuario sin permiso no puede editar la matriz
- **WHEN** un usuario sin el permiso `pago_proveedores.administrar_requisitos_documentales` intenta modificar una celda de la matriz
- **THEN** el sistema bloquea la operación
