# Tarea 08 — Pago de Proveedores por sgf_id

Implementar módulo piloto Pago de Proveedores.

Tablas:

- supplier_payment_cases
- invoices
- cgu_accounting_records
- bank_payment_records
- cgu_egresses
- cgu_egress_items

Regla:

- Un `sgf_id` = un caso = un proceso workflow individual.
- No crear payment_submissions ni lotes iniciales.
