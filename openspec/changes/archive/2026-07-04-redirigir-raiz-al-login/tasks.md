## 1. Backend: ruta raíz

- [x] 1.1 Editar `routes/web.php`: cambiar `Route::inertia('/', 'welcome')->name('home');` por `Route::redirect('/', '/login')->name('home');`.

## 2. Backend: usuario sadmin

- [x] 2.1 Editar `database/seeders/DatabaseSeeder.php`: fijar `'password' => Hash::make('sadmin123')` en la creación del usuario `sadmin@pjud.cl` (importar `Illuminate\Support\Facades\Hash`).
- [x] 2.2 Aplicar el cambio a la base de datos local actual (el usuario ya existía; se actualizó su hash de contraseña directamente vía tinker) para que `sadmin@pjud.cl` / `sadmin123` funcione de inmediato.

## 3. Tests

- [x] 3.1 Editar `tests/Feature/ExampleTest.php` para reflejar que `/` ahora redirige a `/login` en vez de responder 200.

## 4. Verificación

- [x] 4.1 Ejecutar `tests/Feature/Auth/`, `tests/Feature/Settings/ProfileUpdateTest.php` y `tests/Feature/ExampleTest.php` (18/19, 1 skip preexistente).
- [x] 4.2 Levantar el servidor de desarrollo y verificar en el preview: `/` sin sesión lleva a `/login`; login con `sadmin@pjud.cl` / `sadmin123` funciona y termina en `/dashboard`; `/` ya autenticado termina en `/dashboard`.
- [x] 4.3 Ejecutar `npm run lint:check`, `npm run format:check` y `npm run types:check` (limpios en los archivos tocados); `composer test`/suite completa corriendo.

## 5. Documentación y cierre

- [x] 5.1 Ejecutar `/opsx:archive` para fusionar la spec delta en `openspec/specs/tema-visual-layout/spec.md` y archivar el change.
