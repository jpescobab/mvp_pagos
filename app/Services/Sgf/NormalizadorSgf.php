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
            'observaciones' => isset($filaSgf['observaciones']) ? trim((string) $filaSgf['observaciones']) : null,
            'rut' => trim((string) ($filaSgf['rut'] ?? '')),
            'monto' => isset($filaSgf['monto']) ? $this->parseNumeroChileno((string) $filaSgf['monto']) : null,
        ];
    }

    private function parseNumeroChileno(string $valor): float
    {
        $normalizado = str_replace(['.', ','], ['', '.'], $valor);

        return (float) $normalizado;
    }
}
