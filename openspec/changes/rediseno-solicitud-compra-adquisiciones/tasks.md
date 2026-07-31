## 1. Migración de base de datos

- [ ] 1.1 Migración `add_solicitud_compra_fields_to_procesos_adquisicion_table`: agregar `fecha_inicio` (date, nullable), `nombre` (string, nullable), `id_requerimiento` (string, nullable), `funcionario_requirente_id` (foreignId nullable → `funcionarios`, `nullOnDelete`), `caracteristicas` (text, nullable), `motivo_contratacion` (text, nullable), `en_plan_compras` (boolean, nullable), `id_pac` (string, nullable), `codigo_bip` (string, nullable) a `procesos_adquisicion`.
- [ ] 1.2 Migración `rename_monto_to_monto_estimado_in_procesos_adquisicion_table`: `renameColumn('monto', 'monto_estimado')`.
- [ ] 1.3 Migración `make_objeto_nullable_in_procesos_adquisicion_table`: `objeto` pasa a `nullable()`.

## 2. Modelo

- [ ] 2.1 `app/Models/ProcesoAdquisicion.php`: actualizar `$fillable` con los campos nuevos y `monto_estimado`; actualizar `casts()` (`en_plan_compras` => boolean, `fecha_inicio` => date, `monto_estimado` => decimal:2).
- [ ] 2.2 Agregar relación `funcionarioRequirente(): BelongsTo` → `Funcionario` en `ProcesoAdquisicion`.

## 3. Seeders de datos de soporte

- [ ] 3.1 Agregar `TipoDocumento` `INFORME_JUSTIFICACION_TRATO_DIRECTO` (activo) en el seeder de tipos de documento correspondiente (`database/seeders/TiposDocumentoSeeder.php`).
- [ ] 3.2 `database/seeders/RequisitosDocumentalesAdquisicionesSeeder.php`: agregar `INFORME_JUSTIFICACION_TRATO_DIRECTO` como obligatorio para la modalidad `TRATO_DIRECTO`.
- [ ] 3.3 `database/seeders/WorkflowAdquisicionesSeeder.php`: otorgar el permiso `adquisiciones.publicar` también al rol `administrativo_adquisiciones` (ver design.md § "Alcance del permiso de aprobación" — hoy solo lo tiene `admin`, y la nómina real mapea la jefatura de Adquisiciones a ese rol).

## 4. Servicio de dominio (`ProcesoAdquisicionService`)

- [ ] 4.1 `app/Services/Adquisiciones/ProcesoAdquisicionService::crear()`: generar `codigo` automáticamente siguiendo el patrón de `CrearBorradorCertificadoDisponibilidadService` (insertar con placeholder único, luego `update(['codigo' => sprintf('SC %05d-%d', $proceso->id, $anio)])` dentro de la misma transacción; `$anio` desde `fecha_inicio`).
- [ ] 4.2 `ProcesoAdquisicionService::crear()`: resolver `modalidad_id` a partir del booleano `convenio_marco` recibido (`true` → `ModalidadAdquisicion` con `codigo = 'CONVENIO_MARCO'`, `false` → `codigo = 'TRATO_DIRECTO'`, ambas vía `firstOrFail()`), reemplazando la validación de "modalidad enviada activa".
- [ ] 4.3 `ProcesoAdquisicionService::crear()` y `actualizar()`: sincronizar `objeto = caracteristicas` antes de persistir.
- [ ] 4.4 `ProcesoAdquisicionService::actualizar()`: aplicar la misma derivación de modalidad desde `convenio_marco` (punto 4.2) al actualizar, manteniendo la regla existente de solo permitir edición en `borrador`.
- [ ] 4.5 Agregar método `ProcesoAdquisicionService::validarMontoBajoUmbralUtm(float $montoEstimado, CarbonInterface $fecha): void` que resuelva la UTM vigente vía `IndicadorEconomicoSelector::paraPeriodo('UTM', ...)` (inyectado por constructor) y lance `ProcesoAdquisicionException` si `$montoEstimado >= 1000 * $utm->valor`; invocarlo desde `crear()` y `actualizar()`.
- [ ] 4.6 Agregar caso a `app/Exceptions/ProcesoAdquisicionException.php` para el rechazo por umbral UTM (mensaje descriptivo).

## 5. Validación HTTP (Form Requests)

- [ ] 5.1 `CrearProcesoAdquisicionRequest`: reemplazar `modalidad_id` por `convenio_marco: required|boolean`; agregar reglas para `fecha_inicio` (required|date), `nombre` (required|string), `id_requerimiento` (nullable|string), `ccosto_id` (ya existe, mantener), `funcionario_requirente_id` (required|integer), `caracteristicas` (required|string), `motivo_contratacion` (required|string), `en_plan_compras` (required|boolean), `id_pac` (nullable|string), `codigo_bip` (nullable|string), `monto_estimado` (required|numeric|min:0); quitar `codigo` del payload aceptado (se genera en el Service, no se valida como input).
- [ ] 5.2 `CrearProcesoAdquisicionRequest`: agregar `withValidator()` que rechace cuando el `funcionario_requirente_id` enviado no pertenezca al `ccosto_id` enviado (ver design.md).
- [ ] 5.3 Replicar 5.1 y 5.2 en `ActualizarProcesoAdquisicionRequest` (mismas reglas; `codigo` no es editable, se excluye igual que en creación).

## 6. Controlador y Resource

- [ ] 6.1 `ProcesoAdquisicionController::create()` y `edit()`: reemplazar la carga de `modalidades` por `Funcionario::where('activo', true)->get(['id', 'nombre', 'cargo', 'ccosto_id'])`, manteniendo `ccostos` y `proveedores` como hoy.
- [ ] 6.2 `ProcesoAdquisicionResource`: exponer `fecha_inicio`, `nombre`, `id_requerimiento`, `funcionario_requirente` (id, nombre, cargo), `caracteristicas`, `motivo_contratacion`, `en_plan_compras`, `id_pac`, `codigo_bip`, `monto_estimado` (renombrado desde `monto`).
- [ ] 6.3 `resources/js/types/adquisiciones.ts`: actualizar el tipo `ProcesoAdquisicion` y `ProcesoAdquisicionResumenResource`-equivalente con los campos nuevos; agregar `FuncionarioSeleccionable`.

## 7. Exportación a PDF

- [ ] 7.1 Crear `app/Services/Adquisiciones/ExportadorSolicitudCompraPdfService.php`: recibe un `ProcesoAdquisicion`, renderiza una vista Blade nueva con dompdf (mismo mecanismo que `ExportadorInformeRazonadoService`) y devuelve el binario PDF.
- [ ] 7.2 Crear vista `resources/views/adquisiciones/solicitud-compra-pdf.blade.php` con los antecedentes generales del proceso y su estado actual.
- [ ] 7.3 Agregar ruta `GET adquisiciones/procesos/{proceso}/pdf` (`routes/adquisiciones.php`) y acción en `ProcesoAdquisicionController` (o un controlador dedicado si el método no es un simple `find + stream`, siguiendo la disciplina de controladores livianos) que devuelva el PDF vía `ExportadorSolicitudCompraPdfService`, gateada por `ProcesoAdquisicionPolicy::view`.

## 8. Frontend: formulario de creación

- [ ] 8.1 Reestructurar `resources/js/pages/adquisiciones/procesos/crear.tsx`: sección "Antecedentes Generales" con todos los campos nuevos (sin campo de código, sin select de modalidad).
- [ ] 8.2 Select de `ccosto_id` (Unidad requirente): mantener igual que hoy (prop `ccostos`).
- [ ] 8.3 Select de `funcionario_requirente_id`: nueva prop `funcionarios`, filtrado en cliente por el `ccosto_id` elegido (deshabilitado u opciones vacías hasta elegir unidad).
- [ ] 8.4 Radios Sí/No (`en_plan_compras`, `convenio_marco`) con `useState`; al elegir "Sí" en `en_plan_compras` se revela el campo `id_pac`; incluir el texto de ayuda de Convenio Marco (informe de justificación si "No").
- [ ] 8.5 Validación cliente antes de enviar (bloquear submit si falta algún campo requerido y mostrar el error localmente), siguiendo el ejemplo de `egresos-cgu/crear.tsx`.
- [ ] 8.6 Enviar `convenio_marco` (boolean) en vez de `modalidad_id` al `router.post`.

## 9. Frontend: edición, listado y detalle

- [ ] 9.1 Replicar los cambios de la sección 8 en `resources/js/pages/adquisiciones/procesos/editar.tsx`, precargando los valores actuales (incluyendo derivar el Sí/No de Convenio Marco desde la modalidad actual del proceso).
- [ ] 9.2 `resources/js/pages/adquisiciones/procesos/index.tsx`: mostrar `nombre` junto al código; renombrar el uso de `monto` a `monto_estimado`.
- [ ] 9.3 `resources/js/pages/adquisiciones/procesos/show.tsx`: mostrar los antecedentes generales nuevos; agregar botón "Descargar PDF" que enlace a la ruta de la sección 7; relabel contextual del botón de la transición `publicar` a "Aprobar solicitud" cuando el proceso está `en_revision`.

## 10. Tests

- [ ] 10.1 Actualizar `tests/Feature/Adquisiciones/ApiAdquisicionesTest.php`, `EditarProcesoAdquisicionTest.php`, `ProcesoAdquisicionServiceTest.php`, `ChecklistDocumentalAdquisicionTest.php` para el nuevo contrato (campos, `convenio_marco` en vez de `modalidad_id`, código autogenerado).
- [ ] 10.2 Test nuevo: generación de código único y con el formato esperado (incluyendo un caso de creación concurrente/sucesiva sin colisión).
- [ ] 10.3 Test nuevo: `convenio_marco = true` deriva `CONVENIO_MARCO`; `convenio_marco = false` deriva `TRATO_DIRECTO` y el checklist exige `INFORME_JUSTIFICACION_TRATO_DIRECTO`.
- [ ] 10.4 Test nuevo: rechazo de creación/actualización cuando `monto_estimado` alcanza o supera 1.000 UTM vigentes.
- [ ] 10.5 Test nuevo: rechazo cuando `funcionario_requirente_id` no pertenece al `ccosto_id` enviado.
- [ ] 10.6 Test nuevo: descarga del PDF con y sin permiso de consulta.
- [ ] 10.7 Test nuevo: la transición `publicar` sobre un proceso de adquisiciones queda registrada en el historial (usuario y fecha), ejecutable por un usuario con rol `administrativo_adquisiciones`.

## 11. Validación final

- [ ] 11.1 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP tocados.
- [ ] 11.2 `php artisan test --compact --filter=Adquisicion`.
- [ ] 11.3 `composer ci:check` completo.
- [ ] 11.4 Regenerar Wayfinder si se agregó una ruta nueva (`php artisan wayfinder:generate --with-form`) y verificar `npm run types:check`.
- [ ] 11.5 Verificación manual en navegador del formulario de creación (flujo completo: elegir unidad → funcionario filtrado → Plan de Compras revela ID PAC → Convenio Marco → crear → ver detalle → aprobar vía transición → descargar PDF), coordinando con el usuario dado que el navegador integrado no llega al Laragon local.
