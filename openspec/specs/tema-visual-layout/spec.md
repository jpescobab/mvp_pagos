# Spec: tema-visual-layout

## Purpose

Define la identidad visual institucional de la aplicación ("CAPJ +"), el tema de colores/tipografía que reemplaza el scaffolding neutro de `laravel/react-starter-kit`, la navegación principal del sidebar agrupada por módulo y limitada a los módulos realmente implementados, el login institucional, el topbar con tema y menú de usuario, y el panel general con datos reales.

## Requirements

### Requirement: Identidad de marca de la aplicación
El sistema SHALL identificarse como "CAPJ +" en toda superficie de marca visible al usuario (logo del sidebar, título de la pestaña del navegador), reemplazando el branding del scaffolding de `laravel/react-starter-kit`. La ruta raíz del sitio SHALL llevar a la experiencia institucional (login) en vez de a la página `welcome` del scaffolding.

#### Scenario: Logo del sidebar
- **WHEN** un usuario autenticado visualiza cualquier página con layout de sidebar
- **THEN** el encabezado del sidebar muestra la marca "CAPJ +", no "Laravel Starter Kit"

#### Scenario: Título de la pestaña del navegador
- **WHEN** un usuario carga cualquier página de la aplicación
- **THEN** el `<title>` de la página refleja el nombre configurado de la aplicación ("CAPJ +"), no "Laravel"

#### Scenario: Raíz del sitio lleva al login institucional
- **WHEN** un visitante no autenticado visita la raíz del sitio (`/`)
- **THEN** es redirigido a la página de login institucional, no a la página `welcome` del scaffolding

#### Scenario: Raíz del sitio para un usuario ya autenticado
- **WHEN** un usuario ya autenticado visita la raíz del sitio (`/`)
- **THEN** termina en el panel general (`/dashboard`), sin quedar en la página de login

### Requirement: Tema visual con paleta y tipografía institucional
El sistema SHALL aplicar una paleta de colores (primario azul, semánticos verde/rojo/ámbar con variantes suaves para badges y deltas, variantes dark-mode), tipografía (`Manrope` como fuente principal) y una escala de tamaños de texto (`--text-xs` a `--text-2xl`) reducida frente a los valores por defecto de Tailwind, definidas como tokens de tema, con radio base de 16px para tarjetas y superficies, sin alterar los nombres de las variables CSS que consumen los componentes UI existentes.

#### Scenario: Color primario de acciones
- **WHEN** se renderiza un componente con `bg-primary` o `text-primary` (ej. un botón primario)
- **THEN** el color resultante corresponde al azul institucional definido en el tema, no al gris neutro original

#### Scenario: Tipografía principal
- **WHEN** se renderiza texto con la fuente sans-serif por defecto del tema
- **THEN** la fuente aplicada es `Manrope`, no `Instrument Sans`

#### Scenario: Tokens semánticos suaves
- **WHEN** se renderiza un badge o indicador de estado con variante suave (éxito, alerta, error)
- **THEN** el color proviene de los tokens semánticos del tema (verde/ámbar/rojo con fondo suave), no de valores hex escritos en el componente

#### Scenario: Escala tipográfica reducida en toda la aplicación
- **WHEN** se renderiza cualquier texto con una utilidad `text-xs`, `text-sm`, `text-base`, `text-lg`, `text-xl` o `text-2xl` en cualquier página, el sidebar de navegación o un componente compartido
- **THEN** el tamaño resultante corresponde a la escala reducida definida en el tema (no a los tamaños por defecto de Tailwind), aplicada automáticamente sin necesidad de clases adicionales por página

### Requirement: Botones institucionales sin relleno de color sólido
El sistema SHALL presentar los botones con variante semántica de color (`default`/primario, `secondary`, `destructive`) sin relleno de color sólido: solo con borde y texto del color semántico correspondiente, con un fondo suave únicamente al pasar el cursor (`hover`). Las variantes sin color (`outline`, `ghost`, `link`) no cambian.

#### Scenario: Botón de acción primaria sin relleno
- **WHEN** se renderiza un botón con la variante primaria/por defecto (ej. "Guardar", "Nuevo usuario")
- **THEN** el botón se muestra con borde y texto en el color primario institucional, sin fondo de color sólido

#### Scenario: Botón destructivo sin relleno
- **WHEN** se renderiza un botón con la variante `destructive` (ej. "Eliminar")
- **THEN** el botón se muestra con borde y texto en el color destructivo, sin fondo de color sólido

#### Scenario: Retroalimentación visual al pasar el cursor
- **WHEN** el usuario pasa el cursor sobre un botón con variante `default`, `secondary` o `destructive`
- **THEN** el botón muestra un fondo suave del color semántico correspondiente como retroalimentación, sin llegar a un relleno sólido

### Requirement: Navegación principal como riel de íconos
El sistema SHALL presentar la navegación principal del sidebar como grupos colapsables por módulo funcional implementado, con la marca institucional (logo + "CAPJ +" + subtítulo "Finanzas y Ppto") en el encabezado, labels de grupo en mayúsculas, ítem activo destacado con fondo acentuado y barra lateral, y colapso del sidebar a modo ícono con tooltips. El sidebar SHALL seguir sin listar módulos funcionales que no tengan páginas implementadas. El sidebar SHALL además filtrar cada ítem individualmente según los permisos del usuario autenticado (`auth.permissions`), mostrando solo aquellos a los que el usuario tiene acceso, y SHALL ocultar un grupo completo si, tras filtrar, no le queda ningún ítem visible.

#### Scenario: Grupos por módulo implementado
- **WHEN** un usuario autenticado visualiza el sidebar principal
- **THEN** los ítems de navegación aparecen agrupados por módulo (General, Administración, Pago de Proveedores, Adquisiciones, Reportabilidad, Integraciones) y no como lista plana
- **AND** el grupo Administración incluye, además de las funciones de administración de usuarios y seguridad, los catálogos de consulta (Proveedores, Clientes Medidores, Centros Financieros, Centros de Costos)

#### Scenario: Ítem activo destacado
- **WHEN** el usuario navega a una página de un módulo
- **THEN** el ítem correspondiente del sidebar se muestra con estado activo destacado y su grupo aparece expandido

#### Scenario: Sin módulos no implementados
- **WHEN** un usuario autenticado visualiza el sidebar principal
- **THEN** no se muestran entradas para módulos sin páginas implementadas (p. ej. Presupuesto, Contabilidad, Mercado Público)

#### Scenario: Sin enlaces al scaffolding original
- **WHEN** un usuario autenticado visualiza el sidebar
- **THEN** no se muestran enlaces al repositorio o documentación de `laravel/react-starter-kit`

#### Scenario: Ítem oculto sin el permiso requerido
- **WHEN** un usuario autenticado sin el permiso que gobierna un ítem del sidebar (p. ej. `usuarios.ver`, `auditoria.ver`, `roles.administrar`, `core_institucional.administrar`, `reportabilidad.ver`, `informes.ver`) visualiza el sidebar principal
- **THEN** ese ítem no aparece en la navegación

#### Scenario: Grupo oculto si queda vacío tras filtrar
- **WHEN** un usuario autenticado no tiene permiso para ningún ítem de un grupo del sidebar
- **THEN** el grupo completo no se muestra

#### Scenario: Ítems de acceso abierto siguen visibles
- **WHEN** un usuario autenticado sin permisos administrativos visualiza el sidebar principal
- **THEN** sigue viendo los ítems cuya visibilidad es intencionalmente abierta a cualquier autenticado (Casos, Egresos CGU, Procesos de Adquisición, Conectores Playwright, Definiciones de Workflow, Importaciones SGF, Sistemas Externos, Indicadores Económicos)

### Requirement: Imports de rutas del sidebar con nombre, no por defecto
El sidebar principal SHALL importar con nombre únicamente las funciones de ruta de Wayfinder que efectivamente usa (por ejemplo, `index`), en vez de importar el export por defecto de un módulo de rutas (que agrupa todos los métodos del controlador), por consistencia con el patrón ya usado en el resto de componentes globales (`app-header.tsx`, `user-menu-content.tsx`). Nota: en la práctica esto no logró reducir el tamaño del bundle (ver `openspec/changes/archive/*-sidebar-route-imports-tree-shaking/proposal.md`, sección "Resultado medido") porque el código generado por Wayfinder ata sus métodos al objeto exportado vía asignaciones a nivel de módulo, lo que impide a Rollup tree-shakearlos mientras cualquier otro consumidor de la app siga usando el export por defecto del mismo archivo.

#### Scenario: Import con nombre de la función de ruta usada
- **WHEN** se revisa el código fuente de `app-sidebar.tsx`
- **THEN** cada ítem de navegación importa la función de ruta correspondiente con import con nombre (p. ej. `import { index as proveedores } from '@/routes/maestros/proveedores'`)
- **AND** ningún ítem importa el export por defecto de un módulo de rutas

#### Scenario: Los enlaces del sidebar no cambian
- **WHEN** un usuario autenticado visualiza el sidebar principal tras el cambio de imports
- **THEN** cada ítem de navegación apunta exactamente a la misma URL que antes del cambio

### Requirement: Login institucional
El sistema SHALL presentar la página de inicio de sesión con la identidad institucional: logo del Poder Judicial como fondo dentro de la tarjeta central (baja opacidad, detrás del formulario), título "Bienvenido a CAPJ +", subtítulo "Sección Finanzas y Presupuesto - Zonal Coyhaique", y tarjeta central sobre una escena de fondo institucional. La lógica de autenticación (Fortify) no cambia. El sistema SHALL NOT mostrar chips de indicadores económicos en esta página. La tarjeta central SHALL permanecer centrada horizontal y verticalmente en viewports de tamaño desktop, tablet y mobile en orientación normal.

#### Scenario: Logo como fondo de la tarjeta
- **WHEN** un visitante carga la página de login
- **THEN** el logo del Poder Judicial se muestra como elemento de fondo dentro de la tarjeta central, detrás del formulario, y no en la barra superior

#### Scenario: Autenticación intacta
- **WHEN** un usuario envía credenciales válidas desde el login
- **THEN** inicia sesión mediante el flujo Fortify existente y es redirigido al panel

#### Scenario: Tarjeta centrada en tamaños de viewport habituales
- **WHEN** un visitante carga la página de login en un viewport de tamaño desktop, tablet o mobile en orientación portrait
- **THEN** la tarjeta central se muestra centrada horizontal y verticalmente, sin desplazamiento perceptible por scrollbars ni por el espacio reservado para la barra superior o el pie de página

### Requirement: Topbar con tema y menú de usuario
El sistema SHALL presentar en el encabezado superior de las páginas autenticadas, junto a las migas de pan, un control para alternar el tema claro/oscuro y un avatar circular con las iniciales del usuario autenticado que abre el menú de usuario (perfil, configuración, cerrar sesión).

#### Scenario: Alternar tema desde el topbar
- **WHEN** el usuario pulsa el control de tema del topbar
- **THEN** la apariencia alterna entre claro y oscuro y la preferencia se conserva

#### Scenario: Menú de usuario desde el avatar
- **WHEN** el usuario pulsa su avatar en el topbar
- **THEN** se despliega el menú de usuario con acceso a configuración y cierre de sesión

### Requirement: Desplegable de indicadores económicos en el topbar
El sistema SHALL presentar en el topbar de las páginas autenticadas, junto al control de tema, un botón con ícono de indicadores económicos que al pulsarse despliega el último valor registrado de UF, UTM, dólar e IPC, con el mismo formato (decimales, símbolo, etiqueta) que usan las tarjetas de indicadores del panel general. El sistema SHALL NOT exigir un permiso adicional para ver este desplegable, dado que los indicadores económicos ya son consultables por cualquier usuario autenticado.

#### Scenario: Abrir el desplegable de indicadores
- **WHEN** un usuario autenticado pulsa el botón de indicadores económicos del topbar
- **THEN** se despliega una lista con el último valor registrado de UF, UTM, dólar e IPC

#### Scenario: Disponible en cualquier página autenticada
- **WHEN** un usuario autenticado visualiza cualquier página con el layout de sidebar, no solo el panel general
- **THEN** el botón de indicadores económicos del topbar está presente y funcional

#### Scenario: Sin datos para un indicador
- **WHEN** no existe ningún valor importado todavía para alguno de los cuatro indicadores
- **THEN** el desplegable omite esa fila en vez de mostrar un valor inventado o un error

### Requirement: Panel general con datos reales
El sistema SHALL presentar como página de inicio autenticada un "Panel general" con: tarjetas KPI con conteos reales (casos de pago activos, egresos CGU del mes, procesos de adquisición activos, informes razonados en curso), chips con los últimos indicadores económicos registrados, y una tabla de casos de pago recientes con su estado y enlace al detalle. El panel SHALL NOT mostrar datos inventados ni métricas sin respaldo en la base de datos.

#### Scenario: KPIs con conteos reales
- **WHEN** un usuario autenticado carga el panel general
- **THEN** cada tarjeta KPI muestra el conteo real obtenido de la base de datos

#### Scenario: Casos recientes con enlace
- **WHEN** existen casos de pago registrados
- **THEN** el panel lista los más recientes con su estado actual y cada fila enlaza al detalle del caso

#### Scenario: Panel vacío sin errores
- **WHEN** un usuario autenticado carga el panel general sin datos en los módulos
- **THEN** el panel se renderiza con conteos en cero y estados vacíos, sin errores

### Requirement: Listados tabulares densos
El sistema SHALL presentar cualquier página de listado/índice tabular (catálogos de consulta, tablas maestras u otro listado paginado) con: columnas de ancho fijo que no se reordenan según el contenido más largo, una identidad visual (avatar con iniciales u otro indicador equivalente) junto al campo principal de cada fila, un badge de estado con los tokens semánticos del tema cuando la entidad tenga un estado, columnas secundarias con una sola línea de texto truncada y su valor completo disponible al pasar el cursor, ocultamiento progresivo de columnas secundarias en viewports angostos, y un menú de acciones desplegable al final de la fila (en vez de íconos sueltos) donde las acciones aún no implementadas se muestran deshabilitadas con la indicación "Disponible próximamente". El título de página y los controles del encabezado del listado SHALL usar la escala tipográfica reducida del tema para maximizar el espacio disponible para los datos.

#### Scenario: Columnas de ancho fijo
- **WHEN** un usuario autenticado visualiza un listado tabular con el sidebar expandido
- **THEN** todas las columnas permanecen visibles dentro del viewport, sin que el contenido más largo de una columna empuje a otra columna fuera de la vista

#### Scenario: Texto truncado con valor completo accesible
- **WHEN** el valor de una columna secundaria (por ejemplo dirección o correo) excede el ancho disponible de su columna
- **THEN** el texto se trunca en una sola línea y su valor completo queda disponible al pasar el cursor sobre la celda

#### Scenario: Columnas secundarias ocultas en viewports angostos
- **WHEN** un usuario autenticado visualiza un listado tabular en un viewport angosto
- **THEN** las columnas secundarias se ocultan progresivamente, dejando visibles el campo principal, el estado y las acciones

#### Scenario: Acciones agrupadas en un menú desplegable
- **WHEN** un usuario autenticado abre el menú de acciones de una fila de un listado tabular
- **THEN** las acciones se muestran agrupadas en un desplegable en vez de íconos sueltos en la fila
- **AND** las acciones que aún no están implementadas se muestran deshabilitadas con la indicación "Disponible próximamente"

#### Scenario: Título de listado con tipografía reducida
- **WHEN** un usuario autenticado visualiza el encabezado de cualquier página de listado/índice
- **THEN** el título de la página usa la escala tipográfica reducida del tema, dejando el mayor espacio vertical posible para la tabla de datos

#### Scenario: Columna con entidad relacionada
- **WHEN** la entidad del listado tiene una relación jerárquica directa con otra entidad (ej. un centro financiero con su jurisdicción, o un centro de costo con su centro financiero)
- **THEN** el listado muestra el nombre de la entidad relacionada como columna secundaria, truncado con tooltip igual que cualquier otra columna secundaria

#### Scenario: Valor nulo en columna opcional
- **WHEN** un campo opcional de la entidad listada es `null` para una fila
- **THEN** la celda correspondiente muestra el indicador `"—"` en vez de quedar en blanco o producir un error

### Requirement: Formato numérico global
El sistema SHALL presentar todo número de negocio visible al usuario (montos en pesos, indicadores económicos, cantidades, KPIs, contadores de paginación y demás magnitudes) con un formato legible y consistente en toda la aplicación: separador de miles con punto (`.`) y separador decimal con coma (`,`) — convención `es-CL`. El sistema SHALL resolver este formateo mediante un helper/componente central reutilizable en `resources/js` en vez de lógica de formateo repetida por página. Este requirement SHALL NOT aplicar a identificadores, códigos institucionales, años ni otros valores que no representen una magnitud de negocio.

#### Scenario: Monto grande con miles y decimales
- **WHEN** una vista renderiza un monto o cantidad igual o mayor a 1.000
- **THEN** se muestra con punto como separador de miles y, si tiene decimales, coma como separador decimal (ej. `1.234.567,89`)

#### Scenario: Reutilización del helper central
- **WHEN** una página nueva o existente necesita mostrar un monto, indicador o cantidad
- **THEN** usa el helper/componente central de formato numérico en vez de invocar `Intl.NumberFormat` o `toLocaleString` de forma ad-hoc

#### Scenario: Contadores de paginación con el mismo formato
- **WHEN** un usuario autenticado visualiza el contador "Mostrando X–Y de Z" de un listado paginado
- **THEN** los tres números (`X`, `Y`, `Z`) siguen el mismo formato `es-CL` que el resto de los números de la aplicación

### Requirement: Valores negativos en rojo
El sistema SHALL mostrar todo valor numérico de negocio negativo (monto, indicador, cantidad, KPI) en el color semántico "danger" del tema (rojo), reutilizando el token existente `text-destructive` sin introducir un color hardcodeado nuevo. El sistema SHALL NOT aplicar este color a valores en cero o positivos.

#### Scenario: Monto negativo resaltado
- **WHEN** una vista renderiza un monto o cantidad con valor negativo
- **THEN** el número se muestra en el color rojo semántico del tema, distinguible del texto normal

#### Scenario: Monto positivo o cero sin color especial
- **WHEN** una vista renderiza un monto o cantidad con valor cero o positivo
- **THEN** el número se muestra con el color de texto normal, sin el rojo reservado para negativos

### Requirement: Legibilidad tipográfica de cifras
Por ser una aplicación financiera, el sistema SHALL renderizar todo número de negocio (montos, indicadores, cantidades, KPIs) con la tipografía monoespaciada del tema (`font-mono` / `JetBrains Mono`, definida en `resources/css/app.css` y ya usada como convención para códigos e identificadores), de modo que los dígitos no se presten a confusión entre sí (ej. `0` con `8`, `1` con `l`) y las cifras en una misma columna queden alineadas. El sistema SHALL NOT usar para cifras una tipografía o estilo donde dígitos distintos resulten visualmente ambiguos.

#### Scenario: Cifra con tipografía monoespaciada
- **WHEN** una vista renderiza un monto, indicador, cantidad o KPI
- **THEN** el número se muestra con la fuente monoespaciada del tema, con ancho de dígito uniforme

#### Scenario: Columna de montos alineada
- **WHEN** una tabla o listado muestra varias filas con montos en la misma columna
- **THEN** las cifras quedan alineadas verticalmente entre filas gracias al ancho uniforme de la tipografía monoespaciada
