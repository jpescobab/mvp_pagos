## 1. Base de datos y modelo

- [x] 1.1 Migración `add_datos_completos_to_proveedores_table`: agrega `giro`, `tipo_contribuyente`, `rubros` (json), `contacto_cargo`, `contacto_telefono`, `region`, `comuna`, `banco`, `tipo_cuenta`, `numero_cuenta`, `condicion_pago` (default `dias_30`), `moneda` (default `clp`), `correo_pago`, `documento_respaldo_path`, `notas_internas` — todas nullable salvo los defaults indicados.
- [x] 1.2 Enums `App\Enums\Maestros\{TipoContribuyente,TipoCuentaBancaria,CondicionPago,Moneda,RubroProveedor}` (backed string, con `label(): string`).
- [x] 1.3 Actualizar `App\Models\Proveedor`: agregar los campos nuevos a `$fillable`, cast `rubros` a `array`, cast `activo` (ya existe).

## 2. Backend de alta

- [x] 2.1 `App\Http\Requests\Maestros\StoreProveedorRequest`: reglas para todos los campos (rut único, nombre requerido, resto opcionales con `Rule::enum` donde corresponda, `rubros.*` con `Rule::enum(RubroProveedor::class)`, documento con `mimes:pdf,jpg,jpeg,png|max:8192`).
- [x] 2.2 `App\Policies\ProveedorPolicy` con `create(): bool` sobre `core_institucional.administrar`; registrar en `AppServiceProvider`.
- [x] 2.3 `ProveedorController::create()`: `Gate::authorize`, retorna Inertia con los catálogos de enums (`[{value,label}]`) y la lista estática de bancos comunes.
- [x] 2.4 `ProveedorController::store()`: `Gate::authorize`, valida con `StoreProveedorRequest`, guarda el documento de respaldo en el disco `local` bajo `proveedores/{proveedor}/documento-respaldo.{ext}` dentro de una transacción, crea el `Proveedor` y redirige al índice con mensaje flash de éxito.
- [x] 2.5 Actualizar `App\Http\Resources\Maestros\ProveedorResource` para incluir los campos nuevos (usados por una futura vista de detalle; no cambia el contrato de `index()` de `consulta-catalogo-proveedores`).
- [x] 2.6 Rutas `GET /maestros/proveedores/create` y `POST /maestros/proveedores` en `routes/maestros.php`.
- [x] 2.7 Regenerar Wayfinder (`php artisan wayfinder:generate --with-form`).

## 3. Primitivos de UI faltantes

- [x] 3.1 Agregar `resources/js/components/ui/tabs.tsx`, `switch.tsx`, `textarea.tsx`, `progress.tsx` (estilo "new-york", mismo patrón que los componentes shadcn ya instalados) y sus dependencias `@radix-ui/react-tabs`, `@radix-ui/react-switch`, `@radix-ui/react-progress`.

## 4. Frontend: formulario de alta

- [x] 4.1 Tipos en `resources/js/types/maestros.ts`: extender `Proveedor` con los campos nuevos; agregar tipos de catálogo (`OpcionCatalogo = {value: string; label: string}`).
- [x] 4.2 `resources/js/pages/maestros/proveedores/create.tsx`: layout de dos columnas (formulario por pasos + panel "Resumen"), con `Tabs` para los 5 pasos, con botones Cancelar / Borrador (deshabilitado, "Disponible próximamente") / Anterior / Siguiente / Registrar proveedor (botón institucional sin relleno sólido, variante `default`). Implementado con `router.post` + `useState` por campo (patrón ya usado en `seguridad/usuarios/create.tsx`), no con `useForm` de Inertia, para mantener consistencia con el resto de páginas de alta del proyecto.
- [x] 4.3 Paso Identificación: RUT, nombre (razón social), giro, tipo de contribuyente.
- [x] 4.4 Paso Clasificación: checkboxes de rubros (`RubroProveedor` cases recibidos como prop).
- [x] 4.5 Paso Contacto: nombre de contacto, cargo, teléfono, correo.
- [x] 4.6 Paso Domicilio: dirección, región, comuna.
- [x] 4.7 Paso Datos bancarios: banco (select con lista estática + opción "Otro" a texto libre), tipo de cuenta, número de cuenta, condición de pago, moneda, correo para pagos, input de archivo para documento de respaldo, switch "Activar proveedor al guardar" (mapea a `activo`), textarea de notas internas.
- [x] 4.8 Panel "Resumen": avatar con iniciales (o placeholder "Sin nombre"), nombre, RUT, correo, condición de pago, badge de estado (Activo/Inactivo según el switch), barra de completitud calculada en cliente y checklist de los 5 pasos.
- [x] 4.9 Al recibir errores de validación del submit, saltar a la primera pestaña con un campo errado.
- [x] 4.10 Habilitar el enlace "Nuevo Proveedor" del índice (`resources/js/pages/maestros/proveedores/index.tsx`) hacia la nueva ruta `create` (no existía un botón previo en el índice real; se agregó de cero, ya enlazado desde el inicio).

## 5. Validación y documentación

- [x] 5.1 Test Feature `StoreProveedorTest` (Pest): alta mínima, alta completa con documento, RUT duplicado, usuario sin permiso.
- [x] 5.2 `vendor/bin/pint --dirty`, `npm run lint:check`, `npm run format:check`, `npm run types:check`, `php artisan test --compact`.
- [x] 5.3 Sincronizar la spec delta en `openspec/specs/registrar-proveedor/spec.md` y archivar el change.
