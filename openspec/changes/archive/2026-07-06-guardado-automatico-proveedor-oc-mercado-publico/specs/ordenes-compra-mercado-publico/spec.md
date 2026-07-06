## MODIFIED Requirements

### Requirement: Verificar y vincular al proveedor emisor de la OC
El sistema SHALL resolver automáticamente al proveedor emisor de una OC nueva como parte de la misma operación transaccional de guardado, sin bloquear ni exigir un paso manual previo del usuario.

#### Scenario: El proveedor ya existe y está completo
- **WHEN** el proveedor emisor de una OC obtenida de la API ya existe en el catálogo de proveedores (identificado por su RUT normalizado) y sus datos ya están completos
- **THEN** el sistema vincula ese proveedor a la OC sin modificar ninguno de sus campos

#### Scenario: El proveedor ya existe pero tiene campos vacíos
- **WHEN** el proveedor emisor ya existe en el catálogo pero tiene campos vacíos que el payload de Mercado Público sí aporta (p. ej. `nombre`)
- **THEN** el sistema completa únicamente esos campos vacíos con los datos del payload, sin sobreescribir ningún campo que ya tenga un valor cargado

#### Scenario: El proveedor no existe
- **WHEN** el proveedor emisor de una OC obtenida de la API no existe en el catálogo de proveedores
- **THEN** el sistema crea el proveedor con los datos disponibles del payload (RUT normalizado y nombre) como parte de la misma transacción de guardado de la OC

#### Scenario: Falla el guardado de la OC tras resolver el proveedor
- **WHEN** la creación o actualización del registro `orden_compra_mercado_publico` falla después de haberse creado o actualizado el proveedor dentro de la misma operación
- **THEN** la transacción completa se revierte, incluyendo la creación o actualización del proveedor

#### Scenario: Override manual del proveedor
- **WHEN** el usuario indica explícitamente un `proveedor_id` distinto al detectado por RUT del payload
- **THEN** el sistema vincula la OC a ese proveedor indicado sin ejecutar la lógica de creación o completado automático

### Requirement: Guardar una OC nueva solo tras confirmación explícita del usuario
El sistema SHALL requerir una confirmación explícita del usuario antes de persistir una OC obtenida de la API de Mercado Público, y SHALL guardar en la misma operación transaccional: la resolución del proveedor emisor (creación o completado de campos vacíos, salvo override manual), la OC, sus ítems, y dejar vinculados el snapshot y la solicitud que la originaron.

#### Scenario: Confirmación de guardado
- **WHEN** un usuario confirma guardar una OC previamente mostrada como vista previa
- **THEN** el sistema resuelve el proveedor emisor (crea, completa campos vacíos, o usa el override manual indicado), crea el registro `orden_compra_mercado_publico`, sus `orden_compra_mercado_publico_items`, y lo asocia al `snapshot_datos_externos` y la `solicitud_api_externa` que la originaron, todo en una sola transacción
- **AND** el sistema informa el resultado de la operación sobre el proveedor (creado, actualizado, o sin cambios)

#### Scenario: Usuario no confirma
- **WHEN** un usuario descarta la vista previa de una OC sin confirmar el guardado
- **THEN** el sistema no persiste ningún registro de OC, de sus ítems, ni realiza ninguna operación sobre el catálogo de proveedores
