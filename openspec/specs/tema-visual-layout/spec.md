# Spec: tema-visual-layout

## Purpose

Define la identidad visual institucional de la aplicación ("CAPJ +"), el tema de colores/tipografía que reemplaza el scaffolding neutro de `laravel/react-starter-kit`, y la navegación principal del sidebar como riel de íconos limitado a los módulos realmente implementados.

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
El sistema SHALL aplicar una paleta de colores (primario azul, semánticos verde/rojo/ámbar/violeta, variantes dark-mode) y tipografía (`Manrope` como fuente principal) definidas como tokens de tema, reemplazando la paleta neutra y la tipografía del scaffolding original, sin alterar los nombres de las variables CSS que consumen los componentes UI existentes.

#### Scenario: Color primario de acciones
- **WHEN** se renderiza un componente con `bg-primary` o `text-primary` (ej. un botón primario)
- **THEN** el color resultante corresponde al azul institucional definido en el tema, no al gris neutro original

#### Scenario: Tipografía principal
- **WHEN** se renderiza texto con la fuente sans-serif por defecto del tema
- **THEN** la fuente aplicada es `Manrope`, no `Instrument Sans`

### Requirement: Navegación principal como riel de íconos
El sistema SHALL presentar la navegación principal del sidebar como íconos con tooltip al pasar el cursor, sin agregar ítems de navegación para módulos funcionales que no tengan páginas implementadas.

#### Scenario: Único ítem de navegación real
- **WHEN** un usuario autenticado visualiza el sidebar principal
- **THEN** el único ítem de navegación visible es "Dashboard", agrupado bajo la etiqueta "General"

#### Scenario: Sin enlaces al scaffolding original
- **WHEN** un usuario autenticado visualiza el pie del sidebar
- **THEN** no se muestran enlaces al repositorio o documentación de `laravel/react-starter-kit`
