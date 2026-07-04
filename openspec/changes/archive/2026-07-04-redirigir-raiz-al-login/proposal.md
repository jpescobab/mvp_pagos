## Why

La ruta raíz (`http://pagos.test/`) hoy renderiza la página `welcome` del scaffolding original de `laravel/react-starter-kit` ("Let's get started", enlaces a documentación/Laracasts) — nunca se personalizó y no tiene relación con la identidad institucional CAPJ +. El usuario pidió que la raíz lleve directo al login del sistema. Se aprovecha además para fijar la contraseña del usuario `sadmin@pjud.cl` sembrado por `DatabaseSeeder`, que hoy usa la contraseña por defecto de la factory (`password`) en vez de una conocida.

## What Changes

- `routes/web.php`: la ruta `/` (nombre `home`, se mantiene igual para no romper los usos existentes de `route('home')` como destino de logout/eliminación de cuenta) pasa de renderizar `welcome` a redirigir a `/login`. Un usuario ya autenticado que visite `/` es rebotado por el middleware `guest` de la ruta de login hasta `/dashboard` (comportamiento de Fortify ya existente, sin cambios).
- `database/seeders/DatabaseSeeder.php`: el usuario `sadmin@pjud.cl` sembrado se crea con la contraseña `sadmin123` en vez de la contraseña por defecto de la factory.
- `tests/Feature/ExampleTest.php` (boilerplate del starter kit, sin relación con el dominio CAPJ): se actualiza para reflejar que la raíz ahora redirige en vez de responder 200.
- Sin cambios en `config('fortify.home')` (ya apunta a `/dashboard`) ni en el resto de rutas de autenticación.

## Capabilities

### New Capabilities
- (ninguna nueva; ver Modified)

### Modified Capabilities
- `tema-visual-layout`: se agrega a la identidad de marca de la aplicación el requisito de que la raíz del sitio lleve a la experiencia institucional (login), nunca al scaffolding de `laravel/react-starter-kit`.

## Impact

- Código: `routes/web.php`, `database/seeders/DatabaseSeeder.php`, `tests/Feature/ExampleTest.php`.
- Sin impacto en Fortify, permisos, ni en las páginas de auth existentes.
- El usuario `sadmin@pjud.cl` / `sadmin123` (rol `superadmin`) queda disponible tras correr `php artisan migrate:fresh --seed` o al re-sembrar en la base de datos actual.
