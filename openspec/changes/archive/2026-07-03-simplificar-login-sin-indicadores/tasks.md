## 1. Backend

- [x] 1.1 Editar `app/Providers/FortifyServiceProvider.php`: quitar `'indicadores' => ...` de `Fortify::loginView()`.

## 2. Frontend: layout de login

- [x] 2.1 Editar `resources/js/layouts/auth/auth-simple-layout.tsx`: quitar el bloque de chips de indicadores (JSX), el tipo `IndicadorLogin`, `ETIQUETAS` y `formatearIndicador`; quitar el `usePage<{ indicadores?: ... }>()`.
- [x] 2.2 Quitar la caja de logo (`<span>` con ambos `<img>`) de la barra superior; dejar el `<header>` solo con `ThemeToggle`.
- [x] 2.3 Agregar el logo (ambas variantes light/dark) como fondo dentro de la tarjeta central: la tarjeta pasa a `relative overflow-hidden`, el logo se posiciona `absolute` con opacidad baja y `pointer-events-none`, y el contenido existente de la tarjeta se envuelve en un contenedor `relative z-10`.

## 3. Frontend: botón de login

- [x] 3.1 Editar `resources/js/pages/auth/login.tsx`: quitar `bg-gradient-to-b from-primary to-[#1e40af] shadow-lg shadow-primary/30 dark:to-[#60a5fa]` del `className` del botón "Iniciar sesión"; conservar el resto de clases de tamaño/forma.

## 4. Tests

- [x] 4.1 Editar `tests/Feature/Auth/AuthenticationTest.php`: eliminar los tests `'login screen incluye el ultimo valor por tipo de indicador cuando hay datos'` y `'login screen incluye un arreglo vacio de indicadores sin datos'` (y sus imports ya sin uso: `IndicadorEconomico`, `IndicadorEconomicoImportacion`, `AssertableInertia`).

## 5. Verificación

- [x] 5.1 Ejecutar `tests/Feature/Auth/*` completo (12/13, 1 skip preexistente por feature flag).
- [x] 5.2 Levantar el servidor de desarrollo y verificar en el preview, claro y oscuro: sin chips de indicadores, logo visible como fondo dentro de la tarjeta (opacidad baja, variante correcta por tema), botón "Iniciar sesión" sin relleno sólido con buen contraste, formulario y ThemeToggle funcionando igual.
- [x] 5.3 Ejecutar `npm run lint:check`, `npm run format:check` y `npm run types:check` (limpios); suite completa (272/276, 0 fallos — 1 falla intermitente no relacionada se confirmó flaky).

## 6. Documentación y cierre

- [x] 6.1 Ejecutar `/opsx:archive` para fusionar la spec delta en `openspec/specs/tema-visual-layout/spec.md` y archivar el change.
