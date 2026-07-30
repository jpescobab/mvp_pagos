# Spec: presupuesto-importacion-cgu

## Purpose

Importar y conservar con evidencia (snapshot) el presupuesto asignado que CGU formula y aprueba,
entregado hoy como reporte Excel manual, para dejar líneas de presupuesto (cfinanciero × cuenta ×
plan de tarea × año) contra las cuales el módulo de Certificado de Disponibilidad Presupuestaria
(CDP, siguiente change de la secuencia) pueda comprometer y controlar saldo.

## Requirements

### Requirement: Importar el presupuesto asignado desde el Excel de CGU con snapshot
El sistema SHALL importar el presupuesto asignado desde un archivo Excel exportado por CGU,
usando la columna `Ppto.Vigente` como monto asignado de cada línea, y SHALL conservar un
`snapshot_datos_externos` inmutable del archivo original (payload crudo, hash, método de captura
`excel`, usuario responsable) vinculado a la importación, vía el sistema externo `CGU` y la capa
transversal de integraciones ya existente.

#### Scenario: Importar un Excel válido
- **WHEN** un usuario con permiso `presupuesto.importar` sube un Excel de CGU con el formato de
  columnas esperado
- **THEN** el sistema crea una `importacion_presupuesto` con estado `completado` y sus totales
  (filas recibidas, creadas, omitidas, fallidas)
- **AND** crea o actualiza una línea de `presupuesto` por cada fila válida, usando `Ppto.Vigente`
  como `monto_asignado`
- **AND** conserva un `snapshot_datos_externos` del archivo original con su hash y usuario
  responsable

#### Scenario: Excel con formato de columnas inesperado
- **WHEN** un usuario sube un archivo cuyas columnas no calzan con el formato esperado de CGU
- **THEN** el sistema rechaza la importación antes de crear ninguna línea de presupuesto
- **AND** informa qué columna o encabezado no pudo reconocer

### Requirement: Mantener el catálogo de planes de tarea
El sistema SHALL mantener un catálogo `planes_tarea` (`codigo`, `nombre`, `activo`) independiente
del clasificador presupuestario (`items`/`asignaciones`/`catalogos`), poblado por upsert desde el
Excel de CGU, dado que un mismo plan de tarea puede combinarse con más de una cuenta
presupuestaria y viceversa.

#### Scenario: Un plan de tarea nuevo aparece en la importación
- **WHEN** una fila del Excel referencia un plan de tarea cuyo `codigo` no existe todavía
- **THEN** el sistema crea el `plan_tarea` correspondiente
- **AND** lo asocia a la línea de presupuesto de esa fila

#### Scenario: Un plan de tarea ya existente se reutiliza
- **WHEN** dos filas del Excel referencian el mismo `codigo` de plan de tarea bajo cuentas
  presupuestarias distintas
- **THEN** el sistema reutiliza el mismo `plan_tarea` para ambas líneas de presupuesto
- **AND** no crea un `plan_tarea` duplicado

### Requirement: Modelar las líneas de presupuesto por cfinanciero, cuenta, plan de tarea y año
El sistema SHALL modelar cada línea de presupuesto (`presupuestos`) como la combinación única de
`cfinanciero_id` (derivado de `U.Ejecutora`), `catalogo_id` (cuenta presupuestaria), `plan_tarea_id`
y `anio`, con su `monto_asignado`.

#### Scenario: Registrar una línea de presupuesto nueva
- **WHEN** se importa una fila del Excel cuya combinación de cfinanciero, cuenta, plan de tarea y
  año no existe todavía
- **THEN** el sistema crea una `presupuesto` con esos cuatro campos como identidad y el
  `monto_asignado` de la fila

#### Scenario: La cuenta de una fila no existe en el clasificador
- **WHEN** una fila del Excel referencia un código de cuenta presupuestaria que no existe en
  `catalogos`
- **THEN** el sistema NO crea la línea de presupuesto para esa fila
- **AND** la registra como fila omitida en los totales de la `importacion_presupuesto`, con el
  código de cuenta no reconocido

### Requirement: Reimportar una versión nueva del presupuesto sin perder historial
El sistema SHALL permitir reimportar un Excel de CGU con una `Nro.Versión` distinta para el mismo
año, actualizando el `monto_asignado` de las líneas de presupuesto existentes por upsert, sin
eliminar ni sobrescribir el snapshot de importaciones anteriores.

#### Scenario: Reimportar una versión posterior del mismo año
- **WHEN** se importa un Excel de CGU con `Nro.Versión` mayor a la última importada para ese año
- **THEN** el sistema actualiza `monto_asignado` en las líneas de presupuesto ya existentes que
  coincidan en cfinanciero, cuenta, plan de tarea y año
- **AND** conserva sin cambios los snapshots de las importaciones anteriores

### Requirement: Consultar las líneas de presupuesto y el historial de importaciones
El sistema SHALL exponer un listado de las líneas de presupuesto vigentes con su `monto_asignado`,
y un historial de las importaciones realizadas con sus totales, a usuarios con permiso
`presupuesto.consultar`.

#### Scenario: Consultar el listado de líneas de presupuesto
- **WHEN** un usuario con permiso `presupuesto.consultar` visita el listado de presupuesto
- **THEN** el sistema muestra las líneas de presupuesto con cfinanciero, cuenta, plan de tarea,
  año y monto asignado

#### Scenario: Consultar el historial de importaciones
- **WHEN** un usuario con permiso `presupuesto.consultar` visita el historial de importaciones
- **THEN** el sistema muestra cada `importacion_presupuesto` con su fecha, versión, usuario
  responsable y totales de filas procesadas

#### Scenario: Usuario sin permiso no puede importar ni consultar
- **WHEN** un usuario autenticado sin `presupuesto.importar` intenta subir un Excel, o sin
  `presupuesto.consultar` intenta ver el listado o el historial
- **THEN** el sistema deniega el acceso
- **AND** registra el intento en `security_audit_logs` como `acceso_denegado`
