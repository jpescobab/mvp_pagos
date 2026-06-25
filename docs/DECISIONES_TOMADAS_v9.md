# Decisiones tomadas v9

1. El sistema será una plataforma de gestión, no reemplazo de sistemas oficiales.
2. Stack: Laravel 13, PostgreSQL, React, Laravel Boost, OpenSpec.
3. Core no desactivable y módulos funcionales activables.
4. Estructura CAPJ: CAPJ -> Jurisdicciones -> Centros financieros -> Centros de costos.
5. Tablas maestras usan `id` interno y código institucional `unique`.
6. `jurisdicciones.codigo` debe permitir default `14`.
7. SGF es fuente de origen; sus estados/grupos no gobiernan el workflow interno.
8. Todo dato/documento SGF relevante queda como snapshot.
9. Primer módulo funcional: Pago de Proveedores.
10. Un `sgf_id` equivale a un caso de pago y a un proceso workflow individual.
11. No se modelan envíos/lotes iniciales SGF.
12. Indicadores económicos se importan desde CMF/SII u otra fuente oficial configurada.
13. Día 10: UF, UTM, UTA, IPC. Diario: USD.
14. Informes razonados se generan desde cortes y snapshots, con aprobación humana.
15. Playwright solo se usa si no existe API suficiente y existe autorización.
