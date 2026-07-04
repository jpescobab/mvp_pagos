## Context

`proveedores` hoy es de solo lectura (`consulta-catalogo-proveedores`): 7 columnas (`rutproveedor`, `nombre`, `correo`, `direccion`, `contacto`, `imagen`, `activo`), sin `create`/`store`, sin Form Request, sin policy propia. El usuario adjuntó una captura de referencia de un formulario de alta por pasos (Identificación, Clasificación, Contacto, Domicilio, Datos bancarios) con un panel lateral de resumen y "completitud del registro", y un HTML standalone que en realidad es el **índice** de Proveedores de esa misma maqueta (no el formulario) — solo sirve como referencia de paleta/tipografía, no de estructura de campos, y usa botones con relleno sólido que contradicen el requirement ya vigente "Botones institucionales sin relleno de color sólido" (`tema-visual-layout`), así que no se copia tal cual.

## Goals / Non-Goals

**Goals:**
- Permitir registrar un proveedor con los datos que Finanzas realmente necesita para pagarle: identificación tributaria, clasificación (rubros), contacto comercial, domicilio estructurado y datos bancarios de la cuenta de destino.
- Reproducir la experiencia de formulario por pasos con panel de resumen/completitud de la referencia visual, adaptada al tema institucional ya vigente (tipografía reducida, botones sin relleno sólido, tokens semánticos).
- Mantener compatibilidad con los 7 campos existentes y con el contrato de `consulta-catalogo-proveedores` (no cambia lo que el índice devuelve).

**Non-Goals:**
- Edición de proveedores existentes (`edit`/`update`) — el proposal solo cubre alta. Se deja el botón "Editar" del índice como "Disponible próximamente".
- Guardado de borrador en servidor. El botón "Borrador" de la referencia queda deshabilitado con tooltip "Disponible próximamente"; el estado del formulario solo vive en el cliente mientras no se envía.
- Catálogo de bancos como tabla propia. Se usa una lista estática de bancos chilenos comunes en el frontend; el campo se valida como texto libre (`nullable|string|max:255`) para no bloquear un banco fuera de la lista.
- Verificación real de la cuenta bancaria o del documento de respaldo (OCR, validación de RUT ante SII, etc.) — el documento solo se guarda como adjunto.

## Decisions

- **Migración aditiva, no romper lo existente**: se agregan columnas nullable a `proveedores` (`giro`, `tipo_contribuyente`, `rubros` json, `contacto_cargo`, `contacto_telefono`, `region`, `comuna`, `banco`, `tipo_cuenta`, `numero_cuenta`, `condicion_pago`, `moneda`, `correo_pago`, `documento_respaldo_path`, `notas_internas`). `rutproveedor`, `nombre`, `correo`, `direccion`, `contacto`, `imagen`, `activo` no cambian de tipo ni de nullability.
- **Enums PHP para los campos de opción cerrada** (`App\Enums\Maestros\{TipoContribuyente,TipoCuentaBancaria,CondicionPago,Moneda,RubroProveedor}`), backed por string, con método `label(): string`. Se valida con `Rule::enum(...)` en el Form Request y se exponen como `[{value,label}]` en `ProveedorController::create()` para poblar los `Select`/checkboxes en React sin hardcodear texto en el frontend (consistente con "expediente documental variable" y con `catalogos()` de `UserController`). `rubros` se guarda como array de `RubroProveedor::value` en una columna `json` (selección múltiple real, no una sola opción).
- **Banco como texto libre con sugerencias**, no enum: a diferencia de los anteriores, la lista de bancos no es un dominio cerrado por reglas de negocio; una lista incompleta no debe bloquear el alta. El Select de React ofrece una lista estática de bancos comunes + opción "Otro" que habilita un input de texto; el backend solo valida `nullable|string|max:255`.
- **Documento de respaldo** se guarda en el disco privado `local` (no `public`), bajo `proveedores/{proveedor}/documento-respaldo.{ext}`, igual que el resto de documentos de expediente de la aplicación (nunca se expone por URL pública directa). Validación `nullable|file|mimes:pdf,jpg,jpeg,png|max:8192`. Se guarda solo la ruta relativa en `documento_respaldo_path`; no hay endpoint de descarga en este change (queda fuera de alcance, igual que la edición).
- **Autorización**: se crea `ProveedorPolicy` con `create(): bool => $user->can('core_institucional.administrar')`, registrada en `AppServiceProvider` junto a `CfinancieroPolicy`/`CcostoPolicy`. El listado (`index`) no cambia — sigue abierto a cualquier autenticado, como ya especifica `consulta-catalogo-proveedores`.
- **Un solo POST al final, no un POST por paso**: el wizard es 100% client-side (estado en `useForm` de Inertia), los 5 pasos son pestañas (`Tabs` de shadcn/Radix) navegables libremente con botones "Anterior"/"Siguiente" que solo mueven el índice de pestaña activa; el envío real ocurre una sola vez en el último paso. Si el `store()` devuelve errores de validación, el formulario salta a la primera pestaña que contenga un campo con error.
- **Completitud del registro es puramente derivada en el cliente**: un paso se marca "completo" cuando sus campos mínimos tienen valor (Identificación: rut+nombre; Clasificación: al menos 1 rubro; Contacto: nombre de contacto; Domicilio: dirección; Datos bancarios: banco+número de cuenta). No se persiste ni se envía al backend — es solo la barra de progreso y los ítems con check del panel "Resumen".
- **Primitivos de shadcn/ui a agregar** (`Tabs`, `Switch`, `Textarea`, `Progress`): mismo origen Radix UI que los componentes ya instalados (`@radix-ui/react-*`), se generan con el mismo estilo "new-york" ya configurado en `components.json`. No se introduce ninguna librería de UI nueva.

## Risks / Trade-offs

- [Riesgo] Agregar 14 columnas nullable en una sola migración infla el ancho de la tabla `proveedores` aunque la mayoría de los proveedores existentes (sembrados) las deje en `null`. → Mitigación: todas son nullable y sin default salvo `condicion_pago`/`moneda` (que sí tienen default porque la mayoría de los proveedores reales pagan en las mismas condiciones); no se requiere backfill.
- [Riesgo] La lista estática de rubros/bancos puede quedar corta frente a la realidad institucional. → Mitigación: rubros vive en un enum PHP (fácil de extender con un solo archivo, sin migración) y banco es texto libre desde el día uno.
- [Trade-off] No hay `edit`/`update` en este change, por lo que un error de tipeo en el alta requeriría re-crear el proveedor o esperar un change futuro de edición. Aceptable porque el proposal se limita explícitamente a "registrar", igual que el resto de tablas maestras (`cfinancieros`/`ccostos`) que hoy tampoco tienen edición.

## Migration Plan

1. Migración aditiva (`up`: agrega columnas nullable; `down`: las elimina). Sin backfill necesario.
2. Enums nuevos + `StoreProveedorRequest` + `ProveedorPolicy` + `create()`/`store()` en el controlador.
3. Frontend: primitivos shadcn nuevos, componentes de paso, página `create.tsx`, botón del índice deja de estar deshabilitado.
4. Rollback: si algo falla en producción, revertir la migración (`down`) es seguro porque ninguna columna nueva es referenciada por otro módulo todavía.
