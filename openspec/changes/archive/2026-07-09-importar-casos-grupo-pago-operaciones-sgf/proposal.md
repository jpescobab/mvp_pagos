## Why

La importación masiva de SGF trae todos los procesos pendientes de la Bandeja, sin distinguir si ya tienen financiamiento confirmado o siguen en trámite en otras unidades. El grupo SGF "Pago operaciones" agrupa específicamente los pagos que ya tienen financiamiento y están listos para revisión y pago inmediato — quien gestiona pagos necesita poder traer solo esos casos, sin esperar ni filtrar manualmente entre el resto de la Bandeja, y sin tener que correr (o esperar a que termine) la importación masiva completa cada vez.

## What Changes

- Nueva importación selectiva que trae únicamente los procesos de la Bandeja SGF cuyo `grupo_actual` sea "Pago Operaciones", usando el propio filtro nativo del formulario "Buscar" de la Bandeja (multiselect "Grupo" + rango de fechas desde un mes atrás hasta hoy), reutilizando el resto de la navegación y los selectores ya calibrados de la Bandeja.
- Nuevo Job independiente (`ImportarCasosGrupoPagoOperacionesSgfJob`) con su propio lock `WithoutOverlapping`, para que la importación selectiva y la masiva puedan correr en paralelo sin bloquearse entre sí.
- Nuevo `tipo` de `trabajo_integracion` (`importar_grupo_pago_operaciones`), siguiendo el mismo patrón que `importar_pendientes`, sin cambios de esquema.
- Nuevo botón "Importar grupo Pago operaciones" en `/sgf/importaciones`, junto al botón de importación masiva existente.
- Ampliación del stub del microservicio Playwright (`services/sgf-playwright/server.js`) con casos de prueba de distintos grupos, para poder probar el flujo completo en desarrollo sin tocar SGF real.

## Capabilities

### New Capabilities

(ninguna — esta funcionalidad amplía la capability existente de importación vía el conector Playwright de SGF, no introduce un dominio nuevo)

### Modified Capabilities

- `conector-sgf-playwright`: se agrega un nuevo requirement de importación selectiva por grupo "Pago operaciones", análogo en estructura al requirement existente de importación masiva (permiso, Job en cola, lock independiente, snapshot por fila, manejo de huérfanos y errores), pero filtrando a un único grupo SGF.
- `consulta-importaciones-sgf`: el requirement de listado de `trabajos_integracion` de SGF actualiza su descripción de `tipo` para reflejar que ahora existen tres variantes (verificación puntual, importación masiva, importación selectiva por grupo "Pago operaciones") en vez de dos.

## Impact

- **Backend**: nuevo controlador o método adicional en el área de `app/Http/Controllers/Sgf/`, nuevo Job en `app/Jobs/`, nuevo método en `app/Services/Sgf/ConectorSgfPlaywrightService.php`, nueva ruta en `routes/sgf.php`. Posible ajuste de permisos (`WorkflowPagoProveedoresSeeder`, `CasoPagoProveedorPolicy`) — a decidir en design.md si reutiliza `pago_proveedores.importar_casos_sgf` o usa uno dedicado.
- **Microservicio Playwright**: nuevo endpoint en `services/sgf-playwright/server.js`, nueva función en `services/sgf-playwright/sgf-scraper.js` que usa el filtro nativo "Grupo" + rango de fechas de la Bandeja antes de leer la tabla, con una verificación defensiva de `grupo_actual` como red de seguridad, y nuevos casos de prueba en el stub.
- **Frontend**: `resources/js/pages/sgf/importaciones/index.tsx` (nuevo botón), tipos Wayfinder regenerados.
- **Configuración**: posible entrada nueva en `config/integraciones.php`/`.env` para el umbral de huérfano del nuevo `tipo` (si no se agrega, cae en el umbral `default` de 120 min).
- **Specs**: `openspec/specs/conector-sgf-playwright/spec.md`, `openspec/specs/consulta-importaciones-sgf/spec.md`.
- **Tests**: nuevo test de controlador análogo a `tests/Feature/Sgf/ImportarCasosPendientesSgfTest.php`, y cobertura nueva en `tests/Feature/Sgf/ConectorSgfPlaywrightServiceTest.php` verificando que solo se persisten los casos del grupo "Pago operaciones".
