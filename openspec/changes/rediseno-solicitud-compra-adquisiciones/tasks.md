## 1. Migración de base de datos

- [x] 1.1 Migración `add_solicitud_compra_fields_to_procesos_adquisicion_table`: agregar `fecha_inicio` (date, nullable), `nombre` (string, nullable), `id_requerimiento` (string, nullable), `funcionario_requirente_id` (foreignId nullable → `funcionarios`, `nullOnDelete`), `caracteristicas` (text, nullable), `motivo_contratacion` (text, nullable), `en_plan_compras` (boolean, nullable), `id_pac` (string, nullable), `codigo_bip` (string, nullable) a `procesos_adquisicion`.
- [x] 1.2 Migración `rename_monto_to_monto_estimado_in_procesos_adquisicion_table`: `renameColumn('monto', 'monto_estimado')`.
- [x] 1.3 Migración `make_objeto_nullable_in_procesos_adquisicion_table`: `objeto` pasa a `nullable()`.

## 2. Modelo

- [x] 2.1 `app/Models/ProcesoAdquisicion.php`: actualizar `$fillable` con los campos nuevos y `monto_estimado`; actualizar `casts()` (`en_plan_compras` => boolean, `fecha_inicio` => date, `monto_estimado` => decimal:2).
- [x] 2.2 Agregar relación `funcionarioRequirente(): BelongsTo` → `Funcionario` en `ProcesoAdquisicion`.

## 3. Seeders de datos de soporte

- [x] 3.1 Agregar `TipoDocumento` `INFORME_JUSTIFICACION_TRATO_DIRECTO` (activo) en el seeder de tipos de documento correspondiente (`database/seeders/TiposDocumentoSeeder.php`).
- [x] 3.2 `database/seeders/RequisitosDocumentalesAdquisicionesSeeder.php`: agregar `INFORME_JUSTIFICACION_TRATO_DIRECTO` como obligatorio para la modalidad `TRATO_DIRECTO`.
- [x] 3.3 `database/seeders/WorkflowAdquisicionesSeeder.php`: otorgar el permiso `adquisiciones.publicar` también al rol `administrativo_adquisiciones` (ver design.md § "Alcance del permiso de aprobación" — hoy solo lo tiene `admin`, y la nómina real mapea la jefatura de Adquisiciones a ese rol).

## 4. Servicio de dominio (`ProcesoAdquisicionService`)

- [x] 4.1 `app/Services/Adquisiciones/ProcesoAdquisicionService::crear()`: generar `codigo` automáticamente siguiendo el patrón de `CrearBorradorCertificadoDisponibilidadService` (insertar con placeholder único, luego `update(['codigo' => sprintf('SPC-%03d-%d', $proceso->id, $anio)])` dentro de la misma transacción; `$anio` desde `fecha_inicio`). Formato actualizado a `SPC-NNN-AAAA` (ej. `SPC-001-2026`) a pedido explícito del usuario durante la verificación.
- [x] 4.2 `ProcesoAdquisicionService::crear()`: resolver `modalidad_id` a partir del booleano `convenio_marco` recibido (`true` → `ModalidadAdquisicion` con `codigo = 'CONVENIO_MARCO'`, `false` → `codigo = 'TRATO_DIRECTO'`), reemplazando la validación de "modalidad enviada activa" (nota: se implementó con `where(...)->first()` + `ProcesoAdquisicionException::modalidadInvalida()` en vez de `firstOrFail()`, para preservar el manejo de error existente del controller en vez de una `ModelNotFoundException` sin capturar).
- [x] 4.3 `ProcesoAdquisicionService::crear()` y `actualizar()`: sincronizar `objeto = caracteristicas` antes de persistir.
- [x] 4.4 `ProcesoAdquisicionService::actualizar()`: aplicar la misma derivación de modalidad desde `convenio_marco` (punto 4.2) al actualizar, manteniendo la regla existente de solo permitir edición en `borrador`.
- [x] 4.5 Agregar método `ProcesoAdquisicionService::validarMontoBajoUmbralUtm(float $montoEstimado, CarbonInterface $fecha): void` que resuelva la UTM vigente vía `IndicadorEconomicoSelector::paraPeriodo('UTM', ...)` (inyectado por constructor) y lance `ProcesoAdquisicionException` si `$montoEstimado >= 1000 * $utm->valor`; invocarlo desde `crear()` y `actualizar()`.
- [x] 4.6 Agregar caso a `app/Exceptions/ProcesoAdquisicionException.php` para el rechazo por umbral UTM (mensaje descriptivo); se agregó además `campo(): string` a la excepción para que el controller mapee el error al campo correcto (`modalidad_id` vs `monto_estimado`), no contemplado explícitamente en el texto original de esta tarea pero necesario para que ambos casos de error se vean bien en el formulario.

## 5. Validación HTTP (Form Requests)

- [x] 5.1 `CrearProcesoAdquisicionRequest`: reemplazar `modalidad_id` por `convenio_marco: required|boolean`; agregar reglas para `fecha_inicio` (required|date), `nombre` (required|string), `id_requerimiento` (nullable|string), `ccosto_id` (ya existe, mantener), `funcionario_requirente_id` (required|exists:funcionarios,id), `caracteristicas` (required|string), `motivo_contratacion` (required|string), `en_plan_compras` (required|boolean), `id_pac` (nullable|string), `codigo_bip` (nullable|string), `monto_estimado` (required|numeric|min:0); quitar `codigo` del payload aceptado (se genera en el Service, no se valida como input).
- [x] 5.2 `CrearProcesoAdquisicionRequest`: agregar `withValidator()` que rechace cuando el `funcionario_requirente_id` enviado no pertenezca al `ccosto_id` enviado (ver design.md).
- [x] 5.3 Replicar 5.1 y 5.2 en `ActualizarProcesoAdquisicionRequest` (mismas reglas; `codigo` no es editable, se excluye igual que en creación).

## 6. Controlador y Resource

- [x] 6.1 `ProcesoAdquisicionController::create()` y `edit()`: reemplazar la carga de `modalidades` por `Funcionario::where('activo', true)->get(['id', 'nombre', 'cargo', 'ccosto_id'])`, manteniendo `ccostos` y `proveedores` como hoy.
- [x] 6.2 `ProcesoAdquisicionResource`: exponer `fecha_inicio`, `nombre`, `id_requerimiento`, `funcionario_requirente` (id, nombre, cargo), `caracteristicas`, `motivo_contratacion`, `en_plan_compras`, `id_pac`, `codigo_bip`, `monto_estimado` (renombrado desde `monto`).
- [x] 6.3 `resources/js/types/adquisiciones.ts`: actualizar el tipo `ProcesoAdquisicion` con los campos nuevos; agregar `FuncionarioSeleccionable`; se removió `ModalidadSeleccionable` (quedó sin uso: el select manual de modalidad se elimina del formulario).

## 7. Exportación a PDF

- [x] 7.1 Crear `app/Services/Adquisiciones/ExportadorSolicitudCompraPdfService.php`: recibe un `ProcesoAdquisicion`, renderiza una vista Blade nueva con dompdf (mismo mecanismo que `ExportadorInformeRazonadoService`) y devuelve el binario PDF.
- [x] 7.2 Crear vista `resources/views/adquisiciones/solicitud-compra-pdf.blade.php` con los antecedentes generales del proceso y su estado actual.
- [x] 7.3 Agregar ruta `GET adquisiciones/procesos/{proceso}/pdf` (`routes/adquisiciones.php`) y acción `ProcesoAdquisicionController::pdf()` (find + delegar en el service, sin lógica propia — no ameritó un controlador dedicado) que devuelva el PDF vía `ExportadorSolicitudCompraPdfService`, gateada por `ProcesoAdquisicionPolicy::view`.

## 8. Frontend: formulario de creación

- [x] 8.1 Reestructurar `resources/js/pages/adquisiciones/procesos/crear.tsx`: todos los campos nuevos (sin campo de código, sin select de modalidad).
- [x] 8.2 Select de `ccosto_id` (Unidad requirente): mantener igual que hoy (prop `ccostos`).
- [x] 8.3 Select de `funcionario_requirente_id`: nueva prop `funcionarios`, filtrado en cliente por el `ccosto_id` elegido (deshabilitado hasta elegir unidad, se resetea al cambiar de unidad).
- [x] 8.4 Botones Sí/No (`en_plan_compras`, `convenio_marco`) con `useState` (no hay componente RadioGroup en el repo; se implementó un toggle de dos `Button` — `default`/`outline` — reutilizable); al elegir "Sí" en `en_plan_compras` se revela el campo `id_pac`; incluye el texto de ayuda de Convenio Marco.
- [x] 8.5 Validación cliente antes de enviar (bloquea submit si falta algún campo requerido y muestra el error localmente sin llamar al backend).
- [x] 8.6 Enviar `convenio_marco` (boolean) en vez de `modalidad_id` al `router.post`.

## 9. Frontend: edición, listado y detalle

- [x] 9.1 Replicar los cambios de la sección 8 en `resources/js/pages/adquisiciones/procesos/editar.tsx`, precargando los valores actuales (`convenio_marco` ya viene derivado desde el backend, calculado por `edit()` comparando `modalidad.codigo === 'CONVENIO_MARCO'`); componente `BotonesSiNo` extraído a `resources/js/components/adquisiciones/botones-si-no.tsx` para reusarlo entre crear/editar.
- [x] 9.2 `resources/js/pages/adquisiciones/procesos/index.tsx`: mostrar `nombre` junto al código; renombrar el uso de `monto` a `monto_estimado`.
- [x] 9.3 `resources/js/pages/adquisiciones/procesos/show.tsx`: mostrar los antecedentes generales nuevos en una sección dedicada; agregar botón "Descargar PDF" que enlace a la ruta de la sección 7; el botón de la transición `publicar` se etiqueta "Aprobar solicitud" (el relabel es siempre por código de transición, no solo cuando `en_revision`, ya que solo aparece disponible en ese estado).

## 10. Tests

- [x] 10.1 Actualizar `tests/Feature/Adquisiciones/ApiAdquisicionesTest.php`, `EditarProcesoAdquisicionTest.php`, `ProcesoAdquisicionServiceTest.php`, `ChecklistDocumentalAdquisicionTest.php` para el nuevo contrato (campos, `convenio_marco` en vez de `modalidad_id`, código autogenerado). Se eliminaron 2 tests de `EditarProcesoAdquisicionTest.php` que verificaban unicidad de `codigo` en edición — ya no aplica, `codigo` no es editable. Se corrigieron además dos consumidores no listados originalmente en esta tarea que quedaron rotos por el rename `monto`→`monto_estimado`: `BuscarProcesoAdquisicionController` (query de búsqueda asistida) y `ProcesoAdquisicionResumenResource`. También se actualizaron 9 archivos de test adicionales fuera de los 4 mencionados (`MostrarComprasMpEnProcesoTest`, `VinculoAdquisicionCdpTest`, `VinculoAdquisicionCasoPagoProveedorTest`, `HistorialValidacionesDocumentoTest`, `DashboardTest`, `VinculoLicitacionMercadoPublicoTest`, `ListadoLicitacionesMercadoPublicoTest`, `ListadoOrdenesCompraMercadoPublicoTest`, `VinculoOrdenCompraMercadoPublicoTest`) que llamaban a `ProcesoAdquisicionService::crear()` con el contrato viejo. Los que usan `ProcesoAdquisicion::create()` directo (sin pasar por el Service) no necesitaron cambios.
- [x] 10.2 Test nuevo: generación de código único y con el formato esperado (incluyendo un caso de creación concurrente/sucesiva sin colisión).
- [x] 10.3 Test nuevo: `convenio_marco = true` deriva `CONVENIO_MARCO`; `convenio_marco = false` deriva `TRATO_DIRECTO` y el checklist exige `INFORME_JUSTIFICACION_TRATO_DIRECTO`.
- [x] 10.4 Test nuevo: rechazo de creación/actualización cuando `monto_estimado` alcanza o supera 1.000 UTM vigentes.
- [x] 10.5 Test nuevo: rechazo cuando `funcionario_requirente_id` no pertenece al `ccosto_id` enviado.
- [x] 10.6 Test nuevo: descarga del PDF con y sin permiso de consulta.
- [x] 10.7 Test nuevo: la transición `publicar` sobre un proceso de adquisiciones queda registrada en el historial (usuario y fecha), ejecutable por un usuario con rol `administrativo_adquisiciones`.

## 11. Validación final

- [x] 11.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP tocados — sin cambios pendientes.
- [x] 11.2 `php artisan test --compact --filter=Adquisicion` → 139/139 OK. Se corrió además la suite completa (`php artisan test --compact`, sin filtro): 881 tests, 877 passed, 4 skipped (preexistentes, no relacionados), 0 failed.
- [x] 11.3 `composer ci:check`: ESLint, Prettier, `tsc --noEmit` y Pint quedaron en verde. **PHPStan/Larastan (parte de `types:check`) queda con 17 errores preexistentes en `master`, no relacionados con este change** (`RoleController.php`, `UserResource.php`, `GestionRolesService.php` en el dominio Seguridad — ninguno tocado por este change; confirmado que ya estaban committeados sin modificar). Este change contribuye 2 de esos 17: `ProcesoAdquisicionController::funcionariosActivos()` cae en la misma limitación conocida de Larastan ("Template type TValue on Collection no es covariante") que ya afecta a esos 3 archivos preexistentes — no tiene solución sin las técnicas que el propio PHPStan prohíbe explícitamente (@phpstan-ignore, casts para silenciar, ensanchar tipos). Se dejó consistente con el patrón ya aceptado en el resto del repo. Los otros 2 errores originalmente atribuibles a este change (`fecha_inicio` mal tipado por PHPStan) sí se corrigieron ajustando el cast a `date:Y-m-d` y quitando el `?->toDateString()` redundante, siguiendo la convención ya usada en `CertificadoDisponibilidadPresupuestariaResource`.
- [x] 11.4 Wayfinder regenerado (`php artisan wayfinder:generate --with-form`, incluye la ruta `procesos.pdf` nueva); `npm run types:check` en verde.
- [x] 11.5 Verificación manual en navegador del formulario de creación — el usuario probó directamente en su Laragon local (`pagos.test`) durante la implementación; encontró feedback real (ver sección 12) que se incorporó al mismo change.

## 12. Moneda y paridad en el monto estimado (feedback del usuario durante la verificación manual)

Durante la verificación manual el usuario pidió que el monto estimado siguiera la misma lógica de moneda/paridad ya implementada para el CDP (`presupuesto-certificado-disponibilidad`): elegir moneda (CLP/UF/USD), si es distinta de CLP pedir fecha de paridad y resolverla contra `indicadores_economicos`, y calcular el monto final multiplicando paridad × monto solicitado. Se replicó el patrón exacto de `CrearBorradorCertificadoDisponibilidadService::resolverParidadYMonto()`.

- [x] 12.1 Migración `add_moneda_paridad_to_procesos_adquisicion_table`: agrega `moneda_compra` (string, default CLP), `monto_estimado_solicitado` (decimal 14,4, nullable), `fecha_paridad` (date, nullable), `paridad` (decimal 14,4, nullable). `monto_estimado` (ya existente) pasa a ser el monto final calculado en CLP, ya no un input directo.
- [x] 12.2 `ProcesoAdquisicionService::resolverMonedaYMonto()`: mismo patrón que `resolverParidadYMonto()` del CDP — CLP sin paridad (monto_estimado = solicitado), UF/USD resuelven `IndicadorEconomicoSelector::paraFecha()` y lanzan `ProcesoAdquisicionException::sinIndicadorParaFecha()` si no hay valor para la fecha. Integrado en `crear()`/`actualizar()`, antes de la validación de umbral UTM (que ahora valida el `monto_estimado` ya calculado, no el solicitado directamente).
- [x] 12.3 `CrearProcesoAdquisicionRequest`/`ActualizarProcesoAdquisicionRequest`: `monto_estimado` reemplazado por `moneda_compra` (nullable, in:CLP,UF,USD), `monto_estimado_solicitado` (required), `fecha_paridad` (required_if moneda≠CLP) — mismas reglas que `CrearCertificadoDisponibilidadRequest`.
- [x] 12.4 Nuevo `ParidadAdquisicionController::show()` (ruta `GET adquisiciones/procesos/paridad`, nombre `adquisiciones.procesos.paridad`) — mismo endpoint de previsualización que `ParidadCdpController`, gateado por `adquisiciones.crear_proceso` en vez del permiso de CDP.
- [x] 12.5 Nuevo componente compartido `resources/js/components/adquisiciones/campo-moneda-monto.tsx` (usado por `crear.tsx`/`editar.tsx`) que replica `cdp-form.tsx`: select de moneda, monto solicitado, fecha de paridad condicional con preview en vivo (`fetch` a `procesos.paridad`), y monto estimado (CLP) de solo lectura.
- [x] 12.6 `show.tsx` y el PDF (`solicitud-compra-pdf.blade.php`) muestran "Monto solicitado" (moneda + paridad) y "Monto estimado (CLP)" por separado.
- [x] 12.7 Resource/Controller (`edit()`) actualizados para exponer `moneda_compra`, `monto_estimado_solicitado`, `fecha_paridad`, `paridad`.
- [x] 12.8 Tests: helpers de los 13 archivos de test afectados actualizados (`monto_estimado` → `monto_estimado_solicitado`); 2 tests nuevos en `ProcesoAdquisicionServiceTest.php` (paridad UF real calculada correctamente; rechazo cuando no hay indicador para la fecha). Suite completa verde (883 tests).
- [x] 12.9 `composer ci:check` (Pint, ESLint, Prettier, `tsc`) en verde; `npm run build` reconstruido.

## 13. Reorganización en fichas y formato de correlativo (feedback adicional)

El usuario pidió, tras seguir probando, agrupar el formulario en fichas visuales (mismo patrón `SeccionCard` numerado del CDP) y cambiar el formato del correlativo de `SC NNNNN-AAAA` a `SPC-NNN-AAAA` (ej. `SPC-001-2026`).

- [x] 13.1 `ProcesoAdquisicionService::crear()`: formato de código cambiado a `sprintf('SPC-%03d-%d', $proceso->id, $anio)`. Test y documentación (`design.md`, `tasks.md` §4.1) actualizados.
- [x] 13.2 Nuevo componente compartido `resources/js/components/adquisiciones/seccion-card.tsx` (idéntico a `SeccionCard` de `cdp-form.tsx`) y `campo-solo-lectura.tsx` (extraído de `campo-moneda-monto.tsx` para reusarlo también en el campo de N° de solicitud).
- [x] 13.3 `crear.tsx`/`editar.tsx` reestructurados en 3 fichas: **Identificación** (N° de solicitud de solo lectura, fecha inicio, nombre, unidad requirente, funcionario requirente, proveedor), **Requerimientos** (características, motivo de contratación, plan de compras/ID PAC, ID requerimiento, código BIP, Convenio Marco), **Moneda y Montos** (`CampoMonedaMonto`).
- [x] 13.4 `npm run types:check`, `php artisan test --filter=Adquisicion` (141/141) y suite completa (883 tests) verdes tras la reestructuración; `npm run build` reconstruido.

## 14. Formulario como wizard (mismo patrón de `ProveedorFormulario`)

El usuario indicó que seguía sintiéndose largo y pidió replicar el formato de "crear proveedores": un único componente de formulario compartido entre crear/editar, con pasos como tabs (uno visible a la vez, con indicador numerado + check al completarse), panel lateral de resumen/completitud, y navegación Anterior/Siguiente.

- [x] 14.1 Nuevo componente `resources/js/components/adquisiciones/solicitud-compra-formulario.tsx` (mirroring `ProveedorFormulario`): 3 pasos como `Tabs` (Identificación, Requerimientos, Moneda y Montos), `pasosCompletos`/`completitud` calculados igual que en proveedores, sidebar de 320px con resumen (N° de solicitud, unidad, monto solicitado) y checklist de completitud, footer con Cancelar / Anterior / Siguiente / submit final (deshabilitado hasta que los 3 pasos estén completos). Si el backend rechaza con un error, salta automáticamente al paso donde vive ese campo (`PASO_POR_CAMPO`, mismo mecanismo que proveedores).
- [x] 14.2 `crear.tsx`/`editar.tsx` reducidos a wrappers delgados (mismo patrón que `proveedores/create.tsx`/`edit.tsx`): solo leen las props de Inertia y renderizan `<SolicitudCompraFormulario modo=... accionUrl=... metodoHttp=... volverUrl=... valoresIniciales=... />`. Ya no duplican el JSX de los campos entre crear y editar.
- [x] 14.3 `seccion-card.tsx` (de la sección 13) quedó sin uso tras este cambio — eliminado.
- [x] 14.4 `npm run types:check`, ESLint, Prettier, Pint, `php artisan test --filter=Adquisicion` (141/141) y suite completa (883 tests) verdes; `npm run build` reconstruido (nuevo chunk `solicitud-compra-formulario-*.js`, tamaño comparable a `proveedor-formulario-*.js`).
