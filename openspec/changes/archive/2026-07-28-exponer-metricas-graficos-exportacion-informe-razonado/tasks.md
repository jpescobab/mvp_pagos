## 1. Lógica de negocio en el Service

- [x] 1.1 Agregar `editarMetrica(MetricaInformeRazonado $metrica, ...)` y `eliminarMetrica(MetricaInformeRazonado $metrica)` a `InformeRazonadoService`, siguiendo la forma de `editarSeccion`/`eliminarSeccion` (validar que la sección asignada, si se cambia, pertenezca a la misma ejecución).
- [x] 1.2 Agregar `editarGrafico(GraficoInformeRazonado $grafico, ...)` y `eliminarGrafico(GraficoInformeRazonado $grafico)` a `InformeRazonadoService`, análogos a los de métrica (incluir `tipo`, `datos`, `orden`, sección opcional validada).
- [x] 1.3 Crear `app/Services/InformesRazonados/ExportadorInformeRazonadoService` con `exportar(EjecucionInformeRazonado $ejecucion, string $formato): string` que, para `html`, arma el documento desde el contenido ensamblado (reutilizando `InformeRazonadoService::ensamblarContenido()` — exponerlo como público o mover el ensamblado a una vista Blade dedicada), lo escribe en el disco privado `informes-razonados/{ejecucion}/...` y devuelve la ruta relativa; para cualquier otro formato lanza una excepción de validación.
- [x] 1.4 Actualizar/crear tests unitarios en `InformeRazonadoServiceTest` para `editarMetrica`/`eliminarMetrica`/`editarGrafico`/`eliminarGrafico`, y un test para `ExportadorInformeRazonadoService` (genera archivo HTML no vacío; formato no soportado lanza excepción).

## 2. Autorización (policies y permiso)

- [x] 2.1 Crear `app/Policies/MetricaInformeRazonadoPolicy` calcada de `SeccionInformeRazonadoPolicy` (`create`/`update`/`delete` con `informes.elaborar` + `estaEnElaboracion()`).
- [x] 2.2 Crear `app/Policies/GraficoInformeRazonadoPolicy` idéntica para gráficos.
- [x] 2.3 Agregar la ability `exportar` a `EjecucionInformeRazonadoPolicy` autorizada con `informes.exportar` (sin exigir `estaEnElaboracion()`).
- [x] 2.4 Registrar las policies nuevas en `AppServiceProvider::configureAuthorization()` con `Gate::policy(...)` (no hay auto-discovery).
- [x] 2.5 Agregar el permiso `informes.exportar` en el seeder del dominio (`RolesAndPermissionsSeeder`, junto a los demás `informes.*`) y asignarlo a los roles que hoy tienen `informes.elaborar`/`informes.aprobar`; si `RolesAndPermissionsSeederTest` afirma la lista exacta de permisos, actualizarlo. Invalidar la caché de permisos tras sembrar.

## 3. Form Requests

- [x] 3.1 Crear `app/Http/Requests/InformesRazonados/` requests para métrica (store/update): `codigo`, `etiqueta` requeridos; `valor` numérico opcional; `unidad` opcional; `orden` entero; `seccion_informe_razonado_id` opcional y validado contra las secciones de la ejecución.
- [x] 3.2 Crear requests para gráfico (store/update): `codigo`, `titulo`, `tipo` (in `barra,linea,torta,area`) requeridos; `datos` array/JSON válido requerido; `orden` entero; sección opcional validada como en 3.1.
- [x] 3.3 Crear request para exportación (store): `formato` requerido, `in:html` (extensible), rechazando cualquier otro formato por validación.

## 4. Controladores y rutas

- [x] 4.1 Crear `MetricaInformeRazonadoController` (`store`/`update`/`destroy`) liviano: autoriza vía policy, delega en `InformeRazonadoService`, retorna redirect back con flash (mismo estilo que `SeccionInformeRazonadoController`).
- [x] 4.2 Crear `GraficoInformeRazonadoController` (`store`/`update`/`destroy`) análogo.
- [x] 4.3 Crear `ExportacionInformeRazonadoController` con `store` (autoriza `exportar` → `ExportadorInformeRazonadoService::exportar` → `InformeRazonadoService::exportar` para registrar) y `descargar(ExportacionInformeRazonado)` que autoriza y hace stream del archivo privado.
- [x] 4.4 Declarar las rutas anidadas en `routes/informes-razonados.php` bajo el grupo existente: `ejecuciones/{ejecucion}/metricas`, `metricas/{metrica}` (patch/delete), equivalentes para gráficos, `ejecuciones/{ejecucion}/exportaciones` (post) y `exportaciones/{exportacion}/descargar` (get), con nombres consistentes (`ejecuciones.metricas.store`, etc.).
- [x] 4.5 Regenerar Wayfinder (`php artisan wayfinder:generate --with-form`) para que el frontend tenga los helpers tipados.

## 5. Feature tests HTTP

- [x] 5.1 `ElaborarMetricasInformeRazonadoTest`: crear/editar/eliminar con permiso durante `en_elaboracion`; el `Proceso` no cambia de estado; asignar sección de otra ejecución falla; bloqueo fuera de `en_elaboracion`; rechazo sin `informes.elaborar`.
- [x] 5.2 `ElaborarGraficosInformeRazonadoTest`: los mismos casos que 5.1 más el rechazo de `tipo` inválido y `datos` malformado.
- [x] 5.3 `ExportarInformeRazonadoTest`: exportar `html` con `informes.exportar` genera archivo y registra la `ExportacionInformeRazonado` (formato/ruta/responsable) sin cambiar el `Proceso`; formato no soportado → 422; sin permiso → 403; descarga autorizada hace stream del archivo.

## 6. Frontend (ejecuciones/show.tsx)

- [x] 6.1 Verificar/ampliar `EjecucionInformeRazonadoResource` para que métricas y gráficos expongan los campos editables (`codigo`, `orden`, `seccion_informe_razonado_id`, y `tipo`/`datos` del gráfico) y cada exportación incluya su URL de descarga y metadatos (`formato`, `generado_en`, responsable).
- [x] 6.2 Agregar en `ejecuciones/show.tsx` los formularios de crear/editar/eliminar de métricas y de gráficos (mismo estilo y gating de editabilidad que el bloque de secciones ya presente), usando helpers de Wayfinder con import con nombre.
- [x] 6.3 Agregar la acción de exportar (selector de formato con solo `html` habilitado) y el listado de exportaciones registradas con enlace de descarga; ocultar los controles según `auth.permissions` (`informes.elaborar`, `informes.exportar`) y el flag de editabilidad de la ejecución.

## 7. Validación final

- [x] 7.1 Correr `php artisan test` (suite completa del dominio InformesRazonados) y dejar todo en verde.
- [x] 7.2 Correr `composer lint` (Pint), `npm run lint:check`, `npm run types:check` y `composer types:check` (PHPStan) — recordar que `tsc` no lo corre el CI, verificarlo local.
- [x] 7.3 Revisar que los controladores nuevos quedaron livianos (sin `whereHas`/transacciones/ramas de negocio): toda la lógica en los services nombrados. Actualizar `CLAUDE.md` solo si el usuario lo pide.
