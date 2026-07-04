## 1. Stepper con flechas

- [x] 1.1 `proveedor-formulario.tsx`: reemplazar la `TabsList` en grilla por un stepper con separadores `ChevronRight` entre cada `TabsTrigger`, con un círculo numerado (o check si el paso está completo) junto a cada etiqueta.

## 2. Botón de guardar permanente

- [x] 2.1 Mostrar el botón de guardar ("Registrar proveedor"/"Guardar cambios") en todos los pasos, junto a "Anterior"/"Siguiente" (ya no reemplaza a "Siguiente" solo en el último paso).
- [x] 2.2 Mantener la condición de habilitación en `pasosCompletos.identificacion` (RUT + razón social), los únicos campos `required` del backend.
- [x] 2.3 Cambiar "Siguiente" a `variant="outline"` para no competir visualmente con el botón de guardar.

## 3. Validación

- [x] 3.1 `vendor/bin/pint --dirty`, `npm run lint:check`, `npm run format:check`, `npm run types:check`, `composer types:check`, `php artisan test --compact`.
- [x] 3.2 Verificado en el navegador: el botón de guardar aparece deshabilitado al crear un proveedor nuevo (sin RUT/nombre) y se habilita al completarlos, visible junto a "Siguiente" desde el primer paso; en edición de un proveedor existente aparece habilitado desde el primer paso.
- [x] 3.3 Sincronizar la spec delta en `openspec/specs/registrar-proveedor/spec.md` y archivar el change.
