## Why

`integraciones-api-browser-automation` (tarea 09) ya exige que todo `conector_automatizacion_navegador` esté asociado a un `sistema_externo`, activo y con autorización explícita (usuario y fecha) antes de permitir cualquier ejecución Playwright sobre él (`ConectorAutomatizacionNavegador::estaAutorizado()`), y el permiso `integraciones.gestionar_conectores` ya está sembrado por `IntegracionesSeeder`. Sin embargo, ningún controlador permite registrar un conector ni autorizarlo: hoy es imposible cumplir ese requisito sin escribir código a mano. `AutomatizacionNavegadorService` (iniciar/registrar pasos/artefactos/finalizar una ejecución) y `IntegracionExternaService` (trabajos de integración, solicitudes API, snapshots) existen y están testeados, pero son APIs internas pensadas para que una corrida real de integración los invoque — no tienen ninguna acción de usuario que built-earlier no haya cubierto ya con el catálogo de `consultar-catalogo-sistemas-externos`.

## What Changes

- Exponer la gestión de conectores de automatización Playwright: listar, crear un conector (asociado a un sistema externo) y autorizarlo explícitamente, todo gated por el permiso `integraciones.gestionar_conectores`.
- Exponer el registro de perfiles de autenticación de un conector (nombre, almacén de secretos y referencia a la clave) — nunca el secreto real, conforme al requisito ya documentado en la spec de la tarea 09.
- **Fuera de alcance explícito**: no se construye ningún disparador real de ejecución Playwright ni UI para `trabajos_integracion`/`solicitudes_api_externas`/`snapshots_datos_externos`/`ejecuciones_automatizacion_navegador` — esos modelos son evidencia/auditoría que se genera como efecto de una corrida real de integración o automatización, que no existe todavía en este sistema (ningún Job ni comando ejecuta Playwright hoy). Construir esa UI ahora solo mostraría listas permanentemente vacías. Iniciar automatización real contra sistemas externos requiere autorización explícita separada (HARNESS_IA), no implícita en esta tarea de exposición de UI.

## Capabilities

### New Capabilities
- `gestionar-conectores-automatizacion-navegador`: registrar, autorizar y consultar conectores de automatización Playwright y sus perfiles de autenticación.

## Impact

- Nuevos: `App\Policies\ConectorAutomatizacionNavegadorPolicy`, `App\Http\Controllers\Integraciones\{ConectorAutomatizacionNavegadorController,PerfilAutenticacionNavegadorController}`, sus Form Requests y Resources, página `resources/js/pages/integraciones/conectores/index.tsx`.
- Modificados: `app/Providers/AppServiceProvider.php` (registrar la policy), `routes/integraciones.php`, `resources/js/components/app-sidebar.tsx`.
- Sin cambios de esquema ni de permisos — todo ya existe desde la tarea 09.
