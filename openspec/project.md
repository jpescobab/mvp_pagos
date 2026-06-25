# OpenSpec — CAPJ App Pagos / Plataforma de Gestión Institucional

## Objetivo

Construir una plataforma institucional para gestión, workflow, trazabilidad, auditoría, expediente documental variable, indicadores económicos, integraciones externas, reportabilidad e informes razonados.

## Alcance inicial

El primer módulo funcional es Pago de Proveedores, construido sobre un core institucional no desactivable.

## Jerarquía institucional

```txt
CAPJ -> Jurisdicciones -> Centros financieros -> Centros de costos
```

## Stack

- Laravel 13
- PostgreSQL
- React
- Laravel Boost
- Spatie Laravel Permission
- Laravel Queue / Scheduler / Process
- OpenSpec
- Playwright autorizado cuando corresponda

## Sistemas externos

- SGF: fuente de origen de casos de pago. Sus estados y grupos no gobiernan el workflow interno.
- CMF/SII: fuentes oficiales para indicadores económicos.
- CGU/BancoEstado: sistemas oficiales externos; el sistema registra referencias, evidencias y trazabilidad.
- Mercado Público/SII/RUT u otros: integraciones mediante capa transversal.

## Módulos funcionales previstos

- Pago de Proveedores
- Presupuesto
- Adquisiciones
- Mantenimiento
- Recursos Humanos
- Servicios contratados
- Consumo eléctrico
- Informes razonados de gestión
