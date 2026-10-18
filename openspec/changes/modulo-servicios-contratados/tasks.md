## 1. Base de datos y modelo

- [x] 1.1 Migración `servicios_contratados` (contrato_id FK, nombre, categoria, monto_mensual_esperado, moneda, fecha_inicio, fecha_fin, ccosto_id nullable, administrador_funcionario_id nullable, observaciones, terminado_en nullable; índices y nombres de FK explícitos < 63 caracteres)
- [x] 1.2 Modelo `ServicioContratado` (constantes de categoría, casts, relaciones a Contrato/Ccosto/Funcionario, estado derivado y scope por estado; sin factory, igual que Contrato)
- [x] 1.3 Config `servicios_contratados.dias_alerta_vencimiento` (60)

## 2. Servicios y autorización

- [x] 2.1 `ServicioContratadoService` (registrar, actualizar, terminar; valida contrato aprobado y vigencia contenida; auditoría vía trait `RegistraAuditoria` del modelo; excepción de dominio con campo)
- [x] 2.2 `PeriodosServicioContratadoService` (períodos mensuales derivados, estados pagado/en_tramite/sin_caso/ambiguo, ignora `periodo` no `YYYY-MM`)
- [x] 2.3 `ServiciosContratadosSeeder` (permisos ver/crear/editar/terminar; roles admin y administrativo_finanzas) y registro en `DatabaseSeeder`; actualizar `RolesAndPermissionsSeederTest` solo si corresponde
- [x] 2.4 `ServicioContratadoPolicy` registrada en `AppServiceProvider::configureAuthorization()`

## 3. HTTP

- [x] 3.1 Form Requests Store/Update, Resources (con estado derivado y alerta) y `ServicioContratadoController` liviano
- [x] 3.2 `routes/servicios-contratados.php` requerido desde `routes/web.php`; regenerar Wayfinder

## 4. Frontend

- [x] 4.1 Tipos en `resources/js/types/servicios-contratados.ts`
- [x] 4.2 Páginas `servicios-contratados/index` (listado denso, búsqueda con debounce, filtro por estado), `show` (datos, alerta, tabla de períodos, acción terminar), `create`, `edit`
- [x] 4.3 Ítem de sidebar condicionado por `servicios_contratados.ver`

## 5. Pruebas y verificación

- [x] 5.1 Tests Feature en `tests/Feature/ServiciosContratados/` (registro, validaciones de contrato/vigencia, edición, terminar, estado derivado, períodos incl. ambiguo y periodo inválido, permisos)
- [x] 5.2 `composer lint`, `composer types:check`, `npm run types:check`, `npm run lint:check`, suite de tests
- [ ] 5.3 Verificación visual en navegador (listado, detalle con períodos, alta)
- [x] 5.4 Actualizar CLAUDE.md y HARNESS_IA.md (módulo implementado)
