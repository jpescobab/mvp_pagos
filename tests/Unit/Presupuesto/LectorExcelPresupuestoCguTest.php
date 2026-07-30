<?php

use App\Services\Presupuesto\LectorExcelPresupuestoCgu;
use PhpOffice\PhpSpreadsheet\Cell\DataType;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @param  list<string>  $encabezados
 * @param  list<list<mixed>>  $filas  el monto (última columna) siempre numérico,
 *                                    igual que el Excel real de CGU (celda "n",
 *                                    formato `#,##0`, no texto con puntos).
 */
function crearExcelPresupuestoDePrueba(array $encabezados, array $filas): string
{
    $spreadsheet = new Spreadsheet;
    $hoja = $spreadsheet->getActiveSheet();
    $hoja->fromArray($encabezados, null, 'A1');
    $hoja->fromArray($filas, null, 'A2');

    foreach ($filas as $indice => $fila) {
        $hoja->getStyle('W'.(2 + $indice))->getNumberFormat()->setFormatCode('#,##0');
    }

    $ruta = tempnam(sys_get_temp_dir(), 'presupuesto_cgu_').'.xlsx';
    (new Xlsx($spreadsheet))->save($ruta);

    return $ruta;
}

function encabezadosPresupuestoCguDePrueba(): array
{
    return [
        'Nro.Versión', 'Catalogo', 'Descripción', 'P.Pptario.', 'U.Ejecutora',
        'PROG', 'SUBPR', 'ACTIV', 'TAREA',
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
        'Total Proyectado', 'Ppto.Vigente',
    ];
}

test('parsea filas reales con el monto como celda numérica nativa (formato #,##0 de CGU)', function () {
    $ruta = crearExcelPresupuestoDePrueba(
        encabezadosPresupuestoCguDePrueba(),
        [
            ['5', '2203002000', 'Para Maquinarias', '22', '1400', 'PE', 'GV', '01', '01', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 2095473],
        ],
    );

    $filas = (new LectorExcelPresupuestoCgu)->leer($ruta);

    expect($filas)->toHaveCount(1);
    expect($filas[0]['nro_version'])->toBe('5');
    expect($filas[0]['catalogo_codigo'])->toBe('2203002000');
    expect($filas[0]['cfinanciero_codigo'])->toBe('1400');
    expect($filas[0]['plan_tarea_codigo'])->toBe('PEGV0101');
    expect($filas[0]['monto_asignado'])->toEqual(2095473.0);

    unlink($ruta);
});

test('rechaza un excel con encabezados que no calzan', function () {
    $encabezados = encabezadosPresupuestoCguDePrueba();
    $encabezados[1] = 'Cuenta'; // en vez de "Catalogo"

    $ruta = crearExcelPresupuestoDePrueba($encabezados, []);

    expect(fn () => (new LectorExcelPresupuestoCgu)->leer($ruta))
        ->toThrow(RuntimeException::class);

    unlink($ruta);
});

test('parsea un monto de respaldo llegado como texto con puntos de miles', function () {
    $spreadsheet = new Spreadsheet;
    $hoja = $spreadsheet->getActiveSheet();
    $hoja->fromArray(encabezadosPresupuestoCguDePrueba(), null, 'A1');
    $hoja->fromArray([['5', '2203002000', 'Para Maquinarias', '22', '1400', 'PE', 'GV', '01', '01', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0]], null, 'A2');
    $hoja->setCellValueExplicit('W2', '124.085', DataType::TYPE_STRING);

    $ruta = tempnam(sys_get_temp_dir(), 'presupuesto_cgu_').'.xlsx';
    (new Xlsx($spreadsheet))->save($ruta);

    $filas = (new LectorExcelPresupuestoCgu)->leer($ruta);

    expect($filas[0]['monto_asignado'])->toEqual(124085.0);

    unlink($ruta);
});
