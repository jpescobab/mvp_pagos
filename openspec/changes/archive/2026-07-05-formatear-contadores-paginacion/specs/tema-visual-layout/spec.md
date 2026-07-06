## ADDED Requirements

### Requirement: Formato numérico institucional
El sistema SHALL presentar todo número visible al usuario (montos, conteos, porcentajes, contadores de paginación) con el formato del locale `es-CL`: separador de miles con punto, separador decimal con coma. Todo valor numérico que pueda ser negativo SHALL mostrarse en el color semántico de énfasis/error del tema cuando su valor sea negativo.

#### Scenario: Miles con punto y decimales con coma
- **WHEN** un usuario autenticado visualiza cualquier número mayor a 999 en la aplicación
- **THEN** el número se muestra con puntos como separador de miles y coma como separador decimal (p. ej. `69.542,00`), no en formato crudo sin separadores

#### Scenario: Valor negativo en color de énfasis
- **WHEN** un usuario autenticado visualiza un monto o número que puede ser negativo y su valor actual es negativo
- **THEN** el número se muestra con el color semántico de énfasis/error del tema, no con el color de texto por defecto

#### Scenario: Contadores de paginación con el mismo formato
- **WHEN** un usuario autenticado visualiza el contador "Mostrando X–Y de Z" de un listado paginado
- **THEN** los tres números (`X`, `Y`, `Z`) siguen el mismo formato `es-CL` que el resto de los números de la aplicación
