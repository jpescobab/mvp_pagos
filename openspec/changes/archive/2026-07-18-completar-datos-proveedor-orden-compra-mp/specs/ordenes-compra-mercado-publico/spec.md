## MODIFIED Requirements

### Requirement: Verificar y vincular al proveedor emisor de la OC
El sistema SHALL resolver automáticamente al proveedor emisor de una OC nueva como parte de la misma operación transaccional de guardado, sin bloquear ni exigir un paso manual previo del usuario. La resolución SHALL poblar y completar el proveedor con todos los campos que el payload de Mercado Público aporta y para los que el catálogo de proveedores tiene columna (RUT, nombre, dirección, comuna, región, giro, correo y datos de contacto), normalizando a nulo los valores que Mercado Público entrega vacíos o con solo espacios. Cuando el payload no aporta un RUT de proveedor identificable y el usuario no indica un override, el sistema SHALL rechazar el guardado de esa OC con un mensaje claro, sin crear un proveedor con RUT vacío ni abortar la operación por una violación de unicidad.

#### Scenario: El proveedor ya existe y está completo
- **WHEN** el proveedor emisor de una OC obtenida de la API ya existe en el catálogo de proveedores (identificado por su RUT normalizado) y sus datos ya están completos
- **THEN** el sistema vincula ese proveedor a la OC sin modificar ninguno de sus campos

#### Scenario: El proveedor ya existe pero tiene campos vacíos
- **WHEN** el proveedor emisor ya existe en el catálogo pero tiene campos vacíos que el payload de Mercado Público sí aporta (p. ej. `nombre`, `direccion`, `comuna`, `region`, `giro`, `correo` o datos de contacto)
- **THEN** el sistema completa únicamente esos campos vacíos con los datos del payload, sin sobreescribir ningún campo que ya tenga un valor cargado

#### Scenario: El proveedor no existe
- **WHEN** el proveedor emisor de una OC obtenida de la API no existe en el catálogo de proveedores
- **THEN** el sistema crea el proveedor con todos los datos disponibles del payload (RUT normalizado, nombre, dirección, comuna, región, giro, correo y datos de contacto), como parte de la misma transacción de guardado de la OC
- **AND** los campos que el payload entrega vacíos o con solo espacios se guardan como nulos, no como cadenas vacías

#### Scenario: El payload no trae un RUT de proveedor identificable
- **WHEN** se intenta guardar una OC cuyo payload no aporta un RUT de proveedor identificable y el usuario no indicó un `proveedor_id` de override
- **THEN** el sistema rechaza el guardado con un mensaje claro
- **AND** no crea ningún proveedor con RUT vacío ni persiste la OC

#### Scenario: Falla el guardado de la OC tras resolver el proveedor
- **WHEN** la creación o actualización del registro `orden_compra_mercado_publico` falla después de haberse creado o actualizado el proveedor dentro de la misma operación
- **THEN** la transacción completa se revierte, incluyendo la creación o actualización del proveedor

#### Scenario: Override manual del proveedor
- **WHEN** el usuario indica explícitamente un `proveedor_id` distinto al detectado por RUT del payload
- **THEN** el sistema vincula la OC a ese proveedor indicado sin ejecutar la lógica de creación o completado automático
