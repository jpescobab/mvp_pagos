## 1. Microservicio Playwright

- [x] 1.1 Agregar selectores `FILTRO_BANDEJA` (TODO-VERIFICAR) en `services/sgf-playwright/selectors.js` para el multiselect `GRUPO`, los inputs `FECHA INICIAL`/`FECHA FINAL` y el botón "Buscar" (ya existe como `BANDEJA_PROCESOS.botonBuscar`) del formulario de la Bandeja
- [x] 1.2 Crear `importarGrupoPagoOperaciones()` en `services/sgf-playwright/sgf-scraper.js`, reutilizando `asegurarSesionIniciada` y `navegarABandeja`; antes de leer la tabla, seleccionar "Pago Operaciones" en el multiselect `GRUPO`, fijar `FECHA INICIAL` en la fecha actual menos un mes (mismo día del mes) dejando `FECHA FINAL` en su valor por defecto (hoy), y hacer clic en "Buscar"; recién entonces reutilizar `leerEncabezadosTabla` y `avanzarSiguientePagina` tal cual existen hoy, llamando `extraerDatosFila()` y `descargarDocumentosDeFila()` para cada fila resultante, con una verificación defensiva de `grupo_actual` (trim + comparación insensible a mayúsculas) antes de descargar documentos como red de seguridad ante un desajuste del filtro nativo
- [x] 1.3 Agregar endpoint `POST /casos/importar-grupo-pago-operaciones` en `services/sgf-playwright/server.js`, con handler dual: modo real llama `scraper.importarGrupoPagoOperaciones()`, modo stub devuelve una respuesta fija análoga a `manejarImportarPendientesStub()`
- [x] 1.4 Ampliar el array `CASOS` del stub (`server.js`) con al menos un caso cuyo `grupo_actual = 'Pago Operaciones'` y mantener los existentes de otros grupos, para poder probar en desarrollo que la respuesta filtrada del stub solo trae lo esperado
- [x] 1.5 Verificar manualmente contra la Bandeja SGF real (sesión supervisada, no automatizable en este entorno) el selector exacto del multiselect `GRUPO`, de los inputs de fecha y que el texto de la opción coincide con "Pago Operaciones"; ajustar `selectors.js` si difiere — VERIFICADO 2026-07-09: corrida real supervisada (`SGF_MODO=real`) trajo los 6 casos reales esperados del grupo "Pago Operaciones" (sgf_id 676, 677, 690, 713, 715, 745) con documentos descargados; selectores confirmados sin ajustes

## 2. Backend — Job y Service

- [x] 2.1 Crear `app/Jobs/ImportarCasosGrupoPagoOperacionesSgfJob.php` (mismo patrón que `ImportarCasosPendientesSgfJob`: `$timeout = 3600`, middleware `WithoutOverlapping('sgf-importar-grupo-pago-operaciones')->expireAfter(3700)`)
- [x] 2.2 Agregar método `importarGrupoPagoOperaciones(TrabajoIntegracion $trabajo)` en `app/Services/Sgf/ConectorSgfPlaywrightService.php`, análogo a `importarPendientes()` pero llamando a `casos/importar-grupo-pago-operaciones` en el microservicio
- [x] 2.3 Agregar entrada `'importar_grupo_pago_operaciones' => env('INTEGRACIONES_UMBRAL_HUERFANO_IMPORTAR_GRUPO_PAGO_OPERACIONES_MINUTOS', 90)` en `config/integraciones.php`, y la variable correspondiente en `.env.example`

## 3. Backend — Controlador y ruta

- [x] 3.1 Crear `app/Http/Controllers/Sgf/ImportarCasosGrupoPagoOperacionesSgfController.php::store()`, mismo patrón que `ImportarCasosPendientesSgfController::store()` (autoriza con `Gate::authorize('importarCasosSgf', CasoPagoProveedor::class)`, verifica conector autorizado, busca `TrabajoIntegracion` con `tipo = 'importar_grupo_pago_operaciones'` en curso, expira huérfanos perezosamente, bloquea o crea+despacha)
- [x] 3.2 Agregar ruta `POST sgf/casos/importar-grupo-pago-operaciones` en `routes/sgf.php`, con su propio `name()`
- [x] 3.3 Regenerar helpers Wayfinder (`php artisan wayfinder:generate --with-form`)

## 4. Frontend

- [x] 4.1 Agregar botón "Importar grupo Pago operaciones" en `resources/js/pages/sgf/importaciones/index.tsx`, junto al botón de importación masiva existente, usando el helper Wayfinder generado
- [x] 4.2 Verificar en el navegador que ambos botones conviven y que el listado de trabajos muestra correctamente el nuevo `tipo`

## 5. Specs y validación

- [x] 5.1 Correr `composer test` (Pint + PHPStan + Pest)
- [x] 5.2 Correr `npm run lint:check` y `npm run types:check`
- [x] 5.3 Escribir test de controlador análogo a `tests/Feature/Sgf/ImportarCasosPendientesSgfTest.php` (permiso correcto/denegado, guard de "ya hay uno en curso" scoped al nuevo tipo, huérfano no bloquea, conector no autorizado no crea nada)
- [x] 5.4 Ampliar `tests/Feature/Sgf/ConectorSgfPlaywrightServiceTest.php` con un test para `importarGrupoPagoOperaciones()` que mockea (`Http::fake()`) una respuesta con filas de "Pago operaciones" y de otro grupo, verificando que solo se crean/actualizan `CasoPagoProveedor` para las filas del grupo correcto
- [x] 5.5 Verificar el flujo completo en el navegador con el stub local (modo seguro) antes de considerar la tarea 1.5 (verificación real) como el único paso pendiente de supervisión humana
- [x] 5.6 `/opsx:archive` para fusionar las specs delta en `openspec/specs/conector-sgf-playwright/spec.md` y `openspec/specs/consulta-importaciones-sgf/spec.md`
