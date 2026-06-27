## Why

Las tareas 1-8 construyeron dominio institucional, workflow, expediente documental y el primer módulo funcional (pago de proveedores), pero cada conector con un sistema externo (hoy solo SGF, vía `ImportacionSgf`/`SnapshotSgf` en la tarea 7) inventó su propio mecanismo de registro porque la capa transversal que exige el harness (sección 13: `sistemas_externos`, `solicitudes_api_externas`, `snapshots_datos_externos`, `trabajos_integracion`) todavía no existía. Sin esa capa, cada futuro conector (CGU, BancoEstado, SII, CMF, Mercado Público) repetiría el mismo problema, y no hay ningún lugar autorizado para registrar automatizaciones Playwright cuando una API no sea suficiente.

## What Changes

- Crear `sistemas_externos`: catálogo de sistemas externos con los que la plataforma puede integrarse (código, nombre, mecanismo de integración vigente, estado activo).
- Crear `trabajos_integracion`: cada corrida de integración (importación, consulta, sincronización) contra un sistema externo, con su mecanismo (`api`/`playwright`), estado, quién/qué la inició y resultado.
- Crear `solicitudes_api_externas`: cada llamada HTTP individual a un sistema externo, opcionalmente asociada a un `trabajo_integracion`, con endpoint, payload enviado/recibido, código de respuesta y errores.
- Crear `snapshots_datos_externos`: evidencia inmutable de datos externos (payload crudo, normalizado, hash, método de captura), vinculable de forma polimórfica a cualquier caso interno — el equivalente genérico de `snapshots_sgf` (tarea 7) para cualquier sistema externo.
- Crear `conectores_automatizacion_navegador`: catálogo de automatizaciones Playwright autorizadas, cada una ligada a un sistema externo y con evidencia explícita de autorización (quién y cuándo).
- Crear `perfiles_autenticacion_navegador`: referencia a dónde vive la credencial usada por un conector Playwright (almacén + clave de referencia) — nunca la credencial ni cookies en sí.
- Crear `ejecuciones_automatizacion_navegador`, `pasos_automatizacion_navegador`, `artefactos_automatizacion_navegador`: ejecución, pasos y evidencia (capturas/trazas) de cada corrida Playwright autorizada.
- Crear `App\Services\Integraciones\IntegracionExternaService`: punto único para iniciar/cerrar un `trabajo_integracion`, registrar `solicitudes_api_externas` y persistir `snapshots_datos_externos`.
- Crear `App\Services\Integraciones\AutomatizacionNavegadorService`: valida que el conector esté activo y autorizado antes de iniciar una `ejecucion_automatizacion_navegador`, y registra sus pasos/artifacts.
- Sembrar el catálogo `sistemas_externos` con los sistemas oficiales ya nombrados en el harness (SGF, CGU, BancoEstado, SII, CMF, Mercado Público) como referencia, sin credenciales ni automatización real todavía.
- Agregar permisos `integraciones.gestionar_conectores` e `integraciones.ejecutar_playwright`.

**Fuera de alcance (decisión explícita):** no se modifica `ImportacionSgf`/`SnapshotSgf` (tarea 7) para que use esta capa. SGF sigue funcionando como hoy; conectar SGF a la capa transversal queda como un change futuro independiente, no como parte de esta tarea.

## Capabilities

### New Capabilities
- `integraciones-api-browser-automation`: capa transversal para registrar toda integración con sistemas externos (API primero) y, solo como respaldo autorizado y trazado, automatizaciones Playwright. No gobierna workflow ni reemplaza la lógica de los sistemas oficiales; es evidencia y trazabilidad de integración.

### Modified Capabilities
(ninguna — no cambia comportamiento de `sgf-origen-snapshot`, `workflow-core`, `documentos-expediente-variable` ni `pago-proveedores-sgf`; es infraestructura nueva e independiente.)

## Impact

- Migraciones nuevas: `sistemas_externos`, `trabajos_integracion`, `solicitudes_api_externas`, `snapshots_datos_externos`, `conectores_automatizacion_navegador`, `perfiles_autenticacion_navegador`, `ejecuciones_automatizacion_navegador`, `pasos_automatizacion_navegador`, `artefactos_automatizacion_navegador`.
- Código nuevo: modelos `SistemaExterno`, `TrabajoIntegracion`, `SolicitudApiExterna`, `SnapshotDatosExterno`, `ConectorAutomatizacionNavegador`, `PerfilAutenticacionNavegador`, `EjecucionAutomatizacionNavegador`, `PasoAutomatizacionNavegador`, `ArtefactoAutomatizacionNavegador`; servicios `IntegracionExternaService`, `AutomatizacionNavegadorService`; excepción `ConectorAutomatizacionNoAutorizadoException`.
- Nuevo seeder: `IntegracionesSeeder` (catálogo `sistemas_externos` + permisos).
- Documentos del harness actualizados (`CLAUDE.md`, `HARNESS_IA.md`, `openspec/config.yaml`) para reflejar los nombres en español de esta capa.
- No se construye UI ni endpoints HTTP en esta tarea (igual que tareas 5-8); es la capa de dominio/servicio que los módulos funcionales (pago de proveedores y los que vengan) consumirán para integrarse con sistemas externos reales.
