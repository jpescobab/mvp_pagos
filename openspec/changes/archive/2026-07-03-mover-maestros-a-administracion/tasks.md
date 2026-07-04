## 1. Frontend

- [x] 1.1 En `resources/js/components/app-sidebar.tsx`: agregar "Proveedores" y "Clientes Medidores" a `administracionNavItems`; eliminar `maestrosNavItems`.
- [x] 1.2 Calcular dentro de `AppSidebar()` los ítems finales de "Administración" agregando condicionalmente "Centros Financieros" y "Centros de Costos" (`estructuraInstitucionalNavItems`) cuando `puedeAdministrarEstructura` sea verdadero, igual que hoy hace con "Maestros".
- [x] 1.3 Eliminar el `<NavGroup label="Maestros" .../>` del JSX.

## 2. Verificación

- [x] 2.1 Ejecutar `npm run lint:check`, `npm run format:check` y `npm run types:check` (limpios).
- [x] 2.2 Levantar el servidor de desarrollo y verificar en el preview: el grupo "Maestros" ya no existe; "Administración" incluye Usuarios, Auditoría, Definiciones de Workflow, Roles y Permisos, Proveedores, Clientes Medidores, y (solo con el permiso) Centros Financieros/Costos; las rutas siguen funcionando igual.

## 3. Documentación y cierre

- [x] 3.1 Ejecutar `/opsx:archive` para fusionar la spec delta en `openspec/specs/tema-visual-layout/spec.md` y archivar el change.
