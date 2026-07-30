## Why

El módulo Presupuesto no tiene ningún dato ni mecanismo hoy: el presupuesto asignado se formula y
aprueba en CGU, no en este sistema, y llega actualmente solo como un reporte Excel que el equipo
de finanzas maneja fuera de cualquier control o trazabilidad institucional. Sin importar y
conservar ese presupuesto asignado con evidencia (snapshot) no hay línea presupuestaria contra la
cual el Certificado de Disponibilidad Presupuestaria (CDP, siguiente change) pueda comprometer ni
controlar saldo — esta importación es la base sobre la que se apoya todo el resto del módulo.

## What Changes

- Nueva importación del presupuesto asignado desde el reporte Excel de CGU (columnas reales:
  `Nro.Versión, Catalogo, Descripción, P.Pptario., U.Ejecutora, PROG, SUBPR, ACTIV, TAREA,
  Enero...Diciembre, Total Proyectado, Ppto.Vigente`), usando `Ppto.Vigente` como monto asignado
  (no `Total Proyectado`, que puede ser 0 con presupuesto vigente real).
- Nuevo catálogo `planes_tarea` (tupla `PROG/SUBPR/ACTIV/TAREA`), independiente del clasificador
  presupuestario existente — una cuenta (`catalogo`) puede combinarse con varios planes de tarea.
- Nuevas líneas de presupuesto (`presupuestos`): `cfinanciero × catalogo × plan_tarea × año`, con
  `monto_asignado`. El nivel es `cfinanciero` (unidad ejecutora), no `ccosto`.
- Cada importación queda registrada con snapshot inmutable del Excel original (fuente, fecha,
  hash, usuario), reutilizando la capa transversal de integraciones ya existente
  (`IntegracionExternaService`, sistema externo `CGU`, hoy sembrado pero inactivo) — mismo patrón
  ya usado por SGF y Mercado Público, sin inventar mecanismo nuevo.
- Reimportación: el Excel de CGU viene versionado (`Nro.Versión`); una nueva versión actualiza
  `monto_asignado` de las líneas existentes sin alterar movimientos ni certificados ya emitidos
  (eso vendrá en el change del CDP), y queda registrada como una importación nueva con su propio
  snapshot.
- **Ampliación del clasificador presupuestario existente** (`items`, `asignaciones`, `catalogos`,
  ver `tablas-maestras-institucionales`) para cubrir Subtítulo 29 (Adquisición de Activos No
  Financieros) y Subtítulo 31 (Iniciativas de Inversión), además del Subtítulo 22 ya sembrado —
  confirmado con datos reales de uso activo de CAPJ. Es dato de seeder, no cambia el requirement
  existente que ya modela items/asignaciones/catalogos de forma genérica.
- Pantalla de importación (subir Excel, ver historial de importaciones) y listado denso de líneas
  de presupuesto con su saldo disponible.
- Permisos nuevos `presupuesto.importar` y `presupuesto.consultar`, sembrados en un
  `PresupuestoSeeder` propio (no en `RolesAndPermissionsSeeder`, que tiene un test de lista exacta
  de permisos core).

## Capabilities

### New Capabilities
- `presupuesto-importacion-cgu`: Importar el presupuesto asignado y los planes de tarea desde el
  Excel de CGU con snapshot obligatorio, mantener el catálogo de planes de tarea y las líneas de
  presupuesto (cfinanciero × catálogo × plan de tarea × año) con su saldo, y soportar
  reimportación versionada sin perder historial.

### Modified Capabilities
(ninguna — la ampliación del clasificador presupuestario es una extensión de datos sembrados, no
un cambio de requirement sobre `tablas-maestras-institucionales`, que ya modela items/
asignaciones/catalogos de forma genérica sin restringirlos a un subtítulo)

## Impact

- Nuevo: `app/Models/Presupuesto/{PlanTarea,Presupuesto,ImportacionPresupuesto}.php`,
  `app/Services/Presupuesto/{LectorExcelPresupuestoCgu,ImportadorPresupuestoCguService}.php`,
  `app/Http/Controllers/Presupuesto/ImportacionPresupuestoController.php`,
  `app/Policies/Presupuesto/*`, `routes/presupuesto.php` (requerido desde `routes/web.php`),
  `resources/js/pages/presupuesto/importaciones/*`, `resources/js/pages/presupuesto/lineas/*`,
  `database/seeders/PresupuestoSeeder.php`, `database/migrations/*_create_planes_tarea_table.php`
  y equivalentes para `presupuestos` e `importaciones_presupuesto`,
  `tests/Feature/Presupuesto/*`.
- Modificado: `database/seeders/ItemsSeeder.php`, `AsignacionesSeeder.php`,
  `CatalogosSeeder.php` (Subtítulo 29 y 31), `database/seeders/IntegracionesSeeder.php` (activar
  sistema externo `CGU`), `app/Providers/AppServiceProvider.php` (registrar policies nuevas —
  sin auto-discovery), `resources/js/components/app-sidebar.tsx` (ítem de navegación nuevo).
- Dependencias: usa `phpoffice/phpspreadsheet` (ya instalado, hoy solo se usa para escribir) para
  leer el Excel — primer lector de Excel del proyecto.
