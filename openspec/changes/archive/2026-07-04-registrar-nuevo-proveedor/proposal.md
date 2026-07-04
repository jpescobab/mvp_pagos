## Why

El catálogo de `proveedores` hoy solo se puede leer y solo guarda datos mínimos (RUT, nombre, correo, dirección, contacto, activo). No existe ninguna vía para registrar un proveedor nuevo desde la aplicación — hoy se siembra por seeder. Finanzas necesita dar de alta proveedores con los datos que realmente usa para pagarles: clasificación/rubro, contacto comercial, domicilio estructurado y datos bancarios de la cuenta de destino, antes de poder asociarlos a órdenes de compra o casos de pago.

## What Changes

- Se agregan a `proveedores` los campos necesarios para el alta completa: identificación tributaria (giro, tipo de contribuyente), clasificación (rubros, como lista), contacto comercial (cargo y teléfono, además del nombre de contacto ya existente), domicilio estructurado (región, comuna, además de la dirección ya existente) y datos bancarios (banco, tipo de cuenta, número de cuenta, condición de pago, moneda, correo para pagos, documento de respaldo bancario) y notas internas. Todos los campos nuevos son opcionales salvo los de identificación tributaria básica.
- Se agrega `ProveedorController::create()` / `store()`, con `StoreProveedorRequest` para validación, restringido a `core_institucional.administrar` (mismo permiso que gobierna el resto de tablas maestras).
- Se agrega la página React `maestros/proveedores/create.tsx`: formulario de alta por pasos (Identificación, Clasificación, Contacto, Domicilio, Datos bancarios) con un panel lateral de resumen/vista previa y completitud del registro, siguiendo el patrón visual institucional (tema, botones sin relleno sólido, tipografía reducida) — no la maqueta genérica de referencia, que usa un botón primario con relleno sólido y no aplica al tema del proyecto.
- Se agregan al proyecto los primitivos de shadcn/ui que faltan para el formulario por pasos: `Tabs`, `Switch`, `Textarea`, `Progress` (mismo origen — Radix UI — que los componentes ya instalados).
- El botón "Nuevo Proveedor" del índice de proveedores pasa de "Disponible próximamente" a enlazar a la página de alta.

## Capabilities

### New Capabilities
- `registrar-proveedor`: alta de un proveedor institucional con identificación tributaria, clasificación, contacto comercial, domicilio y datos bancarios, mediante un formulario por pasos con resumen de completitud.

### Modified Capabilities

(ninguna — `consulta-catalogo-proveedores` sigue devolviendo el mismo subconjunto de campos en el listado; los campos nuevos no alteran su contrato)

## Impact

- **DB**: migración que agrega columnas nullable a `proveedores` (sin romper `rutproveedor`, `nombre`, `correo`, `direccion`, `contacto`, `imagen`, `activo` existentes).
- **Backend**: `App\Models\Proveedor` (fillable/casts), `App\Http\Controllers\Maestros\ProveedorController` (create/store), `App\Http\Requests\Maestros\StoreProveedorRequest`, `App\Http\Resources\Maestros\ProveedorResource` (campos nuevos), `App\Policies\ProveedorPolicy` (si no existe, se crea reutilizando `core_institucional.administrar`).
- **Frontend**: `resources/js/pages/maestros/proveedores/create.tsx`, componentes de apoyo en `resources/js/components/maestros/`, tipos en `resources/js/types/maestros.ts`, nuevos componentes `resources/js/components/ui/{tabs,switch,textarea,progress}.tsx` y sus dependencias `@radix-ui/react-tabs`, `@radix-ui/react-switch`, `@radix-ui/react-progress`.
- **Rutas**: `GET /maestros/proveedores/create`, `POST /maestros/proveedores` en `routes/maestros.php`.
