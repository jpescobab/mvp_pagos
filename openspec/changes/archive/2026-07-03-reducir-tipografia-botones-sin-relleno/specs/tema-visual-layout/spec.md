## MODIFIED Requirements

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

## ADDED Requirements

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
