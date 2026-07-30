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
        // formatData=false: se leen los valores crudos, no el texto formateado.
        // El Excel real de CGU guarda "Ppto.Vigente" como celda numérica nativa
        // con formato de miles `#,##0` — PhpSpreadsheet renderiza ese formato
        // siempre con coma de miles/punto decimal (estándar OOXML), sin adaptarlo
        // al separador chileno con el que Excel lo muestra en pantalla. Parsear
        // el texto formateado asumiendo "punto = miles" trunca el monto real.
        $filas = $hoja->toArray(null, true, false, false);

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
                'monto_asignado' => $this->parsearMonto($fila[22]),
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

    /**
     * Fallback defensivo por si la celda llegara como texto en vez de número
     * nativo (el caso real de CGU es numérico y se resuelve arriba sin pasar
     * por aquí). Un separador que se repite más de una vez solo puede ser
     * agrupador de miles — un decimal real no puede aparecer dos veces.
     */
    private function parsearMonto(mixed $valor): float
    {
        if (is_int($valor) || is_float($valor)) {
            return (float) $valor;
        }

        $texto = trim((string) $valor);

        if ($texto === '') {
            return 0.0;
        }

        if (substr_count($texto, ',') > 1) {
            $texto = str_replace(',', '', $texto);
        }

        if (substr_count($texto, '.') > 1) {
            $texto = str_replace('.', '', $texto);
        }

        $comas = substr_count($texto, ',');
        $puntos = substr_count($texto, '.');

        if ($comas === 1 && $puntos === 1) {
            // Quedan ambos: el que aparece más a la derecha es el decimal.
            if (strrpos($texto, ',') > strrpos($texto, '.')) {
                $texto = str_replace('.', '', $texto);
                $texto = str_replace(',', '.', $texto);
            } else {
                $texto = str_replace(',', '', $texto);
            }
        } elseif ($comas === 1) {
            $texto = str_replace(',', '.', $texto);
        } elseif ($puntos === 1 && preg_match('/\.\d{3}$/', $texto) === 1) {
            // Único punto seguido de exactamente 3 dígitos: monto CLP entero
            // con separador de miles (ej. "124.085" = 124085), no un decimal.
            $texto = str_replace('.', '', $texto);
        }

        return (float) $texto;
    }
}
