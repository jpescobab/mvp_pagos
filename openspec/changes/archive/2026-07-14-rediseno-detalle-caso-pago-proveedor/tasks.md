## 1. Panel "Preparación para Asignar Egreso"

- [x] 1.1 `resources/js/components/pago-proveedores/preparacion-egreso-card.tsx` (nuevo): recibe `caso: CasoPagoProveedor`, calcula con un helper puro (`calcularPreparacionEgreso(caso)`) los 4 criterios — tipo de proceso clasificado (`caso.proceso.tipo_proceso_pago_id !== null`), Traspaso registrado (`(caso.registros_contables_cgu ?? []).length > 0`), checklist obligatorio completo (`caso.proceso.checklist?.items.filter(i => i.tipo_requisito === 'obligatorio').every(i => i.documento_id !== null) ?? false`), proveedor identificado (`caso.proveedor.nombre !== null` — usar el mismo campo que ya distingue "identificado" en el resto de la UI); comentario en el código citando `ListoParaEgresoResolver` (`app/Services/PagoProveedores/ListoParaEgresoResolver.php`) como fuente de verdad del criterio
- [x] 1.2 Renderizar barra de progreso (X/4) + 4 tarjetas de estado (ok/pendiente) con los tokens ya existentes (`bg-success-soft text-success`, `bg-warning-soft text-warning`, `rounded-xl`/`rounded-md`), sin introducir colores nuevos
- [x] 1.3 Insertar `<PreparacionEgresoCard caso={caso} />` en `show.tsx`, inmediatamente después del header y antes del `Alert` de revisión en dos instancias

## 2. Extracción de subcomponentes existentes

- [x] 2.1 `resources/js/components/pago-proveedores/checklist-documental-card.tsx` (nuevo): mover la sección "Checklist documental" completa (líneas ~766-942 de `show.tsx` actual) a este componente, recibiendo como props exactamente los mismos valores/callbacks que hoy usa esa sección (`caso`, `errorDocumento`, `documentosHuerfanos`, `puedeGestionarDocumentos`, `subiendoDocumento`, `subirDocumento`, `huerfanoSeleccionado`, `setHuerfanoSeleccionado`, `vinculandoHuerfano`, `vincularHuerfano`); agregar el ícono circular de estado (check verde si `estado_cumplimiento !== 'pendiente'`, círculo gris si `pendiente`) delante de cada ítem, sin cambiar ningún control existente
- [x] 2.2 `resources/js/components/pago-proveedores/transiciones-sidebar-card.tsx` (nuevo): mover la sección "Transiciones disponibles" (líneas ~592-628 actuales) a este componente, mismos props (`transicionesVisibles`, `procesando`, `errorTransicion`, `ejecutar`, `setTransicionConComentario`)
- [x] 2.3 Actualizar `show.tsx` para importar y usar ambos componentes en lugar del JSX inline que reemplazan

## 3. Reorganización visual del resto de la página

- [x] 3.1 Envolver las secciones existentes en 3 grupos visuales con encabezado de sección (componente simple inline `<SeccionGrupo titulo="...">`, sin lógica): "Clasificación y expediente" (Proceso de adquisición vinculado, Tipo de proceso, `ChecklistDocumentalCard`, Documentos), "Financiero" (Registro contable CGU/Traspaso, Registro de pago bancario, Facturas), "Actividad" (Historial de transiciones, Egresos CGU asociados) — mismo contenido y orden relativo dentro de cada grupo que hoy, solo con el título de grupo agregado
- [x] 3.2 Aplicar layout de dos columnas (`grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-6`) con `TransicionesSidebarCard` y un resumen breve de datos del caso (período, número SGF, fecha SII, folio de egreso) en una columna lateral `sticky top-4`, y los 3 grupos de la sección 3.1 en la columna principal
- [x] 3.3 Verificar que el header (nombre de proveedor, sgf_id, monto, `EstadoBadge`, botón "Verificar en SGF") y el `Alert` de revisión en dos instancias permanecen fuera del grid, ocupando el ancho completo arriba de las dos columnas

## 4. Verificación

- [x] 4.1 `npm run types:check` y `npm run lint:check` sin errores
- [x] 4.2 `npm run build` sin errores ni warnings nuevos
- [x] 4.3 Verificar en el navegador, sobre un caso real: el panel de preparación refleja correctamente los 4 criterios (probar con un caso completo y uno incompleto), y los 8 flujos existentes siguen funcionando sin cambios de comportamiento: clasificar tipo de proceso, subir documento del checklist, vincular documento huérfano, registrar Traspaso, registrar pago bancario, registrar factura, ejecutar una transición (con y sin comentario), vincular/desvincular proceso de adquisición
- [x] 4.4 Revisar modo claro y oscuro, y el layout en una ventana angosta (`lg` colapsa a una sola columna) — el `Alert` de revisión en dos instancias y el estado "sin documentos huérfanos" (no muestra selector) también se revisan visualmente
