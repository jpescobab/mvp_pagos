## Context

`app/Services/Sgf/ImportadorSgf.php` y `NormalizadorSgf.php` ya saben transformar y guardar una fila SGF como snapshot inmutable, pero en tablas propias (`importaciones_sgf`, `snapshots_sgf`, `snapshots_sgf_documentos`) diseñadas un día antes de que existiera la capa transversal de integraciones. Esa capa transversal (`sistemas_externos`, `trabajos_integracion`, `solicitudes_api_externas`, `snapshots_datos_externos`, y la capa Playwright `conectores_automatizacion_navegador`/`perfiles_autenticacion_navegador`/`ejecuciones_automatizacion_navegador`/`pasos_automatizacion_navegador`) ya está implementada, archivada, y probada en producción por `App\Services\Adquisiciones\OrdenCompraMercadoPublicoService` vía `App\Services\Integraciones\IntegracionExternaService` y `AutomatizacionNavegadorService`.

`IntegracionesSeeder` ya siembra un `sistema_externo` con `codigo` `SGF` y `tipo_integracion` `manual` (placeholder, sin conector real detrás todavía) — este change lo reutiliza y actualiza, no crea uno nuevo.

SGF no expone ninguna API: el único camino es Playwright. No hay MFA en la cuenta de automatización, pero sí credenciales reales (usuario/clave) que no pueden vivir en la base de datos de Laravel ni en Git — solo una referencia (`perfiles_autenticacion_navegador.almacen_secreto` + `referencia_secreto`) a dónde vive el secreto real.

El motor de Playwright vive fuera de PHP: se decidió un microservicio Node separado, versionado en `services/sgf-playwright/` dentro de este mismo repo, pero fuera del ciclo de vida de la app Laravel (no se despliega ni se prueba como parte de `composer test`/`npm run build`). Este change define el contrato HTTP que ese microservicio debe cumplir; no construye su código interno.

## Goals / Non-Goals

**Goals:**
- Conectar `sistema_externo` `SGF` (mecanismo `playwright`) a un flujo real de dos operaciones: verificación puntual de un caso (síncrona) e importación masiva bajo demanda (Job en cola).
- Registrar toda corrida con la capa transversal: `trabajo_integracion`, `ejecucion_automatizacion_navegador` + `pasos_automatizacion_navegador`, y un `snapshot_datos_externo` por fila obtenida.
- Definir el contrato HTTP interno (autenticado) entre Laravel y `services/sgf-playwright/`.
- Migrar la evidencia de SGF de las tablas bespoke a la capa transversal, retirando el código y esquema que quedan huérfanos.
- Vincular varios documentos del expediente a un mismo `snapshot_datos_externo` (hoy solo soporta un `vinculable` único).

**Non-Goals:**
- No se construye el código interno del microservicio Node/Playwright (queda para una iteración aparte, contra el contrato definido aquí).
- No se agrega disparo programado/scheduler — solo bajo demanda del usuario con permiso.
- No se crea `TransicionWorkflowService` nuevo ni se modifica cómo un snapshot origina o actualiza un `caso_pago_proveedor` (fuera del alcance de este change; sigue las reglas ya vigentes de `pago-proveedores-sgf`).
- No se implementa reintento automático ni backoff ante fallos del microservicio — un fallo se registra como `trabajo_integracion` en estado `error` y queda para que el usuario reintente manualmente.

## Decisions

1. **El microservicio se comunica por HTTP síncrono; el "asincronismo" de la importación masiva vive en el lado Laravel (Job de cola), no en el microservicio.** El Job de importación masiva llama a `services/sgf-playwright/` con un timeout largo y espera la respuesta completa dentro del worker de cola — el worker no bloquea ninguna request de usuario, así que no hace falta que el microservicio implemente su propio patrón asíncrono (webhook de callback, cola propia, etc.). Alternativa descartada: que el microservicio dispare un webhook a Laravel al terminar — más piezas (URL de callback, autenticación inversa, idempotencia del webhook) sin necesidad real, porque el worker de cola ya tolera esperas largas.

2. **Contrato HTTP mínimo, dos endpoints:**
   - `POST /casos/verificar` — body `{ "sgf_id": string }` → `{ "encontrada": bool, "payload_crudo": object|null, "pasos": [{ "orden": int, "accion": string, "estado": string, "detalle": object|null }] }`. Usado por la verificación puntual (síncrona).
   - `POST /casos/importar-pendientes` — body `{}` (sin filtros por ahora) → `{ "filas": [{ "sgf_id": string, "payload_crudo": object }], "pasos": [...] }`. Usado por el Job de importación masiva.
   - Ambos endpoints exigen header `X-Api-Key` con el valor de `services.sgf_playwright.api_key`; responden `401` si falta o no coincide.
   - Ambos responden también un código de error explícito (`50x` con `{ "error": string }`) que Laravel registra como `trabajo_integracion` en estado `error`, replicando el manejo de excepciones que ya usa `OrdenCompraMercadoPublicoService::consultarApiInterno()`.

3. **Configuración vía `config/services.php` + `.env`, mismo patrón que `mercadopublico`:**
   ```php
   'sgf_playwright' => [
       'base_url' => env('SGF_PLAYWRIGHT_BASE_URL'),
       'api_key' => env('SGF_PLAYWRIGHT_API_KEY'),
   ],
   ```
   El microservicio, no Laravel, es responsable de resolver dónde vive el usuario/clave reales de SGF (fuera del alcance de este change).

4. **`ConectorSgfPlaywrightService` reemplaza a `ImportadorSgf`, construido igual que `OrdenCompraMercadoPublicoService`:** inyecta `IntegracionExternaService` y `AutomatizacionNavegadorService`; por cada corrida: `iniciarTrabajo(sistema: SGF, tipo: 'verificar_caso'|'importar_pendientes', mecanismo: 'playwright')` → `iniciarEjecucion()` sobre el `conector_automatizacion_navegador` de SGF (falla con `ConectorAutomatizacionNoAutorizadoException` si no está autorizado) → llama al microservicio vía `Http::` → `registrarPaso()` por cada paso que reporte la respuesta → `registrarSnapshot()` por cada fila con `metodo_captura: 'playwright'` y `referencia_externa: $sgfId` → `finalizarEjecucion()` + `finalizarTrabajo()`.

5. **`sistema_externo` `SGF` ya sembrado se actualiza, no se crea uno nuevo:** `tipo_integracion` pasa de `manual` a `playwright` como parte del seeder (o una migración de datos idempotente), preservando su `id` para no romper FKs existentes.

6. **Verificación puntual = síncrona, en el controlador; importación masiva = Job en cola (`ImportarCasosPendientesSgfJob`), estilo `ImportarIndicadoresMensualesJob`/`ImportarDolarDiarioJob`** (`ShouldQueue`, `WithoutOverlapping()` para exclusividad — no correr dos importaciones masivas en paralelo). El frontend consulta el estado de la importación vía polling Inertia sobre el `trabajo_integracion` creado (igual patrón que `ImportacionSgfController::show` ya usa para mostrar una corrida).

7. **Nueva tabla `snapshots_datos_externos_documentos`** (mismo shape que la actual `snapshots_sgf_documentos`, reapuntada a la tabla genérica):
   ```php
   $table->id();
   $table->foreignId('snapshot_datos_externo_id')->constrained('snapshots_datos_externos')->cascadeOnDelete();
   $table->foreignId('documento_id')->constrained('documentos')->restrictOnDelete();
   $table->timestamp('created_at')->useCurrent();
   ```
   Se prefiere esta tabla de unión explícita sobre extender `vinculable_type`/`vinculable_id` a una relación polimórfica many-to-many, porque `vinculable` ya se usa (y se sigue usando) para el enlace 1:1 opcional al caso/proceso interno (ej. una OC de Mercado Público); mezclar ambos usos en el mismo par de columnas polimórficas complicaría innecesariamente su significado.

8. **`CasoPagoProveedor::snapshotsSgf()` se reescribe** de `hasMany(SnapshotSgf::class, 'sgf_id', 'sgf_id')` a una relación (o accessor) que consulta `SnapshotDatosExterno::where('sistema_externo_id', SistemaExterno::sgf()->id)->where('referencia_externa', $this->sgf_id)->orderByDesc('id')` — replicando el patrón `referencia_externa` que ya usa Mercado Público, en vez de introducir un nuevo mecanismo de vínculo.

9. **Retiro de código bespoke sin migración de datos**, dado que no hay ninguna corrida real de `ImportadorSgf` en producción (confirmado: sin Job/comando/scheduler que lo haya invocado nunca fuera de tests) — se eliminan `app/Services/Sgf/{ImportadorSgf,NormalizadorSgf}.php`, los modelos `ImportacionSgf`/`SnapshotSgf`/`SnapshotSgfDocumento`, sus migraciones (con migraciones `down()` reales de reversión, no solo eliminación de archivo), y los tests que los ejercitan, reemplazados por tests equivalentes sobre `ConectorSgfPlaywrightService`.

## Risks / Trade-offs

- [Riesgo] El contrato HTTP definido aquí (`/casos/verificar`, `/casos/importar-pendientes`) es una suposición razonable sobre la forma de la respuesta de SGF, sin poder validarlo contra el microservicio real (que no existe todavía). → Mitigación: el payload de cada fila (`payload_crudo`) se guarda como JSON sin esquema fijo en `snapshots_datos_externos`, igual que hoy; si la forma real difiere al construir `services/sgf-playwright/`, solo cambia la normalización, no el esquema de tablas ni el contrato de alto nivel (encontrada/filas/pasos/error).
- [Riesgo] Cambiar `tipo_integracion` de `SGF` de `manual` a `playwright` en el seeder podría no aplicarse en entornos ya sembrados (el seeder normalmente no vuelve a correr sobre datos existentes). → Mitigación: usar `updateOrCreate` por `codigo` en el seeder (no `firstOrCreate`), para que sea idempotente y también actualice el registro ya sembrado.
- [Riesgo] Retirar `ImportadorSgf`/`SnapshotSgf` es un cambio **BREAKING** de esquema (elimina tablas). → Mitigación: confirmado que no hay datos reales de producción en esas tablas (solo fixtures de test); se documenta explícitamente en el proposal y se revisa antes de aplicar en cualquier entorno que sí tenga datos.
- [Riesgo] Un Job de importación masiva que falla a mitad de camino (ej. el microservicio se cae después de procesar 50 de 200 filas) podría dejar snapshots parciales sin que el usuario lo note. → Mitigación: el contrato exige que `/casos/importar-pendientes` devuelva su resultado completo en una sola respuesta (no streaming); si falla, no se guarda ningún snapshot parcial de esa corrida — el `trabajo_integracion` queda en `error` y el usuario puede reintentar la corrida completa.
- [Riesgo/Trade-off] No hay reintento automático ni límite de concurrencia más allá de `WithoutOverlapping()` a nivel de una sola importación masiva a la vez — si SGF cambia su HTML o el conector se rompe, cada intento fallará igual hasta que se actualice `services/sgf-playwright/`. Aceptado como alcance de esta primera iteración; no se sobre-diseña resiliencia para un conector que aún no se ha probado contra el sistema real.

## Migration Plan

1. Migraciones: crear `snapshots_datos_externos_documentos`; eliminar (con `down()` de reversión) `importaciones_sgf`, `snapshots_sgf`, `snapshots_sgf_documentos`.
2. Actualizar `IntegracionesSeeder` para que el `sistema_externo` `SGF` quede con `tipo_integracion: 'playwright'` (idempotente vía `updateOrCreate`), y sembrar su `conector_automatizacion_navegador` (inactivo/no autorizado por defecto — la autorización explícita queda como acción manual posterior de un usuario con `integraciones.gestionar_conectores`, no se auto-autoriza desde el seeder).
3. Agregar `config/services.php` → `sgf_playwright` + variables en `.env.example`.
4. Construir `ConectorSgfPlaywrightService`, `ImportarCasosPendientesSgfJob`, controladores/rutas para verificación puntual e importación masiva, permisos `pago_proveedores.verificar_caso_sgf` / `pago_proveedores.importar_casos_sgf`.
5. Reescribir `CasoPagoProveedor::snapshotsSgf()` y el listado/detalle de `consulta-importaciones-sgf` sobre la capa transversal.
6. Retirar `ImportadorSgf`/`NormalizadorSgf`/modelos bespoke y sus tests; agregar tests equivalentes sobre el nuevo servicio (fixtures simulando la respuesta del microservicio, sin depender de Playwright real en la suite de PHP).
7. Sin rollback de datos reales necesario (no hay datos reales que preservar); rollback de código es revertir el change completo vía las migraciones `down()`.

## Open Questions

- ¿Qué filtro exacto define "casos pendientes" en `/casos/importar-pendientes` (por estado/grupo de SGF, por fecha, por unidad)? Se define al construir `services/sgf-playwright/` contra el contrato aquí descrito; no bloquea este change porque el contrato ya deja `payload_crudo` abierto.
- ¿Cada cuánto expira/rota la sesión autenticada de SGF que mantiene el microservicio, y quién es responsable de renovarla manualmente? Queda fuera del alcance de este change (vive en `services/sgf-playwright/`), pero condiciona la operación diaria del conector.
