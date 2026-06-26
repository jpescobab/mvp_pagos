## Context

El usuario aportó 5 seeders reales de otro proyecto Laravel (`C:\laragon\www\erp`): `ItemsSeeder`, `AsignacionesSeeder`, `CatalogosSeeder`, `ProveedoresSeeder` (977 filas, SQL crudo MySQL) y `ClientesMedidoresSeeder`. Ese proyecto usa tablas planas y, en el caso de `ClientesMedidoresSeeder`, un modelo propio llamado "Jurisdiccion" que en realidad representa tribunales/edificios específicos — exactamente los mismos que en este proyecto ya son `ccostos` (sembrados en la tarea 1, mismos códigos: `1400010201`, `1400020301`, etc.).

## Goals / Non-Goals

**Goals:**
- Reproducir los datos reales de los 5 dominios con seeders, remapeados a la jerarquía y convenciones de este proyecto.
- Convertir `ProveedoresSeeder` de SQL MySQL (`INSERT IGNORE`) a `insertOrIgnore` de Laravel, compatible con PostgreSQL, sin retipear manualmente 977 filas (se generó con un script de conversión, no a mano).
- Corregir la confusión de `clientes_medidores` → "Jurisdiccion" del origen, mapeando correctamente a `ccosto_id`.

**Non-Goals:**
- No se sembran datos reales de `funcionarios` (no se aportaron) — solo el esquema.
- No se modela `asignacion_id` en `catalogos` — se confirmó explícitamente con el usuario replicar la estructura real (catálogo → ítem directo), no inventar una jerarquía de 3 niveles que el propio origen no garantiza (hay catálogos sin asignación correspondiente en los datos reales).

## Decisions

- **`catalogos.item_id`, no `catalogos.asignacion_id`.** Decisión explícita del usuario: replicar la estructura real del origen en vez de un modelo "más correcto" que dejaría catálogos huérfanos (ej. `2208999001`-`006` no tienen asignación `2208999000` en los datos de origen).
- **`clientes_medidores.ccosto_id`, no `jurisdiccion_id`.** El proyecto origen llama "Jurisdiccion" a lo que aquí ya es `ccosto` (mismos códigos exactos). Crear jurisdicciones nuevas habría duplicado conceptualmente datos ya sembrados en la tarea 1.
- **Conversión de `ProveedoresSeeder` vía script, no a mano.** 977 filas en sintaxis SQL de MySQL (`INSERT IGNORE`, inválida en PostgreSQL) se convirtieron programáticamente a arrays PHP con `DB::table('proveedores')->insertOrIgnore([...])`, extrayendo los 6 campos por fila con un parser de comillas simples. Se verificó que no hay comillas escapadas en los datos que compliquen el parseo.
- **`activo` + soft deletes en las 6 tablas**, consistente con el criterio de la tarea 2 ("activo y soft deletes donde corresponda"). A diferencia de la jerarquía CAPJ (tarea 1, sin soft deletes, solo `activo` + FK restrict), estas son tablas maestras con mayor rotación (proveedores se dan de baja, medidores se desconectan) donde soft delete tiene sentido adicional a `activo`.
- **Auditoría diferida a la tarea 3**, ya acordado — estas tablas no implementan `audit_logs` todavía.
- **`funcionarios` sin seeder real** — el usuario confirmó que no tiene esos datos por ahora; se construye solo el esquema (`rut` único, `user_id` nullable, `ccosto_id`/`cfinanciero_id` nullable).

## Risks / Trade-offs

- **[Riesgo] El script de conversión de proveedores podría introducir errores de parseo en casos límite** → Mitigación: se verificó previamente que no hay backslashes ni comillas escapadas en los valores reales del archivo origen; se valida el conteo final (977) contra el origen después de generar el seeder.
- **[Riesgo] `clientes_medidores` con `proveedor_id` nulo** si el RUT del proveedor de servicio no existe → Mitigación: se confirmó que el RUT `88272600-2` ("EMPRESA ELECTRICA DE AYSEN S.A.") sí existe en los 977 proveedores migrados, por lo que no debería quedar nulo en la práctica; se mantiene nullable por si cambia la fuente de datos.
