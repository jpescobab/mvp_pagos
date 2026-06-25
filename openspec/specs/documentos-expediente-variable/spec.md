# Spec: documentos-expediente-variable

## Requirement: Resolver checklist documental desde backend

El sistema debe determinar documentos requeridos según reglas configurables.

### Scenario: Generar checklist documental

Given un caso de pago tiene tipo documental, modalidad, monto y estado
When el usuario abre el expediente
Then el backend entrega checklist documental
And cada item indica si es requerido, opcional, condicional o recomendado
And React solo renderiza la respuesta

### Scenario: Bloquear por documento faltante

Given una transición requiere recepción conforme
And el documento no está cargado o validado
When el usuario intenta avanzar el caso
Then el sistema bloquea la transición
And registra la observación correspondiente
