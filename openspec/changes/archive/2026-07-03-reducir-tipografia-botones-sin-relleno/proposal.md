## Why

El usuario reporta que, incluso después de densificar el catálogo de proveedores, la tipografía sigue sintiéndose grande en toda la aplicación (páginas de contenido, listados/índices y el sidebar de navegación), lo que reduce la cantidad de información visible por pantalla — justo lo contrario del objetivo de la convención de "listados tabulares densos". Además, los botones con relleno de color sólido (variantes `default`, `secondary`, `destructive`) añaden peso visual innecesario frente a una interfaz que debe priorizar la lectura de datos institucionales. Se corrige de raíz, a nivel de tema, en vez de ajustar página por página.

## What Changes

- Redefinir la escala tipográfica del tema (`--text-xs` a `--text-2xl` en `@theme`, `resources/css/app.css`) con valores más pequeños que los por defecto de Tailwind, de forma que todas las páginas, el sidebar y cualquier componente que use las utilidades `text-*` se vean afectados automáticamente sin tocar archivo por archivo.
- Ajustar las variantes `default`, `secondary` y `destructive` de `resources/js/components/ui/button.tsx` para que ya no usen relleno de color sólido: pasan a ser solo borde + texto del color semántico correspondiente + fondo suave sutil en `hover`, manteniendo su identidad de color pero sin `bg-*` sólido. La variante `outline` existente y `ghost`/`link` no cambian (ya cumplían la convención).
- Actualizar el requirement "Tema visual con paleta y tipografía institucional" y "Listados tabulares densos" de `tema-visual-layout` para codificar esta convención (escala tipográfica reducida, botones sin relleno sólido) como obligatoria para toda página nueva y existente.
- Sin cambios de contenido, datos ni comportamiento — es un ajuste puramente visual a nivel de tema.

## Capabilities

### Modified Capabilities
- `tema-visual-layout`: la paleta/tipografía institucional pasa a usar una escala de tamaños de texto reducida a nivel de tema, y los botones de cualquier variante con color (default/secondary/destructive) dejan de usar relleno sólido, mostrándose solo con borde y texto del color semántico.

## Impact

- Código modificado: `resources/css/app.css` (escala `--text-*` en `@theme`), `resources/js/components/ui/button.tsx` (variantes sin relleno).
- Efecto en cascada automático sobre todas las páginas y componentes existentes que usan utilidades `text-*` y el componente `Button` (sidebar, listados/índices, formularios, páginas de detalle, login), sin necesidad de editarlas una por una.
- Verificación visual manual en al menos un listado denso (proveedores), el sidebar y un formulario con botón primario, en modo claro y oscuro.
