## 1. Migraciones y modelos

- [x] 1.1 Migración `create_certificados_disponibilidad_presupuestaria_table`: `id`, `folio`
      (string, unique), `presupuesto_id` (fk `presupuestos`, restrictOnDelete), `cfinanciero_id`
      (fk `cfinancieros`, restrictOnDelete), `denominacion`/`unidad_ejecutora`/`n_ue`/`subtitulo`
      (string, denormalizados), `tipo_gasto` (string), `codigo_iniciativa` (string, nullable),
      `nombre` (text), `programa_presupuestario` (string, default `100`), `caracter_gasto`
      (string), `moneda_compra` (string, default `CLP`), `total_moneda_compra` (decimal 14,4),
      `paridad` (decimal 14,4, nullable), `monto` (decimal 14,2), `anio_validez` (unsignedSmallInteger),
      `requerimiento_numero` (string, nullable), `mercado_publico_tipo` (string, nullable),
      `mercado_publico_id` (unsignedBigInteger, nullable), `proceso_adquisicion_id` (fk
      `procesos_adquisicion`, nullable, nullOnDelete), `saldo_disponible_al_emitir` (decimal 14,2,
      nullable), `hubo_sobregiro_al_emitir` (boolean, default false), `cdp_original_id` (fk self,
      nullable, nullOnDelete), `firmado_por` (fk users, nullable, nullOnDelete), `firmado_en`
      (timestamp, nullable), timestamps. **Sin columna `estado`** — vive en `Proceso` vía
      `sujeto`.
- [x] 1.2 Migración `create_movimientos_presupuestarios_table`: `id`, `presupuesto_id` (fk,
      restrictOnDelete), `tipo` (string: `compromiso`\|`liberacion_compromiso`\|`ejecucion`),
      `monto` (decimal 14,2, puede ser negativo), `origen_type`/`origen_id` (polimórfico,
      indexado), `user_id` (fk users, nullable, nullOnDelete), `observacion` (text, nullable),
      timestamps.
- [x] 1.3 Modelo `App\Models\Presupuesto\CertificadoDisponibilidadPresupuestaria`: fillable según
      1.1, casts (`monto`, `total_moneda_compra`, `paridad`, `saldo_disponible_al_emitir` =>
      `decimal:2`/`decimal:4`, `hubo_sobregiro_al_emitir` => `boolean`, `firmado_en` =>
      `datetime`), relaciones `belongsTo(Presupuesto)`, `belongsTo(Cfinanciero)`,
      `belongsTo(ProcesoAdquisicion, nullable)`, `belongsTo(self, 'cdp_original_id')`,
      `belongsTo(User, 'firmado_por')`, y `MorphOne proceso()` hacia `App\Models\Proceso` (mismo
      patrón que `ProcesoAdquisicion::proceso()`).
- [x] 1.4 Modelo `App\Models\Presupuesto\MovimientoPresupuestario`: fillable según 1.2, cast
      `monto => decimal:2`, relaciones `belongsTo(Presupuesto)`, `morphTo('origen')`.
- [x] 1.5 `App\Models\Adquisiciones\ProcesoAdquisicion`: agregar `hasMany(CertificadoDisponibilidadPresupuestaria)`
      como `cdps()`.
- [x] 1.6 `App\Models\Proceso::descriptorNotificacion()`: agregar el caso para sujeto
      `CertificadoDisponibilidadPresupuestaria` (descripción humana + URL de detalle).

## 2. Workflow del CDP

- [x] 2.1 `database/seeders/WorkflowPresupuestoCdpSeeder.php` (mismo molde que
      `WorkflowAdquisicionesSeeder.php`): crea permisos `presupuesto.crear_cdp`,
      `presupuesto.firmar_cdp`, `presupuesto.anular_cdp`; `DefinicionWorkflow`
      (`codigo: presupuesto_cdp`, `activo: true`); `EstadoWorkflow` `borrador` (`es_inicial:
      true`) y `firmado` (`es_final: true`); `TransicionWorkflow` `firmar` (`de: borrador`,
      `a: firmado`, `permiso_requerido: presupuesto.firmar_cdp`, sin `documentos_requeridos`).
- [x] 2.2 Registrar `WorkflowPresupuestoCdpSeeder` en `DatabaseSeeder` junto a los demás seeders de
      workflow.
- [x] 2.3 `database/seeders/TiposDocumentoSeeder.php`: agregar `TipoDocumento` `codigo: CDP`
      (`nombre: Certificado de Disponibilidad Presupuestaria`).

## 3. Services de dominio

- [x] 3.1 `App\Services\Presupuesto\CrearBorradorCertificadoDisponibilidadService::crear(array
      $datos): CertificadoDisponibilidadPresupuestaria` — valida que la línea `presupuesto`
      indicada exista y esté vigente, resuelve `denominacion`/`unidad_ejecutora`/`n_ue`/`subtitulo`
      por snapshot desde `catalogo`/`cfinanciero`, asigna folio con correlativo **global** (único,
      autonumérico, no reinicia por año; `lockForUpdate()` sobre el último folio creado para
      evitar colisión concurrente), y dentro
      de una `DB::transaction` crea el CDP + su `Proceso` (`definicion_workflow_id` de
      `presupuesto_cdp`, `estado_actual_id` = estado `es_inicial`, `sujeto_type/sujeto_id` = el
      CDP, `monto` = el del CDP, `iniciado_por` = usuario autenticado) — igual patrón que
      `ProcesoAdquisicionService::crear()`. NO pasa por `TransicionWorkflowService`.
- [x] 3.2 `App\Services\Presupuesto\CrearBorradorCertificadoDisponibilidadService::actualizar(...)`
      — solo permite editar mientras `$cdp->proceso->estadoActual->codigo === 'borrador'`.
- [x] 3.3 `App\Services\Presupuesto\CalculadorSaldoPresupuestoService::disponible(Presupuesto
      $presupuesto): float` — implementa la fórmula `monto_asignado − (Σcompromiso −
      Σliberacion_compromiso) − Σejecucion` sobre `movimientos_presupuestarios`, con
      `lockForUpdate()` sobre las filas de movimientos de esa línea cuando se llama dentro de una
      transacción de firma (evita condición de carrera entre dos firmas concurrentes contra la
      misma línea).
- [x] 3.4 `App\Services\Presupuesto\FirmarCertificadoDisponibilidadService::firmar(CertificadoDisponibilidadPresupuestaria
      $cdp, ?string $comentario = null): CertificadoDisponibilidadPresupuestaria` — dentro de una
      `DB::transaction`: calcula saldo vía 3.3, marca `hubo_sobregiro_al_emitir` sin bloquear si
      `monto > disponible`, llama a `TransicionWorkflowService::execute($cdp->proceso, 'firmar',
      $comentario)`, guarda `firmado_por`/`firmado_en`/`saldo_disponible_al_emitir` en el CDP,
      crea `MovimientoPresupuestario` tipo `compromiso` (`origen` = el CDP), genera el PDF (ver
      grupo 4) y lo registra como `Documento` tipo `CDP` con `VinculoDocumento` activo hacia
      `$cdp->proceso` y, si `proceso_adquisicion_id` está seteado, también hacia
      `$cdp->procesoAdquisicion->proceso`.
- [x] 3.5 `App\Services\Presupuesto\AnularCertificadoDisponibilidadService::anular(CertificadoDisponibilidadPresupuestaria
      $cdpOriginal, ?string $comentario = null): CertificadoDisponibilidadPresupuestaria` — dentro
      de una `DB::transaction`: llama a 3.1 para crear el borrador de anulación (mismo
      `presupuesto_id`/`cfinanciero_id`/cuenta que el original, `monto` = `-$cdpOriginal->monto`,
      mismo `requerimiento_numero`, `cdp_original_id` = `$cdpOriginal->id`), luego llama a 3.4 para
      firmarlo inmediatamente. Requiere permiso `presupuesto.anular_cdp` (verificado en el
      controlador/policy, no en el service).

## 4. Plantilla PDF y expediente

- [x] 4.1 `resources/views/presupuesto/cdp.blade.php`: replica exacta de la plantilla real
      (folio, CF/ST, checkbox Gasto Operacional/Iniciativa, Nombre, Código Iniciativa, Cuenta
      Presupuestaria, Denominación, Unidad Ejecutora, Monto Impto Incluido, N° UE, Validez,
      Carácter del Gasto, Medio de Solicitud, Requerimiento N°, Moneda de Compra, Total Moneda de
      Compra, bloque de firma electrónica, notas 1 y 2, texto legal). **Sin campo `Programa
      Presupuestario`** — no se imprime.
- [x] 4.2 Texto legal (Art. 3° DS 250 + Ley de Presupuestos del año) como parámetro configurable
      (`config('presupuesto.ley_presupuestos_por_anio')`), no hardcodeado en la vista.
- [x] 4.3 Generación con `barryvdh/laravel-dompdf` desde `FirmarCertificadoDisponibilidadService`
      (grupo 3.4) — reutiliza `ExportadorInformeRazonadoService` como referencia de convención de
      generación de PDF, sin acoplarse a él (dominios distintos).

## 5. Autorización, rutas y controladores

- [x] 5.1 `App\Policies\Presupuesto\CertificadoDisponibilidadPresupuestariaPolicy`: `view`/`create`/`update`
      gateados por `presupuesto.consultar`/`presupuesto.crear_cdp`/`presupuesto.crear_cdp`
      (edición solo si borrador, verificado en el service — la policy solo gatea el permiso).
- [x] 5.2 Registrar la policy en `AppServiceProvider::configureAuthorization()`.
- [x] 5.3 `App\Http\Requests\Presupuesto\CrearCertificadoDisponibilidadRequest` /
      `ActualizarCertificadoDisponibilidadRequest`: validan los campos de 1.1 (incluye regla
      condicional `paridad` requerido si `moneda_compra != CLP`, `codigo_iniciativa` requerido si
      `tipo_gasto = INI`).
- [x] 5.4 `App\Http\Controllers\Presupuesto\CertificadoDisponibilidadPresupuestariaController`:
      `index`/`store`/`update`/`show` — delega a los services del grupo 3, sin lógica de negocio
      en el controlador (solo mapear request → service → resource).
- [x] 5.5 `App\Http\Controllers\Presupuesto\TransicionCertificadoDisponibilidadController@store`
      — mismo patrón que `TransicionProcesoAdquisicionController`: recibe `codigo`/`comentario`
      vía `App\Http\Requests\Presupuesto\EjecutarTransicionCdpRequest`, y si `codigo === 'firmar'`
      delega a `FirmarCertificadoDisponibilidadService::firmar()` (no a
      `TransicionWorkflowService::execute()` directo, porque `firmar` necesita la lógica adicional
      de saldo/movimiento/PDF del grupo 3.4).
- [x] 5.6 `App\Http\Controllers\Presupuesto\AnularCertificadoDisponibilidadController@store` —
      delega a `AnularCertificadoDisponibilidadService::anular()` (grupo 3.5).
- [x] 5.7 `App\Http\Resources\Presupuesto\CertificadoDisponibilidadPresupuestariaResource`:
      incluye `estado_actual` (desde `proceso.estadoActual.codigo`), `transiciones_disponibles`
      (desde `proceso`, mismo formato que `ProcesoResource`), y URL del PDF si está firmado.
- [x] 5.8 Rutas nuevas en `routes/presupuesto.php`: `presupuesto/cdps` (index/store/update/show),
      `presupuesto/cdps/{cdp}/transiciones` (store), `presupuesto/cdps/{cdp}/anular` (store).

## 6. Frontend

- [x] 6.1 `resources/js/pages/presupuesto/cdp/index.tsx` — listado denso (patrón de
      `openspec/specs/tema-visual-layout/spec.md`): folio, estado (badge `success`/`neutral` para
      borrador/firmado), monto, cuenta, línea de presupuesto, búsqueda con debounce 300ms,
      paginación simple.
- [x] 6.2 `resources/js/pages/presupuesto/cdp/create.tsx` / `edit.tsx` — formulario con los campos
      de 1.1; selector de línea de presupuesto, cfinanciero, moneda con cálculo en vivo de
      `total_moneda_compra × paridad` cuando la moneda no es CLP; campo `proceso_adquisicion_id`
      opcional (buscador, no obligatorio).
- [x] 6.3 `resources/js/pages/presupuesto/cdp/show.tsx` — detalle con botones de transición
      (renderizados desde `transiciones_disponibles`, igual patrón que
      `adquisiciones/procesos/show.tsx`: siempre visibles, el servidor rechaza si falta permiso),
      botón "Anular" visible solo si `estado_actual === 'firmado'`, descarga de PDF si firmado.
- [x] 6.4 `resources/js/routes/presupuesto/cdps/*` — regenerar con
      `php artisan wayfinder:generate --with-form` tras crear las rutas del grupo 5.8.
- [x] 6.5 Ítem de sidebar para CDP bajo Presupuesto, condicionado a `auth.permissions` (
      `presupuesto.consultar`), en `resources/js/components/app-sidebar.tsx`.

## 7. Tests

- [x] 7.1 `tests/Feature/Presupuesto/CrearBorradorCertificadoDisponibilidadTest.php`: crear
      borrador asigna folio y no crea movimiento; correlativo global (no se reinicia entre años
      distintos); edición permitida solo en borrador; permiso denegado.
- [x] 7.2 `tests/Feature/Presupuesto/FirmarCertificadoDisponibilidadTest.php`: firmar transiciona
      vía `TransicionWorkflowService` (verificar `historial_transiciones_workflow`); crea
      `movimiento_presupuestario` tipo `compromiso`; sobregiro no bloquea y marca
      `hubo_sobregiro_al_emitir`; un CDP firmado no ofrece transiciones de salida; genera
      `Documento` tipo `CDP` vinculado al `Proceso`; permiso denegado.
- [x] 7.3 `tests/Feature/Presupuesto/AnularCertificadoDisponibilidadTest.php`: anular crea un CDP
      nuevo con monto 100% negativo referenciando el original vía `cdp_original_id`; el original
      no cambia de estado; el saldo neto tras anular vuelve al disponible previo al CDP original;
      permiso denegado.
- [x] 7.4 `tests/Feature/Presupuesto/VinculoAdquisicionCdpTest.php`: vincular un CDP a una
      Adquisición existente; un CDP sin vínculo es válido; las transiciones de
      `WorkflowAdquisicionesSeeder` no se ven afectadas por la existencia (o ausencia) de un CDP
      vinculado.
- [x] 7.5 `tests/Feature/Presupuesto/ConsultarCertificadoDisponibilidadTest.php`: listado y
      detalle con permiso; PDF descargable solo si firmado; permiso denegado audita
      `acceso_denegado`.
- [x] 7.6 Suite completa `php artisan test` — confirmar que Workflow, Adquisiciones y Pago de
      Proveedores siguen verdes tras agregar la nueva `DefinicionWorkflow` y el nuevo caso en
      `Proceso::descriptorNotificacion()`.

## 8. Validación final

- [x] 8.1 `composer ci:check` — eslint/prettier/tsc limpios en los archivos de este change;
      `composer test`/PHPStan expone únicamente deuda preexistente no relacionada (16 errores en
      `RoleController`/`ProcesoAdquisicionResource`/`UserResource`/`GestionRolesService`,
      confirmado con `git stash -u` contra `master` antes de este change) — 0 errores nuevos.
- [x] 8.2 `vendor/bin/pint --dirty --format agent` sobre los archivos PHP tocados — en verde.
- [x] 8.3 Wayfinder regenerado (`php artisan wayfinder:generate --with-form`); `tsc --noEmit` sin
      errores.
- [x] 8.4 Comparación visual manual del PDF generado (vía los tests de Feature, que escriben el
      PDF real a `storage/app/private/documentos/cdp/`) contra los CDP reales — folio, CF/ST,
      checkboxes, detalle, bloque de firma y notas coinciden estructuralmente; ausencia de
      `Programa Presupuestario` confirmada.
- [x] 8.5 `RolesAndPermissionsSeederTest` sigue en verde — confirmado en la corrida de la suite
      completa (863/863); los permisos nuevos viven en `WorkflowPresupuestoCdpSeeder`.

## 9. Formulario real (post-implementación, a partir del mockup del usuario)

- [x] 9.1 Migración (editada sobre la original, tabla aún no archivada):
      `nombre_iniciativa`, `medio_solicitud`, `fecha_solicitud`, `fecha_paridad` — todos nullable,
      no se imprimen en el PDF.
- [x] 9.2 `CrearBorradorCertificadoDisponibilidadService::resolverParidadYMonto()`: resuelve
      `paridad` vía `IndicadorEconomicoSelector::paraFecha()` (UF/USD) y calcula `monto`
      server-side siempre — `paridad`/`monto` ya no se aceptan como input del cliente.
      `CertificadoDisponibilidadPresupuestariaException::sinIndicadorParaFecha()` si no hay
      indicador para la fecha.
- [x] 9.3 `ParidadCdpController@show` (`GET presupuesto/cdps/paridad`) — previsualización en vivo
      para el formulario, mismo selector que el servidor usa al guardar.
- [x] 9.4 `cdp-form.tsx` rediseñado según el mockup: Fecha, Medio de Solicitud, Nombre Iniciativa
      (solo INI), Denominación/Unidad Ejecutora/N° UE de solo lectura al elegir la cuenta, Fecha de
      paridad + Paridad/Monto de solo lectura y calculados en vivo. Sin EUR (sin fuente de
      paridad real).
- [x] 9.5 Corregido bug de concurrencia real en el correlativo del folio (`SELECT...LIMIT 1 FOR
      UPDATE` no serializa bajo READ COMMITTED en Postgres) — ahora usa el `id` autoincremental
      de la fila, atómico de forma nativa.
- [x] 9.6 Tests nuevos (`ParidadCdpTest.php`): resolución de paridad UF/USD real, error sin
      indicador, `nombre_iniciativa` requerido si `tipo_gasto=INI`, endpoint de previsualización.
      Suite completa: 870/870. PHPStan/Pint/tsc en verde (mismo baseline preexistente, 0 errores
      nuevos). PDF sin cambios (confirmado visualmente) — no se tocó `cdp.blade.php`.
