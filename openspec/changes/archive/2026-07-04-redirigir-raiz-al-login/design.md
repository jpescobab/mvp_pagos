## Context

`Route::inertia('/', 'welcome')->name('home');` es el único uso real de la página `welcome.tsx` (scaffold sin personalizar) en toda la app. El nombre de ruta `home` se usa en tres tests (`AuthenticationTest::'users can logout'`, `ProfileUpdateTest::'user can delete their account'`, `ExampleTest`) únicamente para comparar el `Location` del redirect tras logout/eliminación de cuenta — ninguno depende de qué renderiza `/` al visitarla directamente, excepto `ExampleTest`, que sí la visita y espera 200.

## Goals / Non-Goals

**Goals:**
- Que visitar la raíz del sitio lleve a la experiencia institucional (login CAPJ +), no al scaffold de Laravel.
- Mantener el nombre de ruta `home` intacto para no romper `route('home')` en el resto del código/tests.

**Non-Goals:**
- No se elimina `resources/js/pages/welcome.tsx` del repositorio en este change (queda sin ruta que lo use; se puede limpiar en un change de aseo posterior si se decide no conservarlo).
- No se cambia el comportamiento de Fortify para usuarios ya autenticados (ya redirige correctamente vía `config('fortify.home')`).

## Decisions

1. **`Route::redirect('/', '/login')->name('home')` en vez de `Route::inertia('/', 'login')`.**
   Renderizar el login directamente en la raíz duplicaría la lógica de Fortify (que ya gobierna `/login` con su propio controlador, middleware `guest` y formulario). Un redirect simple delega toda la lógica de autenticación a la ruta ya existente, incluyendo el rebote automático a `/dashboard` si el visitante ya tiene sesión.

2. **Contraseña de `sadmin@pjud.cl` fijada explícitamente en el seeder (`Hash::make('sadmin123')`), no dejada en el valor por defecto de la factory.**
   El usuario pidió una contraseña específica y conocida para este usuario de arranque; dejar el valor por defecto de `UserFactory` (`password`) sería inconsistente con el pedido y con el email institucional ya personalizado (`sadmin@pjud.cl`).

## Risks / Trade-offs

- [Riesgo] `welcome.tsx` queda sin ninguna ruta que lo renderice → Mitigación: no se borra (evita romper algo no visto); queda documentado como candidato a limpieza futura.
- [Riesgo] Cualquier integración externa que dependiera de `/` respondiendo 200 con contenido HTML dejaría de funcionar (ahora responde 302) → Mitigación: es exactamente el comportamiento pedido; no hay integraciones conocidas que dependan de la página `welcome`.

## Migration Plan

Sin migraciones de esquema. Para que el usuario `sadmin@pjud.cl`/`sadmin123` exista con la contraseña nueva en la base de datos actual (no solo en bases nuevas), se debe re-sembrar el usuario (recrear si ya existe con otra contraseña, o actualizar su hash) después de aplicar este change.
