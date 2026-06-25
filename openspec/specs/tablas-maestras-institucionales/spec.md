# Spec: tablas-maestras-institucionales

## Requirement: Incorporar tablas institucionales desde el inicio

El sistema debe incorporar desde el inicio proveedores, funcionarios, clientes medidores, items, catálogos y asignaciones.

### Scenario: Crear maestro de proveedores

Given se registra un proveedor
When se guarda el registro
Then el sistema asigna `id` interno
And conserva `rutproveedor` como identificador institucional único
And permite asociarlo a casos de pago, documentos y reportes

### Scenario: Crear funcionario

Given se registra un funcionario
When se guarda el registro
Then el sistema conserva `rut` único
And permite relacionarlo opcionalmente con `users`
And lo asocia a centro de costo y centro financiero cuando corresponda

### Scenario: Registrar cliente medidor

Given existe un proveedor de servicio y un centro de costo
When se registra un cliente medidor
Then queda asociado a proveedor y centro de costo
And puede usarse en consumo eléctrico, reportes y trazabilidad
