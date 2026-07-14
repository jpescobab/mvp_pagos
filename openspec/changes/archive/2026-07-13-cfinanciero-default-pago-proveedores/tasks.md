## 1. Configuración

- [x] 1.1 Crear `config/pago-proveedores.php` con `'cfinanciero_default_codigo' => env('PAGO_PROVEEDORES_CFINANCIERO_DEFAULT_CODIGO', '1400')`
- [x] 1.2 Agregar `PAGO_PROVEEDORES_CFINANCIERO_DEFAULT_CODIGO=1400` a `.env.example` y `.env`

## 2. Resolución del cfinanciero por defecto

- [x] 2.1 Crear `App\Services\PagoProveedores\CfinancieroPorDefectoResolver` con método `resolver(): ?int` que busque el `Cfinanciero` activo por el código configurado, cacheado (`Cache::remember`, TTL 1h) y con log de `warning` cuando el código configurado no resuelve a ningún `cfinanciero` activo
- [x] 2.2 Test unitario del resolver: código válido resuelve al id correcto; código inexistente retorna `null` y loguea warning; resultado cacheado (segunda llamada no repite la query)

## 3. Integrar el default en la resolución de cfinanciero del caso

- [x] 3.1 Modificar `CasoPagoProveedor::cfinancieroId()` (`app/Models/CasoPagoProveedor.php:122-125`) para caer al `CfinancieroPorDefectoResolver` solo cuando `procesoAdquisicion?->ccosto?->cfinanciero_id` sea `null`
- [x] 3.2 Test: caso sin `proceso_adquisicion_id` retorna el cfinanciero por defecto
- [x] 3.3 Test: caso con `proceso_adquisicion_id` vinculado y `ccosto->cfinanciero_id` resuelto ignora el default y retorna el real

## 4. Persistir el default en el EgresoCgu al aprobar desde Finanzas

- [x] 4.1 Modificar `RevisionEgresoService::aprobarPago()` (`app/Services/PagoProveedores/RevisionEgresoService.php:206-224`) para invocar `$caso->egresoCguItems->first()?->egreso?->actualizarCfinancieroSiFalta($caso)` inmediatamente después de que `jurisdiccionDeterminable()` pasa, antes de ejecutar la transición de workflow
- [x] 4.2 Test: aprobar desde Finanzas un caso sin adquisición vinculada, con default configurado y `EgresoCgu.cfinanciero_id` previamente `null` — verificar que la aprobación no se bloquea y que `EgresoCgu.cfinanciero_id` queda seteado con el id del default después de aprobar
- [x] 4.3 Test: aprobar desde Finanzas un caso sin adquisición vinculada y sin default configurado/resoluble sigue bloqueado con el mensaje de error existente (`RuntimeException` "centro financiero determinable")

## 5. Verificación de regresión

- [x] 5.1 Revisar `tests/Feature/PagoProveedores/RevisionPagosTest.php` y actualizar/agregar escenarios acorde a los nuevos requirements (no debe quedar ningún test que asuma que "sin adquisición" siempre bloquea, salvo el escenario explícito sin default)
- [x] 5.2 Correr `composer test` (incluye `config:clear`, `lint:check`, `types:check`, `php artisan test`) y `vendor/bin/pint --dirty --format agent` si se tocó PHP
- [x] 5.3 Verificado a nivel de servicio (sin ejecutar la aprobación real para no mutar el caso real): `CasoPagoProveedor::find(11)->cfinancieroId()` ahora resuelve `1` (antes `null`) y `RevisionEgresoService::jurisdiccionDeterminable()` para ese caso en instancia Finanzas ahora retorna `true`. Queda pendiente que el usuario confirme visualmente en la UI haciendo clic en "Aprobar Pago" cuando decida aprobar el caso real
