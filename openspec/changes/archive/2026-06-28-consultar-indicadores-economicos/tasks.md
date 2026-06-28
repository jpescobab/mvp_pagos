## 1. Backend

- [x] 1.1 Crear `App\Http\Resources\Indicadores\IndicadorEconomicoResource` exponiendo `tipo`, `fecha_valor`, `periodo`, `valor`, `fuente`, `vigente_desde`, `vigente_hasta` (sin `source_payload`).
- [x] 1.2 Crear `App\Http\Controllers\Indicadores\IndicadorEconomicoController::index()`: pagina `IndicadorEconomico` ordenado por `id` descendente, filtrando por `tipo` si se envía y es uno de `UF`, `USD`, `UTM`, `UTA`, `IPC`.
- [x] 1.3 Crear `routes/indicadores.php` con `GET /indicadores-economicos` (middleware `auth`) y registrarla en `routes/web.php`.

## 2. Frontend

- [x] 2.1 Agregar tipo `IndicadorEconomico` y reutilizar `Paginated<T>` en `resources/js/types/indicadores.ts`.
- [x] 2.2 Crear `resources/js/pages/indicadores-economicos/index.tsx`: tabla paginada + `Select` de filtro por tipo (todos los tipos + "Todos"), estado vacío explícito.
- [x] 2.3 Agregar entrada "Indicadores Económicos" en `mainNavItems` de `resources/js/components/app-sidebar.tsx`.

## 3. Tests y validación

- [x] 3.1 Feature test: listar indicadores sin filtro devuelve todos los tipos paginados.
- [x] 3.2 Feature test: listar con `?tipo=UF` devuelve solo indicadores UF.
- [x] 3.3 Feature test: usuario no autenticado es redirigido al login.
- [x] 3.4 Ejecutar `composer test` y `npm run lint:check`/`npm run types:check`.
- [x] 3.5 Verificación manual en navegador: sembrar indicadores de prueba, confirmar que la página los muestra y que el filtro por tipo funciona.
