## MODIFIED Requirements

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
