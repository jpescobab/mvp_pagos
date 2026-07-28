## 1. Fuentes reportables y generador (lógica de negocio)

- [x] 1.1 Definir el contrato de fuente reportable en `app/Services/Reportabilidad/` (interfaz `FuenteReportable` con métodos: obtener entidades del período, etiqueta por entidad, payload serializado por entidad).
- [x] 1.2 Implementar `FuenteReportableCasosPagoProveedor`: consulta `CasoPagoProveedor` por período (`periodo == periodo_reportabilidad.codigo`; **verificar el formato real del campo `periodo` contra el `codigo` y normalizar el match si difiere**), construye la etiqueta (p. ej. `sgf_id`/`numero`) y serializa un `payload_crudo` explícito (campos relevantes del caso, no `toArray()` ciego).
- [x] 1.3 Crear `GeneradorCorteReportabilidadService::generar(CorteReportabilidad $corte): void` que, en una `DB::transaction`: valida que el corte esté en `borrador` (delegando en las guardas de `CorteReportabilidadService` / `estaPublicado()`), elimina items y snapshots previos del corte (regeneración reemplazante), y por cada entidad de cada fuente llama `agregarItem` + `capturarSnapshot` de `CorteReportabilidadService`.
- [x] 1.4 Tests unitarios del generador/fuente: genera un item+snapshot por caso del período con hash correcto; regenerar reemplaza sin duplicar; período sin casos → sin contenido; corte publicado → excepción.

## 2. Autorización (policy y permiso)

- [ ] 2.1 Crear/ampliar `CorteReportabilidadPolicy` con la ability `generar` autorizada por `reportabilidad.generar_corte` (sin exigir estado aquí; el estado lo valida el service).
- [ ] 2.2 Registrar la policy en `AppServiceProvider::configureAuthorization()` con `Gate::policy(...)` si es nueva (no hay auto-discovery).
- [ ] 2.3 Agregar el permiso `reportabilidad.generar_corte` en el seeder del dominio de reportabilidad (junto a `reportabilidad.publicar_corte`) y asignarlo a los roles que hoy tienen `reportabilidad.publicar_corte`; si algún test afirma la lista exacta de permisos, actualizarlo. Invalidar la caché de permisos tras sembrar.

## 3. Controlador y ruta

- [x] 3.1 Agregar `generar(CorteReportabilidad $corte)` a `CorteReportabilidadController` (liviano): `Gate::authorize('generar', $corte)`, delega en `GeneradorCorteReportabilidadService::generar`, captura `CorteReportabilidadException` y devuelve `back()->withErrors(...)`, éxito `back()`.
- [x] 3.2 Declarar la ruta `POST reportabilidad/cortes/{corte}/generar` en `routes/reportabilidad.php` con nombre `reportabilidad.cortes.generar`.
- [x] 3.3 Regenerar Wayfinder (`php artisan wayfinder:generate --with-form`).

## 4. Resource y feature tests HTTP

- [x] 4.1 Ampliar `CorteReportabilidadResource` para exponer la lista de `items` con el resumen de la entidad vinculada (tipo legible, identificador, etiqueta); mantener los counts existentes. Ajustar el eager-load en `CorteReportabilidadController@show` (`items.vinculable`).
- [x] 4.2 `GenerarCorteReportabilidadTest`: generar con permiso puebla items+snapshots para los casos del período (verifica conteos y hash no vacío); regenerar reemplaza (mismo conteo, no duplica); corte publicado → bloqueado; sin `reportabilidad.generar_corte` → 403; período sin casos → 0 items sin error.

## 5. Frontend (cortes/show.tsx)

- [x] 5.1 Ampliar el tipo TS del corte para incluir `items` (con la entidad vinculada resumida) y actualizar `CorteReportabilidadResource` consumido.
- [x] 5.2 En `resources/js/pages/reportabilidad/cortes/show.tsx`: botón "Generar corte" (visible solo con corte en `borrador` y `auth.permissions` incluyendo `reportabilidad.generar_corte`), usando helper Wayfinder con import con nombre; al confirmar, `router.post` con `preserveScroll`.
- [x] 5.3 Renderizar el listado de items del corte (tipo de entidad, identificador, etiqueta) y el conteo de snapshots; estado vacío cuando no hay contenido.

## 6. Validación final

- [x] 6.1 Correr `php artisan test` (suite de Reportabilidad y PagoProveedores que toque casos) en verde.
- [x] 6.2 Correr `composer lint` (Pint), `npm run lint:check`, `npm run types:check`, `composer types:check` (PHPStan). Recordar que `tsc` no lo corre el CI.
- [x] 6.3 Revisar que el controlador quedó liviano (sin recolección/serialización/transacción): toda la lógica en `GeneradorCorteReportabilidadService` y las fuentes. Confirmar el formato real de `periodo` verificado en 1.2.
