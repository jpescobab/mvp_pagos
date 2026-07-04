# Spec: tema-visual-layout

## Purpose

Define la identidad visual institucional de la aplicación ("CAPJ +"), el tema de colores/tipografía que reemplaza el scaffolding neutro de `laravel/react-starter-kit`, la navegación principal del sidebar agrupada por módulo y limitada a los módulos realmente implementados, el login institucional con indicadores económicos, el topbar con tema y menú de usuario, y el panel general con datos reales.

## Requirements

### Requirement: Identidad de marca de la aplicación
El sistema SHALL identificarse como "CAPJ +" en toda superficie de marca visible al usuario (logo del sidebar, título de la pestaña del navegador), reemplazando el branding del scaffolding de `laravel/react-starter-kit`.

#### Scenario: Logo del sidebar
- **WHEN** un usuario autenticado visualiza cualquier página con layout de sidebar
- **THEN** el encabezado del sidebar muestra la marca "CAPJ +", no "Laravel Starter Kit"

#### Scenario: Título de la pestaña del navegador
- **WHEN** un usuario carga cualquier página de la aplicación
- **THEN** el `<title>` de la página refleja el nombre configurado de la aplicación ("CAPJ +"), no "Laravel"

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
El sistema SHALL presentar la navegación principal del sidebar como grupos colapsables por módulo funcional implementado, con la marca institucional (logo + "CAPJ +" + subtítulo "Finanzas y Ppto") en el encabezado, labels de grupo en mayúsculas, ítem activo destacado con fondo acentuado y barra lateral, y colapso del sidebar a modo ícono con tooltips. El sidebar SHALL seguir sin listar módulos funcionales que no tengan páginas implementadas.

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
