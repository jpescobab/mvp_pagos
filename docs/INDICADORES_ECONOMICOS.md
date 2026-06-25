# Indicadores económicos

## Objetivo

Guardar en base de datos los indicadores económicos usados para cálculos, reportes, minutas, cortes e informes razonados.

## Fuentes

- CMF Chile como fuente principal cuando exista endpoint disponible.
- SII u otra fuente oficial configurada cuando corresponda.
- Carga manual solo como excepción controlada y auditada.

## Jobs

### Mensual, día 10

Importa:

- UF
- UTM
- UTA
- IPC

### Diario

Importa:

- USD / dólar observado

## Reglas

- UF tiene valores diarios por tramo mensual.
- UTM, UTA e IPC son mensuales.
- USD es diario y puede no estar disponible todos los días.
- Todo valor debe vincularse a una importación.
- Todo valor debe conservar payload de origen y hash.
