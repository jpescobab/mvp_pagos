<?php

namespace App\Services\Cmf;

use Illuminate\Support\Facades\Http;

class CmfClient
{
    /**
     * Fetch the latest published "dólar observado" value.
     *
     * @return array{url: string, raw: array<string, mixed>, data: list<array{fecha: string, valor: float}>}
     */
    public function dolar(): array
    {
        return $this->fetch('dolar', 'Dolares');
    }

    /**
     * Fetch the daily UF values for a given calendar month.
     *
     * @return array{url: string, raw: array<string, mixed>, data: list<array{fecha: string, valor: float}>}
     */
    public function uf(int $anio, int $mes): array
    {
        return $this->fetch("uf/{$anio}/{$mes}", 'UFs');
    }

    /**
     * Fetch the monthly UTM values for a given year.
     *
     * @return array{url: string, raw: array<string, mixed>, data: list<array{fecha: string, valor: float}>}
     */
    public function utm(int $anio): array
    {
        return $this->fetch("utm/{$anio}", 'UTMs');
    }

    /**
     * Fetch the monthly IPC values.
     *
     * @return array{url: string, raw: array<string, mixed>, data: list<array{fecha: string, valor: float}>}
     */
    public function ipc(): array
    {
        return $this->fetch('ipc', 'IPCs');
    }

    /**
     * @return array{url: string, raw: array<string, mixed>, data: list<array{fecha: string, valor: float}>}
     */
    private function fetch(string $path, string $collectionKey): array
    {
        $baseUrl = rtrim((string) config('services.cmf.base_url'), '/');
        $url = "{$baseUrl}/{$path}";

        $response = Http::get($url, [
            'apikey' => config('services.cmf.api_key'),
            'formato' => 'json',
        ])->throw();

        /** @var array<string, mixed> $raw */
        $raw = $response->json() ?? [];

        $filas = $raw[$collectionKey] ?? [];
        $filas = is_array($filas) ? array_values($filas) : [];

        $data = [];

        foreach ($filas as $fila) {
            $data[] = [
                'fecha' => (string) $fila['Fecha'],
                'valor' => self::parseNumeroChileno((string) $fila['Valor']),
            ];
        }

        return ['url' => $url, 'raw' => $raw, 'data' => $data];
    }

    /**
     * Parse a CMF-formatted number ("40.809,44") into a float (40809.44).
     */
    public static function parseNumeroChileno(string $valor): float
    {
        $normalizado = str_replace(['.', ','], ['', '.'], $valor);

        return (float) $normalizado;
    }
}
