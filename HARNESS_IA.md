# HARNESS IA — CAPJ App Pagos / Plataforma de Gestión Institucional

**Versión:** v9 optimizada  
**Uso:** archivo rector para Claude Code, Codex, agentes IA y equipo de desarrollo  
**Stack base:** Laravel 13 + PostgreSQL + React + Laravel Boost + OpenSpec  
**Estado:** en producción activa. El core (§16, pasos 1-10) y Pago de Proveedores, Adquisiciones e Informes Razonados están implementados; el resto de módulos funcionales (§4.2) siguen siendo plan, no código.

---

## 1. Propósito

Este harness define las reglas obligatorias para construir la plataforma institucional de gestión, trazabilidad y reportabilidad. Su objetivo es evitar improvisación técnica, duplicidad de modelos, pérdida de evidencia, confusión entre datos externos y lógica interna, y generación de código sin control institucional.

La IA debe actuar como desarrollador asistente disciplinado: debe implementar según specs, respetar arquitectura, no inventar flujos no aprobados y detenerse cuando una instrucción contradiga este harness.

---

## 2. Objetivo institucional del sistema

Construir una plataforma institucional que funcione como capa transversal de:

- gestión de procesos;
- workflow interno;
- trazabilidad;
- expediente documental;
- auditoría;
- reportabilidad;
- cortes de gestión;
- integración con sistemas institucionales existentes;
- generación de informes razonados.

El sistema **no reemplaza** sistemas oficiales como SGF, CGU, BancoEstado, SII, CMF, Mercado Público u otros. Los complementa como capa de control, coordinación, evidencia y seguimiento.

---

## 3. Stack tecnológico obligatorio

- Backend: **Laravel 13**.
- Base de datos: **PostgreSQL**.
- Frontend: **React**.
- Desarrollo asistido: **Laravel Boost + Claude Code/Codex**.
- Especificación viva: **OpenSpec**.
- Roles y permisos: **Spatie Laravel Permission**.
- Autenticación API: **Sanctum**, si corresponde.
- Jobs: **Laravel Queue**.
- Programación: **Laravel Scheduler**.
- Procesos externos: **Laravel Process**.
- Automatización navegador: **Playwright**, solo si no existe API suficiente y existe autorización.
- Documentos: expediente documental variable.
- Reportabilidad: cortes, snapshots, dashboards e informes razonados.

---

## 4. Principios obligatorios

1. **Core no desactivable.** Seguridad, usuarios, roles, permisos, estructura CAPJ, workflow, auditoría, documentos, parámetros, integraciones, indicadores económicos, cortes y trazabilidad forman parte del núcleo.
2. **Módulos funcionales activables.** Pago de Proveedores, Adquisiciones, Presupuesto, Mantenimiento, RR.HH., Consumo eléctrico, Servicios contratados e Informes razonados pueden activarse/desactivarse sin borrar datos ni evidencia. **Implementados:** Pago de Proveedores, Adquisiciones, Informes razonados. **Planeados (sin código todavía):** Presupuesto, Mantenimiento, RR.HH., Consumo eléctrico, Servicios contratados — no asumir modelos/tablas/specs existentes para estos; proponer vía OpenSpec antes de codificar.
3. **Workflow antes que CRUD.** Todo proceso relevante debe tener estado interno, transición, tarea, responsable, documento, notificación, auditoría e historial.
4. **SGF es origen, no gobierno interno.** Los estados y grupos SGF no gobiernan workflow, unidades, permisos ni responsables internos.
5. **Snapshot obligatorio.** Todo dato/documento recibido desde SGF o API externa relevante debe conservar payload, fuente, fecha, hash, método de captura, usuario/job y vínculo al caso.
6. **API primero.** Usar API oficial cuando exista. Playwright solo como respaldo autorizado.
7. **IA con revisión humana.** La IA puede extraer, sugerir, analizar o redactar, pero no aprueba pagos, informes, cierres ni decisiones sensibles.
8. **Reportabilidad desde cortes.** Los informes razonados se generan desde cortes y snapshots, no desde datos vivos cambiantes.
9. **Indicadores económicos trazables.** Todo indicador usado en cálculos o reportes debe tener fuente, fecha, vigencia, payload y hash.
10. **No romper trazabilidad.** Ninguna corrección debe borrar evidencia previa; se versiona, observa o anula según corresponda.

---

## 5. Jerarquía institucional CAPJ

La estructura institucional base es:

```txt
CAPJ -> Jurisdicciones -> Centros financieros -> Centros de costos
```

Esta jerarquía guía permisos, filtros, usuarios, funcionarios, reportes, dashboards, casos de pago, informes razonados y trazabilidad.

### Tablas base

- `instituciones`
- `jurisdicciones`
- `cfinancieros`
- `ccostos`

### Reglas

- `instituciones` representa a CAPJ como entidad superior.
- Cada `jurisdiccion` pertenece a una `institucion`.
- Cada `cfinanciero` pertenece a una `jurisdiccion`.
- Cada `ccosto` pertenece a un `cfinanciero`.
- Las tablas maestras usan `id` interno como PK.
- Los códigos institucionales se mantienen como `unique`.
- `jurisdicciones.codigo` debe permitir valor por defecto `14` para la jurisdicción inicial.

---

## 6. Tablas core institucionales iniciales

Estas tablas deben existir desde el inicio del proyecto:

### Institución y estructura

- `instituciones`
- `jurisdicciones`
- `cfinancieros`
- `ccostos`

### Maestros institucionales

- `proveedores`
- `funcionarios`
- `clientes_medidores`

### Clasificación presupuestaria / institucional

- `items`
- `catalogos`
- `asignaciones`

### Indicadores económicos

- `indicadores_economicos_importaciones`
- `indicadores_economicos`

### Seguridad y operación Laravel

- `users`
- `roles`
- `permissions`
- `model_has_roles`
- `model_has_permissions`
- `role_has_permissions`
- `sessions`
- `jobs`
- `job_batches`
- `failed_jobs`
- `cache`
- `cache_locks`
- `personal_access_tokens`, si se usa Sanctum

### Core funcional transversal

- `system_modules`
- `system_module_dependencies`
- `system_module_settings`
- `parameter_groups`
- `parameters`
- `parameter_values`
- `parameter_change_logs`
- `definiciones_workflow`
- `estados_workflow`
- `transiciones_workflow`
- `procesos`
- `tareas_workflow`
- `asignaciones_tareas_workflow`
- `historial_transiciones_workflow`
- `notifications`
- `notification_events`
- `audit_logs`
- `security_audit_logs`
- `tipos_documento`
- `documentos`
- `versiones_documento`
- `vinculos_documento`
- `validaciones_documento`
- `modalidades_adquisicion`
- `conjuntos_requisitos_documentales`
- `requisitos_documentales`
- `checklists_documentales_proceso`
- `checklist_documental_proceso_items`
- `sistemas_externos`
- `solicitudes_api_externas`
- `snapshots_datos_externos`
- `trabajos_integracion`
- `conectores_automatizacion_navegador`
- `perfiles_autenticacion_navegador`
- `ejecuciones_automatizacion_navegador`
- `pasos_automatizacion_navegador`
- `artefactos_automatizacion_navegador`
- `periodos_reportabilidad`
- `cortes_reportabilidad`
- `cortes_reportabilidad_items`
- `snapshots_corte_reportabilidad`
- `definiciones_informe_razonado`
- `ejecuciones_informe_razonado`
- `secciones_informe_razonado`
- `metricas_informe_razonado`
- `graficos_informe_razonado`
- `excepciones_informe_razonado`
- `narrativas_informe_razonado`
- `snapshots_informe_razonado`
- `aprobaciones_informe_razonado`
- `exportaciones_informe_razonado`

---

## 7. Indicadores económicos CMF/SII

La tabla `indicadores_economicos` debe guardar información obtenida desde APIs externas oficiales, principalmente CMF Chile y, cuando corresponda, SII u otra fuente oficial configurada.

### Indicadores mínimos

- `UF`
- `USD` / dólar observado
- `UTM`
- `UTA`
- `IPC`

### Reglas de importación

- El día **10 de cada mes** se ejecuta un proceso mensual para importar `UF`, `UTM`, `UTA` e `IPC`.
- Para `UF`, se deben guardar valores diarios del tramo mensual vigente, normalmente desde el día 10 del mes hasta el día 9 del mes siguiente.
- `UTM`, `UTA` e `IPC` se registran como indicadores mensuales.
- `USD` se consulta diariamente y se guarda en base de datos.
- El dólar puede no tener valor en fines de semana, feriados o días sin publicación; la regla de fallback debe ser parametrizada.
- Cada ejecución se registra en `indicadores_economicos_importaciones`.
- Cada valor se vincula a su importación mediante `importacion_id`.
- Se debe guardar `source_payload`, `endpoint`, `source_url`, `captured_at`, `source_hash`, `fuente`, errores, advertencias y metadatos.

### Regla de cálculo

- Para `USD`, buscar valor exacto por `fecha_valor`; si no existe, aplicar regla parametrizada.
- Para `UF`, buscar valor diario por `fecha_valor`.
- Para `UTM`, `UTA` e `IPC`, buscar por `periodo`.

---

## 8. SGF como fuente de origen

Del SGF se usará información relevante para gestión, reportabilidad, trazabilidad, conciliación y respaldo. No se copiará la lógica interna del SGF.

### Regla central

```txt
SGF entrega evidencia de origen; nuestro sistema gobierna la tramitación interna.
```

### Datos SGF como referencia externa

- `sgf_id`
- `sgf_status`
- `sgf_current_group_raw`
- `sgf_sender_group_raw`
- `sgf_creation_group_raw`
- `raw_sgf_payload`

### Datos internos que gobiernan el proceso

- `estado_workflow_id`
- `tareas_workflow`
- unidades internas
- roles y permisos
- responsables internos
- transiciones internas
- expediente documental interno

---

## 9. Snapshot SGF obligatorio

Todo caso de pago proveniente de SGF debe conservar snapshot de datos y documentos recibidos.

### Tablas

- `sgf_payment_case_imports`
- `sgf_payment_case_snapshots`
- `sgf_payment_case_snapshot_documents`

### El snapshot debe conservar

- `sgf_id`
- payload original
- datos normalizados
- documentos recibidos
- metadatos de documentos
- hash de archivos
- fecha/hora de captura
- fuente y método: API, Playwright, manual, CSV, Excel
- usuario o job responsable
- advertencias y errores

El snapshot no gobierna el workflow interno. Sirve como evidencia, auditoría, conciliación y reportabilidad.

---

## 10. Pago de Proveedores

El primer módulo funcional será Pago de Proveedores.

### Regla definitiva

```txt
Un sgf_id = un caso_pago_proveedor = un proceso workflow individual
```

No crear:

- `payment_submissions`
- `payment_submission_items`
- `sgf_submission_id`

### Tablas del módulo

- `casos_pago_proveedor`
- `invoices`
- `sgf_payment_case_imports`
- `sgf_payment_case_snapshots`
- `sgf_payment_case_snapshot_documents`
- `cgu_accounting_records`
- `bank_payment_records`
- `cgu_egresses`
- `cgu_egress_items`

### Estados internos sugeridos

- `importada_desde_sgf`
- `recibida_finanzas`
- `en_revision_documental`
- `observada`
- `subsanada`
- `lista_para_registro_cgu`
- `registrada_en_cgu`
- `lista_para_pago`
- `pagada_bancoestado`
- `asociada_a_egreso_cgu`
- `cerrada`
- `rechazada`
- `anulada`

---

## 11. Workflow Core

Todo cambio de estado debe pasar por `TransicionWorkflowService::execute()`.

El servicio debe validar:

- módulo activo;
- proceso existente;
- estado actual;
- transición permitida;
- permiso del usuario;
- unidad/responsable;
- documentos obligatorios;
- comentario o adjunto requerido;
- cierre/creación de tareas;
- auditoría;
- notificación;
- historial.

Prohibido cambiar estados directamente desde controladores, jobs, seeders o componentes React.

---

## 12. Expediente documental variable

Los documentos requeridos dependen de:

- módulo;
- tipo de proceso;
- modalidad de compra;
- tipo de servicio;
- tipo de documento tributario;
- monto;
- criticidad;
- estado;
- transición;
- condiciones especiales.

React solo renderiza el checklist que entrega el backend. No debe hardcodear requisitos documentales.

---

## 13. Integraciones y Playwright

### API primero

Toda integración debe pasar por capa transversal:

- `sistemas_externos`
- `solicitudes_api_externas`
- `snapshots_datos_externos`
- `trabajos_integracion`

### Playwright

Permitido solo para sistemas autorizados sin API suficiente, registrado en:

- `conectores_automatizacion_navegador`
- `perfiles_autenticacion_navegador`
- `ejecuciones_automatizacion_navegador`
- `pasos_automatizacion_navegador`
- `artefactos_automatizacion_navegador`

Prohibido:

- evadir MFA;
- evadir CAPTCHA;
- saltar controles de acceso;
- ejecutar pagos automáticos sin confirmación humana;
- guardar credenciales/cookies en Git;
- dejar viva una sesión autenticada de automatización entre ejecuciones. *(Verificado 2026-07-08 en el conector de SGF: se cierra el navegador/contexto de Playwright al terminar cada `ejecucion_automatizacion_navegador`, éxito o error — ver requirement equivalente en spec `integraciones-api-browser-automation`.)*

Toda ejecución Playwright debe guardar run, pasos, artifacts, screenshots o trazas cuando corresponda.

Un Job de importación masiva vía Playwright puede tardar mucho más que el timeout por defecto de un worker de cola. En Windows, `php artisan queue:listen` ejecuta cada job en un proceso hijo con su **propio** timeout (`Symfony\Process`, 60s por defecto) independiente del `$timeout` del Job — si vence, mata el proceso hijo y además crashea `queue:listen` completo, sin dejar ningún registro de error. Cualquier Job de integración de larga duración debe: (1) declarar su propio `$timeout` (documentación, no aplica bajo `queue:listen` en Windows), (2) que quien lo corra pase `--timeout` explícito a `queue:listen` acorde a la duración esperada, y (3) que su `WithoutOverlapping` (si aplica) tenga `expireAfter()` explícito — sin él, el lock por defecto queda tomado ~24h si el proceso muere de forma abrupta, bloqueando reintentos. Ver `services/sgf-playwright/CALIBRACION.md` para el caso real que expuso esto.

**Detección de trabajos huérfanos (implementado, change archivado `2026-07-09-deteccion-trabajos-integracion-huerfanos`)**: pese a las mitigaciones anteriores, un `trabajo_integracion` en `en_progreso` siempre puede quedar huérfano por una causa no anticipada (corte de energía, `kill -9` externo, etc.). La capa transversal detecta esto automáticamente — vía barrido programado (`trabajos-integracion:expirar-huerfanos`, cada 5 min) y chequeo perezoso en la guarda de "ya hay uno en curso" — y lo marca con el estado `huerfano` (distinto de `error`), liberando reintentos sin intervención manual en la base de datos. Umbral configurable por tipo en `config/integraciones.php`. Cualquier conector nuevo con Jobs de larga duración reutiliza esto automáticamente (`IntegracionExternaService::expirarSiEsHuerfano()`), no hace falta reimplementarlo.

---

## 14. Reportabilidad e informes razonados

El sistema debe soportar cortes de reportabilidad e informes razonados de gestión.

Flujo:

```txt
corte mensual publicado
-> snapshot de datos
-> métricas
-> excepciones
-> gráficos
-> texto razonado en borrador
-> revisión humana
-> aprobación
-> publicación
-> exportación Word/PDF/Excel/HTML
```

Los informes no son cierres contables ni presupuestarios oficiales. Son evidencia de gestión, seguimiento, cumplimiento, excepciones y toma de decisiones.

Tablas:

- `periodos_reportabilidad`
- `cortes_reportabilidad`
- `cortes_reportabilidad_items`
- `snapshots_corte_reportabilidad`
- `definiciones_informe_razonado`
- `ejecuciones_informe_razonado`
- `secciones_informe_razonado`
- `metricas_informe_razonado`
- `graficos_informe_razonado`
- `excepciones_informe_razonado`
- `narrativas_informe_razonado`
- `snapshots_informe_razonado`
- `aprobaciones_informe_razonado`
- `exportaciones_informe_razonado`

---

## 15. Reglas de implementación Laravel

- Controladores livianos.
- Form Requests para validación.
- Services para lógica de negocio.
- Policies/Gates/Middleware para autorización.
- Resources/Collections para API hacia React.
- Jobs para procesos pesados.
- Events/Listeners para efectos secundarios.
- Transacciones en operaciones críticas.
- Tests para workflow, permisos, snapshots e importaciones.
- Migraciones limpias, con índices, foreign keys y nombres consistentes.

Prohibido:

- lógica pesada en controladores;
- querys complejas en React;
- consultar APIs externas desde React;
- saltarse services centrales;
- borrar snapshots o auditoría;
- mezclar estados SGF con estados internos.

### Listados/índices en React

Todo listado o índice tabular nuevo (catálogos de consulta, tablas maestras u otro) SHALL seguir el patrón de tabla densa especificado en `openspec/specs/tema-visual-layout/spec.md` (requirement "Listados tabulares densos"): columnas de ancho fijo, identidad visual por fila (avatar con iniciales + nombre + código en `font-mono`), badge de estado con tokens semánticos, columnas secundarias truncadas con tooltip (incluida una columna que muestre el nombre de una entidad relacionada, ej. la jurisdicción o el centro financiero padre) y ocultas progresivamente en viewports angostos, acciones agrupadas en un menú desplegable con las no implementadas deshabilitadas ("Disponible próximamente"), búsqueda con debounce de 300ms preservando estado/scroll, y paginación simple con contador "Mostrando X–Y de Z". La tipografía y los botones del encabezado del listado usan los tokens de tema (escala reducida, botones sin relleno sólido) sin necesidad de clases adicionales. Implementación de referencia: `resources/js/pages/maestros/cfinancieros/index.tsx` (patrón base con columna de entidad relacionada) y `resources/js/pages/maestros/ccostos/index.tsx` (además, columna secundaria opcional con `"—"` cuando el valor es `null`). `resources/js/pages/maestros/proveedores/index.tsx` sigue siendo válido como ejemplo sin relación jerárquica.

---

## 16. Orden de implementación recomendado

Pasos 1-13 (core, `tasks/01..10` + extensiones directas) están **completos e implementados** — ver `openspec/changes/archive/`. Se mantienen aquí como referencia histórica y como checklist para auditar que ningún módulo nuevo se salte una capa (p. ej. no crear un módulo de pago sin workflow, sin documentos ni sin auditoría).

1. Estructura CAPJ y tablas maestras institucionales.
2. Seguridad, usuarios, roles y permisos. *(Extendido en 2026-07 con gestión granular de usuarios institucionales — listar, activar, desactivar, resetear contraseña — ver spec `listar-usuarios-institucionales`.)*
3. Módulos del sistema y parámetros institucionales.
4. Indicadores económicos CMF/SII.
5. Workflow Core.
6. Auditoría y notificaciones.
7. Documentos y matriz documental.
8. Integraciones externas y snapshots.
9. SGF origen/snapshot.
10. Pago de Proveedores por `sgf_id`.
11. CGU, BancoEstado y egreso CGU como referencias/evidencia.
12. Reportabilidad, cortes e informes razonados.
13. Playwright solo donde corresponda. *(Ya implementado: `conectores_automatizacion_navegador` y ejecución de importaciones SGF.)*

Trabajo posterior a estos 13 pasos (Adquisiciones como módulo funcional completo, catálogos de consulta, checklist documental por módulo, auditoría visual, etc.) se propone y documenta ad-hoc vía `/opsx:propose` — no tiene numeración fija; su registro vive en `openspec/specs/` y `openspec/changes/archive/`, no en esta lista.

---

## 17. Frases rectoras

- SGF entrega evidencia de origen; nuestro sistema gobierna la tramitación interna.
- CAPJ estructura la institución; el workflow estructura la gestión.
- Los datos externos se conservan como snapshot; las decisiones internas se gobiernan por reglas propias.
- Todo informe razonado debe nacer de un corte y terminar con revisión humana.
- Todo dato usado en cálculo o reporte debe ser trazable.
