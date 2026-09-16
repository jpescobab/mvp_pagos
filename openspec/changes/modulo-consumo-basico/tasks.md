## 1. Base de datos

- [x] 1.1 Migración `create_consumos_basicos_table`: `cliente_medidor_id` (FK a `clientes_medidores`, `restrictOnDelete`), `caso_pago_proveedor_id` (FK a `casos_pago_proveedor`, `unique`, `cascadeOnDelete`), `numero_documento` (string, folio de la boleta), `numero_medidor` (string), `fecha_inicio_lectura` (date), `fecha_fin_lectura` (date), `fecha_emision` (date), `fecha_vencimiento` (date), `consumo` (decimal, nullable), `tarifa` (string, nullable), `lectura_estimada` (boolean, default false), `monto_neto` (decimal), `iva` (decimal), `monto_exento` (decimal, default 0, admite negativos), `saldo_anterior` (decimal, default 0), `monto_total` (decimal), timestamps.
- [x] 1.2 Agregar relación `consumos(): HasMany` en `app/Models/ClienteMedidor.php`, ordenada por `fecha_inicio_lectura`.

## 2. Modelo y relaciones

- [x] 2.1 Crear `app/Models/ConsumoBasico.php`: fillable según migración, relaciones `clienteMedidor()` (BelongsTo), `casoPagoProveedor()` (BelongsTo), `vinculosDocumento()` (MorphMany a `VinculoDocumento`, mismo patrón que `App\Models\Proceso::vinculosDocumento()`).

## 3. Policy y permisos

- [x] 3.1 Crear `app/Policies/ConsumoBasicoPolicy.php` (`viewAny`/`view`/`create`/`update` mapeados a `consumo_basico.ver`/`consumo_basico.ver`/`consumo_basico.crear`/`consumo_basico.editar`), siguiendo `app/Policies/ContratoPolicy.php` como referencia.
- [x] 3.2 Registrar `Gate::policy(ConsumoBasico::class, ConsumoBasicoPolicy::class)` en `AppServiceProvider::configureAuthorization()`.
- [x] 3.3 Agregar permisos `consumo_basico.crear`, `consumo_basico.editar`, `consumo_basico.ver` a un seeder (nuevo `WorkflowConsumoBasicoSeeder.php` o el seeder de Pago de Proveedores vigente, a decidir según lo que se confirme contra `FuncionariosCapjSeeder` — mismo criterio usado para los permisos de Contratos), vía `givePermissionTo` (aditivo, idempotente), registrado en `DatabaseSeeder.php`.

## 4. Service

- [x] 4.1 Crear `app/Services/ConsumoBasico/ConsumoBasicoService.php`: método `registrar()` — resuelve o crea el `ClienteMedidor` (por `numero_cliente`, mismo criterio de "no sobrescribir campos ya cargados" que `ContratoService`/`OrdenCompraMercadoPublicoService`), valida que el `caso_pago_proveedor_id` no tenga ya un `ConsumoBasico` (unicidad 1:1), crea el `ConsumoBasico` dentro de `DB::transaction`. Método `esCandidatoServicioBasico(CasoPagoProveedor $caso): bool` — consulta `ClienteMedidor::where('proveedor_id', $caso->proveedor_id)->exists()`, sin leer ningún documento.
- [x] 4.2 Método `actualizar()` en el mismo service (edición del detalle ya registrado).

## 5. Documento adjunto

- [x] 5.1 Crear `app/Http/Controllers/ConsumoBasico/ConsumoBasicoDocumentoController.php`: endpoint `store` (sube el PDF, crea `Documento`+`VersionDocumento`+`VinculoDocumento` vinculado al `ConsumoBasico`, reutilizando la lógica de hash/metadata ya existente en `GestorDocumentoProceso` — extraer a un método compartido si la duplicación es mínima, o reimplementar el flujo puntual si el acoplamiento a `Proceso` lo impide sin tocar código compartido) y `destroy` (desvincular). Sin endpoints de versiones ni validaciones (no aplica a este alcance).
- [x] 5.2 Ruta de descarga del documento adjunto, reutilizando el controlador/mecanismo de descarga ya existente para `Documento` si su firma lo permite sin acoplarse a `Proceso`.

## 6. Controlador principal y rutas

- [x] 6.1 Crear `app/Http/Controllers/ConsumoBasico/ConsumoBasicoController.php` (store/update/show) — controlador liviano, delega a `ConsumoBasicoService`.
- [x] 6.2 Crear Form Requests: `StoreConsumoBasicoRequest`, `UpdateConsumoBasicoRequest` con las validaciones de campos obligatorios (incluyendo la validación condicional de "crear `ClienteMedidor` al vuelo" cuando no se indica un `cliente_medidor_id` existente).
- [x] 6.3 Rutas nuevas en `routes/consumo-basico.php`, requerido desde `routes/web.php` (patrón de los demás dominios).

## 7. Resource

- [x] 7.1 Crear `app/Http/Resources/ConsumoBasico/ConsumoBasicoResource.php` para el payload hacia React (incluye datos del `ClienteMedidor` y del documento adjunto si existe).

## 8. Frontend

- [x] 8.1 Agregar sección "Historial de consumo" a `resources/js/pages/maestros/clientes-medidores/show.tsx`: tabla de los `ConsumoBasico` del medidor, ordenados por período.
- [x] 8.2 En el detalle de un `CasoPagoProveedor` (página React existente), agregar aviso/CTA cuando `esCandidatoServicioBasico` es verdadero y el caso todavía no tiene `ConsumoBasico`, enlazando a un formulario para completar el detalle (con selector de `ClienteMedidor` existente o campos para crear uno nuevo al vuelo, subida del PDF).
- [x] 8.3 Regenerar Wayfinder (`php artisan wayfinder:generate --with-form`) tras agregar las rutas/controladores nuevos.

## 9. Tests

- [x] 9.1 `tests/Feature/ConsumoBasico/RegistrarConsumoBasicoTest.php`: registro exitoso vinculado a un caso de pago existente, validación de campos obligatorios, rechazo si el caso ya tiene un `ConsumoBasico` (unicidad 1:1).
- [x] 9.2 `tests/Feature/ConsumoBasico/ResolverClienteMedidorTest.php`: vincula a un `ClienteMedidor` existente por `numero_cliente`, crea uno nuevo al vuelo si no existe.
- [x] 9.3 `tests/Feature/ConsumoBasico/CandidatoServicioBasicoTest.php`: un `CasoPagoProveedor` cuyo proveedor tiene `ClienteMedidor` se marca candidato; uno sin medidores asociados no.
- [x] 9.4 `tests/Feature/ConsumoBasico/DocumentoConsumoBasicoTest.php`: subir y descargar el PDF adjunto a un `ConsumoBasico`, confirmando que se crea `Documento`/`VersionDocumento`/`VinculoDocumento` correctamente.
- [x] 9.5 `tests/Feature/ConsumoBasico/HistorialConsumoTest.php`: el historial de un `ClienteMedidor` devuelve sus `ConsumoBasico` ordenados por período; un medidor sin consumos devuelve historial vacío sin error.
- [x] 9.6 Confirmar en un test que registrar/editar un `ConsumoBasico` no dispara ninguna transición de workflow sobre el `Proceso` del `CasoPagoProveedor` vinculado (test de no-efecto, mismo criterio que `VinculoContratoTest.php`).
- [x] 9.7 Actualizar el test que afirma la lista exacta de permisos core — confirmado que NO aplica (mismo precedente que Contratos): `RolesAndPermissionsSeederTest` no referencia `contratos.*` ni ningún permiso de módulo funcional, solo permisos core. que afirma la lista exacta de permisos core (si aplica según convención de `CLAUDE.md`) con los nuevos permisos `consumo_basico.*` — confirmar primero si aplica (en Contratos no aplicó, ver precedente).

## 10. Validación final

- [x] 10.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP tocados.
- [x] 10.2 `composer test` (lint:check + types:check + suite Pest completa).
- [x] 10.3 `npm run lint:check` y `npm run types:check` sobre el frontend tocado.
- [ ] 10.4 Verificación manual en navegador: desde el detalle de un `CasoPagoProveedor` candidato, completar el detalle de consumo (creando un `ClienteMedidor` nuevo al vuelo), adjuntar un PDF, y confirmar que aparece en el historial del medidor sin alterar el estado del caso de pago.
