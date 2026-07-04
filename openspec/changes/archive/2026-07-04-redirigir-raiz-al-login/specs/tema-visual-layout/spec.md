## MODIFIED Requirements

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
