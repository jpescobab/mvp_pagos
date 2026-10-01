## Context

`descargarDocumentosDeFila()` (sgf-scraper.js) toma el nombre de la columna "Nombre" de la tabla de documentos de SGF y lo usa tal cual: como archivo en `storage/app/private/sgf-documentos/{sgfId}/` y como `ruta_archivo`/`nombre_archivo` del payload crudo. `ConectorSgfPlaywrightService::vincularDocumento()` los copia a `documentos.titulo` y `versiones_documento`, y evita duplicados comparando `ruta_archivo` de forma exacta.

Los nombres corruptos son mojibake clásico: bytes UTF-8 interpretados como Latin-1/CP1252 (`°` → `Â°`, `é` → `Ã©`). No se ha determinado si SGF ya sirve el texto corrupto o si lo introduce la lectura del scraper; eso solo se puede verificar con una corrida real contra SGF con credenciales del usuario, que ejecuta él, no el asistente. El diseño no depende de la respuesta: repara en ambos lados.

## Goals / Non-Goals

**Goals:**
- Nombre, ruta y archivo físico correctos y consistentes en importaciones nuevas.
- Reimportaciones idempotentes aun si hay registros previos con el nombre corrupto.
- Reparar los 6 registros existentes y sus archivos, sin tocar vínculos, hashes ni snapshots.

**Non-Goals:**
- No se cambia el mecanismo de descarga (ya corregido).
- No se altera el payload crudo del snapshot (evidencia).
- No se renombran archivos "sueltos" no registrados como `Documento`.

## Decisions

**1. Heurística de reparación única y conservadora.** Un nombre se considera mojibake si contiene `Ã` o `Â` y, al re-codificarlo a bytes Latin-1 y decodificarlo como UTF-8, resulta UTF-8 válido y distinto. Si no, se devuelve intacto. Alternativa descartada: librería externa de detección (dependencia nueva sin aprobación; la heurística cubre el caso real). Se implementa dos veces con el mismo contrato: JS en el scraper y PHP en `ReparadorNombreArchivoSgf`.

**2. Repara en el scraper Y en el backend.** El scraper corrige antes de escribir en disco, así archivo, `ruta_archivo` y payload nacen consistentes. El backend repara además en `vincularDocumento()` como defensa para payloads antiguos. Si el backend repara un nombre cuyo archivo físico sigue con el nombre corrupto, mueve el archivo al nombre reparado antes de registrar. Alternativa descartada: solo backend (deja al scraper escribiendo archivos con nombre corrupto que luego hay que mover siempre).

**3. Idempotencia por ambas rutas.** `yaVinculado` busca una versión cuya `ruta_archivo` sea la ruta cruda O la reparada. Así reimportar un caso con registro viejo corrupto no crea un duplicado.

**4. Reparación de datos existentes con comando Artisan, no migración.** Toca archivos físicos, no solo filas; debe poder simularse. `--dry-run` lista cambios sin aplicar. Cada registro se procesa en transacción; el archivo se renombra solo si existe y el destino no existe (si el destino existe, se reporta conflicto y se omite). Es idempotente: sin mojibake no hace nada. Registra auditoría con `AuditLogger::log` (antes/después) por documento.

**5. Bytes ya degradados: no se adivinan.** Descubierto con el `--dry-run` sobre datos reales: `AÃ¿REOS` (original `AÉREOS`) y `JAÃ¿A` (original `JAÑA`) llegaron con el byte de continuación (0x89/0x91) ya degradado a `¿`. El roundtrip produciría `ÿ`, un carácter válido pero equivocado. Por eso `Ã¿`/`Â¿` se tratan como irreparables: el nombre se deja intacto y el comando los lista aparte para corrección humana. Hoy afecta a 3 de los 6 documentos (ids 241, 251, 252).

## Risks / Trade-offs

- [Falso positivo: un nombre legítimo que contenga `Ã`/`Â`] → la regla exige roundtrip UTF-8 válido y distinto; un nombre legítimo rara vez lo cumple. Cubierto por tests de no-regresión.
- [Colisión de nombre al renombrar] → se omite y se reporta, nunca se sobrescribe.
- [Hash o vínculos] → no se tocan; solo `titulo`, `nombre_archivo`, `ruta_archivo` y el archivo.
- [Origen real sin confirmar] → reparar en ambos lados evita depender de ello; confirmar con corrida real queda a cargo del usuario.
