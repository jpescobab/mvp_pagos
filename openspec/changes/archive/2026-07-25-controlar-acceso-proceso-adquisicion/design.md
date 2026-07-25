## Context

`ProcesoAdquisicionPolicy` devuelve `true` en `viewAny`/`view`/`create`, así que el proceso de adquisición interno no tiene control de acceso. El controlador ya invoca `Gate::authorize('viewAny'|'view'|'create', ...)` en `index`/`show`/`create`/`store`, y la policy ya está registrada en `AppServiceProvider` (línea 125). El módulo ya tiene permisos de transición (`adquisiciones.publicar`/`adjudicar`/`anular`) y de Mercado Público (`adquisiciones.consultar_orden_compra_mp`/`consultar_licitacion_mp`), más un rol operativo `administrativo_adquisiciones`. El cambio es puramente de autorización: definir los permisos que faltan y hacer que la policy los verifique.

## Goals / Non-Goals

**Goals:**
- Que listar/ver/crear procesos de adquisición exija un permiso explícito, con 403 para quien no lo tenga.
- Mantener la convención `modulo_accion.verbo` y el reparto de permisos por seeder de módulo ya establecido.
- Que la UI (sidebar) refleje el permiso, igual que el resto de ítems gated.

**Non-Goals:**
- Editar procesos de adquisición (gap #2) ni visibilidad bidireccional con Mercado Público (gap #3).
- Cambiar el workflow, las transiciones o sus permisos existentes.
- Introducir filtros/segmentación por jerarquía institucional (fuera de alcance de este change).

## Decisions

- **Nombres de permiso: `adquisiciones.consultar_proceso` y `adquisiciones.crear_proceso`.** Se elige `consultar_*` por consistencia con los permisos MP del mismo módulo (`consultar_orden_compra_mp`), y el sufijo `_proceso` para distinguir el proceso interno de las entidades MP. Alternativa descartada: `adquisiciones.ver`/`adquisiciones.crear` a secas — menos preciso y colisiona conceptualmente con "ver" genérico.
- **Dos permisos, no uno.** Separar consulta de creación permite un rol de solo lectura (consulta sin crear), coherente con cómo otros módulos separan ver/gestionar. Alternativa descartada: un único `adquisiciones.gestionar_proceso` — pierde el nivel de solo lectura.
- **Siembra en `WorkflowAdquisicionesSeeder`, asignación a `admin` y `administrativo_adquisiciones`.** Es el seeder de módulo donde ya viven los permisos de adquisiciones; `administrativo_adquisiciones` es el rol operativo natural (ya recibe los permisos MP en `IntegracionesSeeder`). `firstOrCreate` + `givePermissionTo` mantiene idempotencia/aditividad.
- **Policy verifica con `$user->can(...)`.** `viewAny`/`view` → `adquisiciones.consultar_proceso`; `create` → `adquisiciones.crear_proceso`. `superadmin` no se toca (acceso vía `Gate::before`).
- **Sidebar: el ítem "Procesos" se gatea con `adquisiciones.consultar_proceso`** usando el mismo patrón `NavItemConPermiso`/`filtrarPorPermiso` ya presente en `app-sidebar.tsx`.
- **Invalidación de caché de permisos.** Tras resembrar, invalidar la caché por usuario/rol (`PermisosCompartidosResolver`) para que la UI refleje el permiso sin esperar el TTL de 5 min; en entorno de dev, resembrar + invalidar es el paso operativo esperado.

## Risks / Trade-offs

- **[Usuarios existentes pierden acceso al proceso tras el deploy]** → Es el comportamiento correcto (antes el acceso era universal por error). Mitigación: asignar los permisos a `admin` y `administrativo_adquisiciones` en el seeder; documentar en tasks que hay que resembrar + invalidar caché.
- **[Tests existentes que crean/consultan procesos empiezan a fallar con 403]** → Mitigación: actualizar esos tests para otorgar el permiso correspondiente al usuario de prueba, y agregar casos negativos explícitos. Es parte del alcance, no un efecto colateral no gestionado.
- **[Caché de permisos servida hasta 5 min tras el cambio de roles]** → Mitigación: invalidación explícita en la resiembra; comportamiento ya conocido y documentado del proyecto.
