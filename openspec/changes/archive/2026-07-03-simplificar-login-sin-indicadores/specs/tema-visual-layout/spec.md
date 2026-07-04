## REMOVED Requirements

### Requirement: Login institucional con indicadores económicos
**Reason**: Por decisión de producto se simplifica el login: se elimina la capacidad de mostrar chips de indicadores económicos y el logo del Poder Judicial deja de estar en la barra superior para pasar a ser un elemento de fondo dentro de la tarjeta central.
**Migration**: La identidad institucional (logo, título, subtítulo, tarjeta sobre escena de fondo) y la autenticación intacta se conservan bajo el nuevo requirement "Login institucional". No hay ruta de reemplazo para los chips de indicadores en el login; si se necesitan a futuro, se proponen como un change nuevo.

## ADDED Requirements

### Requirement: Login institucional
El sistema SHALL presentar la página de inicio de sesión con la identidad institucional: logo del Poder Judicial como fondo dentro de la tarjeta central (baja opacidad, detrás del formulario), título "Bienvenido a CAPJ +", subtítulo "Sección Finanzas y Presupuesto - Zonal Coyhaique", y tarjeta central sobre una escena de fondo institucional. La lógica de autenticación (Fortify) no cambia. El sistema SHALL NOT mostrar chips de indicadores económicos en esta página.

#### Scenario: Logo como fondo de la tarjeta
- **WHEN** un visitante carga la página de login
- **THEN** el logo del Poder Judicial se muestra como elemento de fondo dentro de la tarjeta central, detrás del formulario, y no en la barra superior

#### Scenario: Autenticación intacta
- **WHEN** un usuario envía credenciales válidas desde el login
- **THEN** inicia sesión mediante el flujo Fortify existente y es redirigido al panel
