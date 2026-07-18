<?php

namespace App\Services\Sgf;

class NormalizadorSgf
{
    /**
     * @param  array<string, mixed>  $filaSgf
     * @return array<string, mixed>
     */
    public function normalizar(array $filaSgf): array
    {
        return [
            'sgf_id' => trim((string) ($filaSgf['sgf_id'] ?? '')),
            'estado' => trim((string) ($filaSgf['estado'] ?? '')),
            'grupo_actual' => trim((string) ($filaSgf['grupo_actual'] ?? '')),
            'observacion' => $this->trimONull($filaSgf['observaciones'] ?? null),
            'rut' => trim((string) ($filaSgf['rut'] ?? '')),
            'monto' => isset($filaSgf['monto']) ? $this->parseNumeroChileno((string) $filaSgf['monto']) : null,
            'periodo' => $this->trimONull($filaSgf['periodo'] ?? null),
            'folio_egreso' => $this->trimONull($filaSgf['folio_egreso'] ?? null),
            'numero' => $this->trimONull($filaSgf['numero'] ?? null),
            'fecha_sii' => $this->trimONull($filaSgf['fecha_sii'] ?? null),
            'observacion_egreso' => $this->trimONull($filaSgf['observacion_egreso'] ?? null),
            'numero_traspaso' => $this->trimONull($filaSgf['numero_traspaso'] ?? null),
        ];
    }

    /**
     * Trimea un valor de SGF y lo deja en `null` tanto si viene ausente como
     * si viene como string vacío (SGF no distingue "sin dato" de "" en columnas
     * como folio de egreso para casos aún pendientes).
     */
    private function trimONull(mixed $valor): ?string
    {
        if ($valor === null) {
            return null;
        }

        $texto = trim((string) $valor);

        return $texto === '' ? null : $texto;
    }

    private function parseNumeroChileno(string $valor): float
    {
        $limpio = preg_replace('/[^0-9.,\-]/', '', $valor) ?? '';
        $normalizado = str_replace(['.', ','], ['', '.'], $limpio);

        return (float) $normalizado;
    }
}
