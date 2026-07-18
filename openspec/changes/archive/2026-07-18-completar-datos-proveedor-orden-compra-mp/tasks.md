## 1. Mapeo del proveedor desde el payload

- [x] 1.1 En `app/Services/Adquisiciones/OrdenCompraMercadoPublicoService.php`, ampliar el bloque `proveedor` de `normalizarPayload()` con `direccion` (`Proveedor.Direccion`), `comuna` (`Proveedor.Comuna`), `region` (`Proveedor.Region`), `giro` (`Proveedor.Actividad`), `correo` (`Proveedor.MailContacto`), `contacto` (`Proveedor.NombreContacto`), `contacto_cargo` (`Proveedor.CargoContacto`), `contacto_telefono` (`Proveedor.FonoContacto`).
- [x] 1.2 Normalizar cada valor a `null` cuando queda vacío tras `trim` (helper privado `trimONull` en el mismo servicio, reutilizado por todos los campos de texto del proveedor).

## 2. Creación y completado del proveedor

- [x] 2.1 En `resolverProveedor()`, al **crear** un proveedor nuevo, incluir todos los campos mapeados en 1.1 (además de `rutproveedor`, `nombre`, `activo`).
- [x] 2.2 Reemplazar el completado hoy hardcodeado a `nombre` por un recorrido genérico sobre todos los campos mapeados (`camposCompletablesProveedor`): para un proveedor existente, rellenar solo los que están vacíos localmente (`null`/`''`) y que el payload aporta con valor, sin sobrescribir ninguno ya cargado.

## 3. Salvaguarda de RUT ausente

- [x] 3.1 Crear una excepción de dominio `App\Exceptions\OrdenCompraSinProveedorException` (con un mensaje claro para el usuario).
- [x] 3.2 En `resolverProveedor()`, cuando no hay override manual y el RUT normalizado del payload es vacío, lanzar `OrdenCompraSinProveedorException` antes de intentar crear el proveedor (evita el proveedor con RUT vacío y el rollback por unique).
- [x] 3.3 En `OrdenCompraMercadoPublicoController::guardar()`, capturar `OrdenCompraSinProveedorException` y responder con `back()->withErrors(['codigo' => <mensaje>])`, sin persistir la OC.

## 4. Tests (Pest)

- [x] 4.1 Test: al guardar una OC cuyo proveedor no existe, el proveedor se crea con dirección, comuna, región, giro, correo y contacto tomados del payload; los campos vacíos del payload quedan en `null`.
- [x] 4.2 Test: al guardar una OC cuyo proveedor ya existe con campos vacíos, se completan solo esos campos sin sobrescribir los ya cargados.
- [x] 4.3 Test: al guardar una OC cuyo payload no trae RUT de proveedor, el guardado se rechaza (excepción) y no se crea ningún proveedor ni la OC.
- [x] 4.4 Test: override manual (`proveedor_id`) sigue vinculando ese proveedor sin ejecutar creación/completado.

## 5. Validación y cierre

- [x] 5.1 `vendor/bin/pint --dirty` sobre los archivos PHP modificados.
- [x] 5.2 `composer test` en verde (Pint + PHPStan + Pest) — 621 tests, 617 passed, 4 skipped, 0 failed; PHPStan 0 errores.
- [x] 5.3 `npx openspec validate completar-datos-proveedor-orden-compra-mp --strict` en verde.
- [x] 5.4 Verificación con datos reales: al reguardar una OC del proveedor `981`, se completaron dirección, comuna y región vacías sin sobrescribir el `giro` ya cargado (resultado `actualizado`).
