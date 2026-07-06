## 1. Configuración e infraestructura de integración

- [x] 1.1 Agregar `MERCADOPUBLICO_API_KEY` y `MERCADOPUBLICO_API_BASE_URL` a `.env.example` (vacíos) y a `config/services.php` (bloque `mercadopublico`), siguiendo el patrón de `cmf`.
- [x] 1.2 Actualizar `database/seeders/IntegracionesSeeder.php`: activar el `sistema_externo` `MERCADO_PUBLICO` (`activo: true`) y sembrar el permiso `adquisiciones.consultar_orden_compra_mp` otorgado al rol `admin`.
- [x] 1.3 Actualizar/crear el test del seeder correspondiente para cubrir la activación de `MERCADO_PUBLICO` y el nuevo permiso.

## 2. Migraciones y modelos

- [x] 2.1 Migración `ordenes_compra_mercado_publico`: código de OC (unique), proveedor emisor (`proveedor_id` nullable FK), `proceso_adquisicion_id` nullable FK, montos, fecha de emisión, estado/código Mercado Público, `snapshot_datos_externo_id` de origen, timestamps.
- [x] 2.2 Migración `orden_compra_mercado_publico_items`: FK a la OC, código/descripción de ítem, cantidad, precio unitario, monto total.
- [x] 2.3 Modelo `OrdenCompraMercadoPublico` con relaciones (`items()`, `proveedor()`, `procesoAdquisicion()`, `snapshot()`) y factory.
- [x] 2.4 Modelo `OrdenCompraMercadoPublicoItem` con relación inversa y factory.

## 3. Servicio de dominio

- [x] 3.1 Crear `App\Services\Adquisiciones\OrdenCompraMercadoPublicoService` con métodos: `buscarLocal(string $codigo)`, `consultarApi(string $codigo)`, `compararConApi(OrdenCompraMercadoPublico $oc)`, `guardarDesdeApi(array $payload, ...)`, `aplicarActualizacion(OrdenCompraMercadoPublico $oc, array $diferencias)`.
- [x] 3.2 Implementar el cliente HTTP hacia la API de Mercado Público usando `Http::` con `config('services.mercadopublico')`.
- [x] 3.3 Integrar `App\Services\Integraciones\IntegracionExternaService` para `iniciarTrabajo()`, `registrarSolicitud()` y `registrarSnapshot()` en cada consulta (encontrada, no encontrada o error).
- [x] 3.4 Implementar el cálculo de diferencias campo a campo entre el registro local y el payload normalizado de la API.
- [x] 3.5 Implementar la verificación del proveedor emisor contra el catálogo de `proveedores` (por RUT/código) y exponer si existe o no.
- [x] 3.6 Escribir tests de Feature/Unit para: búsqueda local, consulta API con OC encontrada/no encontrada, comparación con y sin diferencias, guardado tras confirmación, registro de snapshot y solicitud en todos los casos.

## 4. Vínculo con proceso de adquisición

- [x] 4.1 Crear `App\Http\Requests\Adquisiciones\VincularOrdenCompraMercadoPublicoRequest` (o reutilizar convención existente).
- [x] 4.2 Crear `VinculoProcesoAdquisicionOrdenCompraMercadoPublicoController` (store/destroy) siguiendo el patrón de `VinculoAdquisicionCasoPagoProveedorController`, con `Gate::authorize` y `AuditLogger`.
- [x] 4.3 Tests de Feature: vincular, desvincular, verificar que el estado del `Proceso` del `proceso_adquisicion` no cambia.

## 5. Capa HTTP (controlador y rutas)

- [x] 5.1 Crear `App\Http\Controllers\Adquisiciones\OrdenCompraMercadoPublicoController` con acciones: buscar por código, verificar contra API, confirmar actualización, guardar OC nueva.
- [x] 5.2 Crear Form Requests de validación para cada acción (código de OC, confirmación de actualización, confirmación de guardado).
- [x] 5.3 Definir Policy/Gate para `adquisiciones.consultar_orden_compra_mp`.
- [x] 5.4 Registrar rutas en `routes/adquisiciones.php` (o archivo de rutas del dominio correspondiente).
- [x] 5.5 Regenerar tipos de Wayfinder (`php artisan wayfinder:generate --with-form`).
- [x] 5.6 Tests de Feature HTTP para cada endpoint: autorizado/no autorizado, casos de éxito y error.

## 6. Alta/actualización de proveedor desde el flujo de OC

- [x] 6.1 Exponer desde el controlador de OC los datos del proveedor emisor (para precargar el formulario existente de `Maestros\ProveedorController`) cuando el proveedor no exista localmente.
- [x] 6.2 Verificar que el guardado de la OC quede bloqueado hasta que el proveedor exista (creado en este flujo o preexistente); test de Feature que lo confirme.
- [x] 6.3 Normalizar el RUT (`Proveedor::normalizarRut()`, mutator en el modelo + `prepareForValidation()` en los Form Requests de alta/edición) para que `verificarProveedor()` detecte proveedores existentes sin importar el formato del RUT recibido desde Mercado Público, evitando duplicados. Bug detectado en producción (proveedor LATAM Airlines duplicado); registro erróneo limpiado.

## 7. Frontend (Inertia/React)

- [x] 7.1 Página de búsqueda de OC por código (`resources/js/pages/adquisiciones/ordenes-compra-mercado-publico/buscar.tsx` o ruta equivalente del dominio), con el código de OC y el botón "Consultar" alineados en una única fila horizontal por sobre la ficha (sin barra lateral de filtros).
- [x] 7.2 Componente de "ficha" genérico y reutilizable (p. ej. `resources/js/components/mercado-publico/ficha-consulta.tsx`), parametrizado por secciones y sin acoplarse a tipos/campos específicos de OC, para que un change futuro de Licitaciones lo reutilice sin rediseñar el layout. Secciones en orden fijo: (1) encabezado (código/tipo/organismo), (2) cronograma de estados de Mercado Público (línea de tiempo de solo lectura, con estado vacío si no viene informado), (3) datos del organismo comprador, (4) condiciones (moneda/forma de pago/plazo de entrega), (5) adjudicación/proveedor, (6) tabla de ítems. En este change se implementa y prueba únicamente con datos de OC.
- [x] 7.3 Vista de OC local (usa el componente de ficha) con acción "Verificar contra Mercado Público".
- [x] 7.4 Vista de comparación de diferencias con acciones "Actualizar" / "Mantener".
- [x] 7.5 Vista de vista previa de OC nueva (usa el componente de ficha) con estado del proveedor emisor y confirmación de guardado.
- [x] 7.6 Aviso explícito de "OC no encontrada" sin acciones de guardado.
- [x] 7.7 Componente/acción de vínculo y desvínculo con `proceso_adquisicion` en el detalle de la OC.
- [x] 7.8 Verificar el flujo end-to-end en el navegador (dev server) cubriendo: fila de filtros sobre la ficha, secciones separadas de la ficha con cronograma como segunda sección, búsqueda local, verificación contra la API real de Mercado Público (sin ticket configurado) sin mutar el registro local, y registro de solicitud/snapshot. El guardado de OC nueva y el caso "no encontrada" quedan cubiertos por los tests de Feature con `Http::fake` (no se pudo forzar de forma fiable en el navegador sin un ticket real, ya que Mercado Público responde eco del código incluso en error).
- [x] 7.9 Agregar el ítem de sidebar "Órdenes de Compra (Mercado Público)" en el grupo "Adquisiciones" (`resources/js/components/app-sidebar.tsx`), sin condicionarlo por permiso — igual que su hermano "Procesos" en el mismo grupo, que tampoco lo hace. El permiso `adquisiciones.consultar_orden_compra_mp` se sigue exigiendo en el controlador/policy (confirmado: un usuario sin el permiso recibe 403 al visitar la ruta directamente). Se descartó gatear la visibilidad del ítem porque el permiso solo se otorga al rol `admin` vía el seeder, no a `superadmin` (que solo recibe la lista fija de permisos "núcleo" de `RolesAndPermissionsSeeder`, no los permisos específicos de cada módulo) — condicionarlo ocultaba el ítem incluso a usuarios que sí pueden acceder a la página.

## 8. Especificación y cierre

- [x] 8.1 Ejecutar `openspec validate integrar-ordenes-compra-mercado-publico --strict` y corregir lo que señale.
- [x] 8.2 Ejecutar `composer test` / `composer ci:check` (lint, types, test) y corregir hallazgos.
- [x] 8.3 Ejecutar `vendor/bin/pint --dirty --format agent` sobre los archivos PHP modificados.
- [ ] 8.4 Tras aprobación, archivar el change con `/opsx:archive` para fusionar los spec delta en `openspec/specs/adquisiciones/spec.md` y crear `openspec/specs/ordenes-compra-mercado-publico/spec.md` y `openspec/specs/paginas-ordenes-compra-mercado-publico/spec.md`.
