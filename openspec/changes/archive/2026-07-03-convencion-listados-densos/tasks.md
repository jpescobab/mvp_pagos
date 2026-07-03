## 1. Documentación

- [x] 1.1 Agregar el requirement "Listados tabulares densos" a `openspec/specs/tema-visual-layout/spec.md` (ya redactado en la spec delta de este change; se aplica al archivar).
- [x] 1.2 Actualizar `HARNESS_IA.md` (sección 15 "Reglas de implementación Laravel" o subsección nueva) con un párrafo corto: todo listado/índice React nuevo SHALL seguir el patrón de tabla densa de `tema-visual-layout`, con puntero a esa spec y a `resources/js/pages/maestros/proveedores/index.tsx` como implementación de referencia.

## 2. Validación

- [ ] 2.1 `openspec validate --specs tema-visual-layout --strict` tras sincronizar, sin errores.
- [ ] 2.2 Revisar que `HARNESS_IA.md` no duplique el detalle completo del patrón (solo referencia), consistente con el criterio ya usado en el harness para no mantener listas que se desactualizan.
