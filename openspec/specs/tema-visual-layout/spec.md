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
El sistema SHALL aplicar una paleta de colores (primario azul, semánticos verde/rojo/ámbar con variantes suaves para badges y deltas, variantes dark-mode) y tipografía (`Manrope` como fuente principal) definidas como tokens de tema, con radio base de 16px para tarjetas y superficies, sin alterar los nombres de las variables CSS que consumen los componentes UI existentes.

#### Scenario: Color primario de acciones
- **WHEN** se renderiza un componente con `bg-primary` o `text-primary` (ej. un botón primario)
- **THEN** el color resultante corresponde al azul institucional definido en el tema, no al gris neutro original

#### Scenario: Tipografía principal
- **WHEN** se renderiza texto con la fuente sans-serif por defecto del tema
- **THEN** la fuente aplicada es `Manrope`, no `Instrument Sans`

#### Scenario: Tokens semánticos suaves
- **WHEN** se renderiza un badge o indicador de estado con variante suave (éxito, alerta, error)
- **THEN** el color proviene de los tokens semánticos del tema (verde/ámbar/rojo con fondo suave), no de valores hex escritos en el componente

### Requirement: Navegación principal como riel de íconos
El sistema SHALL presentar la navegación principal del sidebar como grupos colapsables por módulo funcional implementado, con la marca institucional (logo + "CAPJ +" + subtítulo "Finanzas y Ppto") en el encabezado, labels de grupo en mayúsculas, ítem activo destacado con fondo acentuado y barra lateral, y colapso del sidebar a modo ícono con tooltips. El sidebar SHALL seguir sin listar módulos funcionales que no tengan páginas implementadas.

#### Scenario: Grupos por módulo implementado
- **WHEN** un usuario autenticado visualiza el sidebar principal
- **THEN** los ítems de navegación aparecen agrupados por módulo (General, Administración, Pago de Proveedores, Adquisiciones, Maestros, Reportabilidad, Integraciones) y no como lista plana

#### Scenario: Ítem activo destacado
- **WHEN** el usuario navega a una página de un módulo
- **THEN** el ítem correspondiente del sidebar se muestra con estado activo destacado y su grupo aparece expandido

#### Scenario: Sin módulos no implementados
- **WHEN** un usuario autenticado visualiza el sidebar principal
- **THEN** no se muestran entradas para módulos sin páginas implementadas (p. ej. Presupuesto, Contabilidad, Mercado Público)

#### Scenario: Sin enlaces al scaffolding original
- **WHEN** un usuario autenticado visualiza el sidebar
- **THEN** no se muestran enlaces al repositorio o documentación de `laravel/react-starter-kit`

### Requirement: Login institucional con indicadores económicos
El sistema SHALL presentar la página de inicio de sesión con la identidad institucional: logo del Poder Judicial, título "Bienvenido a CAPJ +", subtítulo "Sección Finanzas y Presupuesto - Zonal Coyhaique", tarjeta central sobre una escena de fondo institucional, y chips con los últimos valores reales de indicadores económicos (UF, UTM, UTA, IPC) cuando existan en la base de datos. La lógica de autenticación (Fortify) no cambia.

#### Scenario: Chips con indicadores reales
- **WHEN** un visitante carga la página de login y existen indicadores económicos en la base de datos
- **THEN** se muestran chips con el último valor registrado de cada indicador disponible

#### Scenario: Sin indicadores registrados
- **WHEN** un visitante carga la página de login y no existen indicadores económicos
- **THEN** la página se renderiza sin chips de indicadores y sin errores

#### Scenario: Autenticación intacta
- **WHEN** un usuario envía credenciales válidas desde el nuevo login
- **THEN** inicia sesión mediante el flujo Fortify existente y es redirigido al panel

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
El sistema SHALL presentar cualquier página de listado/índice tabular (catálogos de consulta, tablas maestras u otro listado paginado) con: columnas de ancho fijo que no se reordenan según el contenido más largo, una identidad visual (avatar con iniciales u otro indicador equivalente) junto al campo principal de cada fila, un badge de estado con los tokens semánticos del tema cuando la entidad tenga un estado, columnas secundarias con una sola línea de texto truncada y su valor completo disponible al pasar el cursor, ocultamiento progresivo de columnas secundarias en viewports angostos, y un menú de acciones desplegable al final de la fila (en vez de íconos sueltos) donde las acciones aún no implementadas se muestran deshabilitadas con la indicación "Disponible próximamente".

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
