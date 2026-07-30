<?php

namespace App\Services\Presupuesto;

use PhpOffice\PhpSpreadsheet\IOFactory;
use RuntimeException;

class LectorExcelPresupuestoCgu
{
    /**
     * @var list<string>
     */
    private const ENCABEZADOS_ESPERADOS = [
        'Nro.Versión',
        'Catalogo',
        'Descripción',
        'P.Pptario.',
        'U.Ejecutora',
        'PROG',
        'SUBPR',
        'ACTIV',
        'TAREA',
        'Enero',
        'Febrero',
        'Marzo',
        'Abril',
        'Mayo',
        'Junio',
        'Julio',
        'Agosto',
        'Septiembre',
        'Octubre',
        'Noviembre',
        'Diciembre',
        'Total Proyectado',
        'Ppto.Vigente',
    ];

    /**
     * @return list<array{nro_version: string, catalogo_codigo: string, cfinanciero_codigo: string, plan_tarea_codigo: string, monto_asignado: float}>
     */
    public function leer(string $rutaArchivo): array
    {
        $spreadsheet = IOFactory::load($rutaArchivo);
        $hoja = $spreadsheet->getActiveSheet();
        $filas = $hoja->toArray(null, true, true, false);

        if ($filas === []) {
            throw new RuntimeException('El archivo Excel no contiene filas.');
        }

        $encabezados = array_map(
            static fn (mixed $valor): string => trim((string) $valor),
            array_shift($filas),
        );

        foreach (self::ENCABEZADOS_ESPERADOS as $indice => $encabezadoEsperado) {
            $encabezadoEncontrado = $encabezados[$indice] ?? null;

            if ($encabezadoEncontrado !== $encabezadoEsperado) {
                throw new RuntimeException(
                    "Columna inesperada en la posición {$indice}: se esperaba \"{$encabezadoEsperado}\" y se encontró \"{$encabezadoEncontrado}\".",
                );
            }
        }

        $filasNormalizadas = [];

        foreach ($filas as $fila) {
            if ($this->filaVacia($fila)) {
                continue;
            }

            $filasNormalizadas[] = [
                'nro_version' => trim((string) $fila[0]),
                'catalogo_codigo' => trim((string) $fila[1]),
                'cfinanciero_codigo' => trim((string) $fila[4]),
                'plan_tarea_codigo' => trim((string) $fila[5]).trim((string) $fila[6]).trim((string) $fila[7]).trim((string) $fila[8]),
                'monto_asignado' => $this->parsearMonto((string) $fila[22]),
            ];
        }

        return $filasNormalizadas;
    }

    /**
     * @param  array<int, mixed>  $fila
     */
    private function filaVacia(array $fila): bool
    {
        return trim((string) ($fila[1] ?? '')) === '';
    }

    private function parsearMonto(string $valor): float
    {
        $normalizado = str_replace('.', '', trim($valor));
        $normalizado = str_replace(',', '.', $normalizado);

        return (float) $normalizado;
    }
}
