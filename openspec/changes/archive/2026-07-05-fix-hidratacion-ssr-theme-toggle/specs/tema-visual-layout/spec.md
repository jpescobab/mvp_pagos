## MODIFIED Requirements

### Requirement: Topbar con tema y menú de usuario
El sistema SHALL presentar en el encabezado superior de las páginas autenticadas, junto a las migas de pan, un control para alternar el tema claro/oscuro y un avatar circular con las iniciales del usuario autenticado que abre el menú de usuario (perfil, configuración, cerrar sesión). El control de tema SHALL renderizar el mismo ícono y aria-label en el primer render del servidor (SSR) y en la primera pintura del cliente, de modo que la hidratación de React no falle ni regenere el árbol de la página.

#### Scenario: Alternar tema desde el topbar
- **WHEN** el usuario pulsa el control de tema del topbar
- **THEN** la apariencia alterna entre claro y oscuro y la preferencia se conserva

#### Scenario: Menú de usuario desde el avatar
- **WHEN** el usuario pulsa su avatar en el topbar
- **THEN** se despliega el menú de usuario con acceso a configuración y cierre de sesión

#### Scenario: Carga de página con tema oscuro sin error de hidratación
- **WHEN** un usuario con preferencia de tema oscuro guardada (cookie o almacenamiento local) carga cualquier página autenticada
- **THEN** el control de tema se hidrata sin que la consola del navegador muestre un error de hidratación de React, y el ícono/aria-label mostrado corresponden al tema oscuro desde el primer render
