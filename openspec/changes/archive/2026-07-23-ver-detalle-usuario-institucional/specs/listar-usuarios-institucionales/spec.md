## ADDED Requirements

### Requirement: Navegar al detalle de un usuario desde el listado
El sistema SHALL ofrecer, en el menú de acciones por usuario del listado, la opción "Ver detalle" cuando el usuario autenticado tenga el permiso `usuarios.ver`, y esa opción SHALL navegar a la página de detalle de ese usuario. La opción SHALL NOT mostrarse a quien no tenga ese permiso.

#### Scenario: Navegar al detalle desde el menú de acciones
- **WHEN** un usuario con el permiso `usuarios.ver` selecciona "Ver detalle" en el menú de acciones de un usuario del listado
- **THEN** la aplicación navega a la página de detalle de ese usuario

#### Scenario: Sin permiso para ver el detalle
- **WHEN** un usuario sin el permiso `usuarios.ver` abre el menú de acciones de un usuario
- **THEN** la opción "Ver detalle" no aparece en el menú

## REMOVED Requirements

### Requirement: Acciones diferidas visibles pero deshabilitadas
**Reason**: El requirement quedó obsoleto: nombraba "Ver detalle", "Editar usuario" y "Asignar roles" como acciones diferidas, pero "Editar usuario" ya está implementada y navega a su página, "Asignar roles" ya no figura en el menú, y "Ver detalle" deja de estar diferida con este cambio. Tras él no queda ninguna acción diferida en el menú, por lo que el requirement no describe ningún comportamiento vigente.

**Migration**: No requiere migración de datos ni de API. El comportamiento de "Ver detalle" queda cubierto por el nuevo requirement "Navegar al detalle de un usuario desde el listado" de esta misma capability, y el detalle en sí por la capability `ver-detalle-usuario-institucional`. El patrón general de mostrar acciones no implementadas como deshabilitadas con la indicación "Disponible próximamente" sigue vigente en el proyecto para otros listados; lo que se retira es su aplicación a este menú, donde ya no queda nada pendiente.
