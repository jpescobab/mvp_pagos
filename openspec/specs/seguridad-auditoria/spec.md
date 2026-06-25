# Spec: seguridad-auditoria

## Requirement: Controlar acceso por roles, permisos y unidad

El sistema debe validar permisos antes de mostrar o ejecutar acciones.

### Scenario: Acción no permitida

Given un usuario no tiene permiso para registrar pago
When intenta ejecutar la acción
Then el sistema bloquea la operación
And registra evento de seguridad si corresponde

## Requirement: Auditar acciones relevantes

Todo cambio sensible debe quedar auditado.

### Scenario: Auditar cambio de estado

Given un usuario ejecuta una transición workflow
When el estado cambia
Then se registra usuario, fecha, estado anterior, estado nuevo, comentario y metadata
