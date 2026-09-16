## ADDED Requirements

### Requirement: Registrar el detalle de consumo vinculado a un caso de pago existente
El sistema SHALL permitir, a un usuario con el permiso `consumo_basico.crear`, registrar un `ConsumoBasico` (medidor, período de lectura, consumo, tarifa, montos) para un `CasoPagoProveedor` existente, exigiendo `cliente_medidor_id` y `caso_pago_proveedor_id`. Un `CasoPagoProveedor` SHALL tener a lo sumo un `ConsumoBasico` asociado.

#### Scenario: Registro exitoso
- **WHEN** un usuario con el permiso requerido registra el detalle de consumo para un `CasoPagoProveedor` que todavía no tiene ninguno, indicando el `ClienteMedidor` correspondiente
- **THEN** el sistema crea el `ConsumoBasico` vinculado a ambos

#### Scenario: Falta un campo obligatorio
- **WHEN** un usuario intenta registrar un `ConsumoBasico` sin `cliente_medidor_id`, `caso_pago_proveedor_id`, `numero_medidor`, `fecha_inicio_lectura`, `fecha_fin_lectura` o `monto_total`
- **THEN** el sistema rechaza el registro con un mensaje de validación, sin crear ningún registro

#### Scenario: El caso de pago ya tiene un detalle de consumo registrado
- **WHEN** un usuario intenta registrar un `ConsumoBasico` para un `CasoPagoProveedor` que ya tiene uno
- **THEN** el sistema rechaza la operación, sin crear un segundo registro

### Requirement: Reconocer automáticamente candidatos a detalle de consumo por proveedor
El sistema SHALL determinar si un `CasoPagoProveedor` es candidato a completar su detalle de consumo consultando si su `proveedor_id` tiene al menos un `ClienteMedidor` asociado, sin leer ni interpretar el contenido del documento/PDF del caso.

#### Scenario: El proveedor del caso tiene medidores registrados
- **WHEN** se consulta el detalle de un `CasoPagoProveedor` cuyo proveedor tiene uno o más `ClienteMedidor` asociados, y el caso todavía no tiene `ConsumoBasico`
- **THEN** el sistema lo señala como candidato a completar el detalle de consumo

#### Scenario: El proveedor del caso no tiene medidores registrados
- **WHEN** se consulta el detalle de un `CasoPagoProveedor` cuyo proveedor no tiene ningún `ClienteMedidor` asociado
- **THEN** el sistema no lo señala como candidato a detalle de consumo

### Requirement: Resolver o crear el ClienteMedidor al vuelo al registrar un consumo
El sistema SHALL permitir, al registrar un `ConsumoBasico`, seleccionar un `ClienteMedidor` existente por su `numero_cliente` o crear uno nuevo en la misma operación si ese número de cliente no está todavía en el catálogo.

#### Scenario: El número de cliente ya existe en el catálogo
- **WHEN** un usuario registra un `ConsumoBasico` indicando un `numero_cliente` que ya corresponde a un `ClienteMedidor` existente
- **THEN** el sistema vincula el `ConsumoBasico` a ese `ClienteMedidor` sin duplicarlo

#### Scenario: El número de cliente no existe todavía
- **WHEN** un usuario registra un `ConsumoBasico` indicando un `numero_cliente` que no existe en el catálogo, junto con los datos mínimos de un `ClienteMedidor` nuevo (proveedor, centro de costo, tipo de suministro)
- **THEN** el sistema crea el `ClienteMedidor` y lo vincula al `ConsumoBasico`, en la misma operación

### Requirement: Adjuntar el PDF de la boleta como evidencia del consumo registrado
El sistema SHALL permitir adjuntar el archivo PDF de la boleta/factura a un `ConsumoBasico`, reutilizando los modelos `Documento`/`VersionDocumento` (hash, metadata, trazabilidad) mediante una relación polimórfica `VinculoDocumento` propia de `ConsumoBasico`, independiente del árbol de `Proceso`.

#### Scenario: Adjuntar el PDF al registrar el consumo
- **WHEN** un usuario con permiso sube el archivo PDF de la boleta al registrar o editar un `ConsumoBasico`
- **THEN** el sistema crea el `Documento`/`VersionDocumento` correspondiente (con su hash) y lo vincula al `ConsumoBasico` mediante `VinculoDocumento`

#### Scenario: Consultar el documento adjunto
- **WHEN** un usuario con el permiso `consumo_basico.ver` consulta un `ConsumoBasico` que tiene un PDF adjunto
- **THEN** el sistema permite descargarlo

### Requirement: El historial de consumo se construye con los registros del propio sistema
El sistema SHALL exponer el historial de consumo de un `ClienteMedidor` como sus `ConsumoBasico` ordenados por período de lectura, sin parsear ni depender de ningún historial impreso en un PDF externo.

#### Scenario: Consultar el historial de un medidor
- **WHEN** un usuario con el permiso `consumo_basico.ver` consulta un `ClienteMedidor` con varios `ConsumoBasico` registrados en distintos períodos
- **THEN** el sistema los presenta ordenados cronológicamente por período de lectura

#### Scenario: Medidor sin consumos registrados todavía
- **WHEN** un usuario consulta un `ClienteMedidor` que todavía no tiene ningún `ConsumoBasico`
- **THEN** el sistema muestra el historial vacío, sin error

### Requirement: El vínculo de consumo no gobierna el estado de pago del caso
El sistema SHALL tratar el registro de un `ConsumoBasico` como puramente informativo respecto del `CasoPagoProveedor` al que se vincula: SHALL NOT inferir, cambiar ni disparar ninguna transición del estado de pago del caso a partir del contenido del PDF adjunto ni de los datos del consumo registrado.

#### Scenario: Registrar un consumo no altera el estado del caso de pago
- **WHEN** un usuario registra un `ConsumoBasico` para un `CasoPagoProveedor`
- **THEN** el estado del `Proceso`/workflow de ese `CasoPagoProveedor` no cambia como consecuencia de esa acción

### Requirement: Permisos del módulo Consumo Básico
El sistema SHALL exponer los permisos `consumo_basico.crear`, `consumo_basico.editar`, `consumo_basico.ver`, en convención `modulo_accion.verbo`, y SHALL condicionar toda acción de creación, edición y visualización de `ConsumoBasico` a que el usuario autenticado posea el permiso correspondiente.

#### Scenario: Usuario sin permiso de creación
- **WHEN** un usuario sin el permiso `consumo_basico.crear` intenta registrar un `ConsumoBasico`
- **THEN** el sistema rechaza la acción y registra el evento en `security_audit_logs`
