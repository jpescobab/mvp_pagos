## 1. Sembrar los roles dedicados

- [x] 1.1 En `database/seeders/WorkflowInformesRazonadosSeeder.php`, después de crear los permisos, crear los tres roles con `Role::firstOrCreate` y asignar su conjunto exacto con `syncPermissions` (idempotente): `gestor_reportabilidad` → [reportabilidad.ver, reportabilidad.generar_corte, reportabilidad.publicar_corte, informes.ver]; `elaborador_informes` → [informes.ver, informes.elaborar, informes.exportar]; `revisor_informes` → [informes.ver, informes.aprobar, informes.publicar, informes.exportar]. No tocar `admin` (sigue con `givePermissionTo` aditivo) ni `superadmin`.

## 2. Test de separación de deberes

- [x] 2.1 Crear un test de feature (p. ej. `tests/Feature/Seguridad/RolesDedicadosInformesTest.php` o junto al seeder de informes) que siembre `WorkflowInformesRazonadosSeeder` y afirme, por cada rol nuevo: el conjunto exacto de permisos esperado (`getPermissionNames()` ordenado) y la ausencia explícita de los de otras etapas — `elaborador_informes` no tiene `informes.aprobar` ni `informes.publicar`; `revisor_informes` no tiene `informes.elaborar`; `gestor_reportabilidad` no tiene ningún `informes.elaborar/aprobar/publicar`.
- [x] 2.2 Afirmar que la siembra es idempotente (correr el seeder dos veces deja el mismo conjunto) y que `admin` conserva el superset completo del flujo.

## 3. Validación y cierre

- [x] 3.1 Confirmar que `RolesAndPermissionsSeederTest` sigue verde (no se agregaron permisos, solo roles); si existe un test que afirme el catálogo exacto de roles, extenderlo con los tres nuevos.
- [x] 3.2 `vendor/bin/pint --dirty --format agent` y `php artisan test` sobre la suite de seguridad + informes razonados.
