<?php

use App\Models\Presupuesto\ImportacionPresupuesto;
use App\Models\Presupuesto\PlanTarea;
use App\Models\Presupuesto\Presupuesto;
use App\Models\SnapshotDatosExterno;
use App\Models\User;
use Database\Seeders\CatalogosSeeder;
use Database\Seeders\CfinancierosSeeder;
use Database\Seeders\IntegracionesSeeder;
use Database\Seeders\ItemsSeeder;
use Database\Seeders\JurisdiccionesSeeder;
use Database\Seeders\PresupuestoSeeder;
use Database\Seeders\TiposDocumentoSeeder;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

/**
 * @param  list<list<mixed>>  $filas  el monto (última columna) siempre numérico,
 *                                    igual que el Excel real de CGU (celda "n",
 *                                    formato `#,##0`, no texto con puntos).
 */
function crearArchivoExcelPresupuestoDePrueba(array $filas): UploadedFile
{
    $encabezados = [
        'Nro.Versión', 'Catalogo', 'Descripción', 'P.Pptario.', 'U.Ejecutora',
        'PROG', 'SUBPR', 'ACTIV', 'TAREA',
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
        'Total Proyectado', 'Ppto.Vigente',
    ];

    $spreadsheet = new Spreadsheet;
    $hoja = $spreadsheet->getActiveSheet();
    $hoja->fromArray($encabezados, null, 'A1');
    $hoja->fromArray($filas, null, 'A2');

    foreach ($filas as $indice => $fila) {
        $hoja->getStyle('W'.(2 + $indice))->getNumberFormat()->setFormatCode('#,##0');
    }

    $ruta = tempnam(sys_get_temp_dir(), 'presupuesto_cgu_').'.xlsx';
    (new Xlsx($spreadsheet))->save($ruta);

    return new UploadedFile($ruta, 'presupuesto_cgu.xlsx', null, null, true);
}

function prepararDatosBasePresupuesto(): void
{
    (new JurisdiccionesSeeder)->run();
    (new CfinancierosSeeder)->run();
    (new ItemsSeeder)->run();
    (new CatalogosSeeder)->run();
    (new IntegracionesSeeder)->run();
    (new PresupuestoSeeder)->run();
    (new TiposDocumentoSeeder)->run();
}

function usuarioConPermisoImportar(): User
{
    $usuario = User::factory()->create();
    $usuario->givePermissionTo('presupuesto.importar');

    return $usuario;
}

test('una importación válida crea líneas de presupuesto y snapshot', function () {
    prepararDatosBasePresupuesto();
    $usuario = usuarioConPermisoImportar();

    $archivo = crearArchivoExcelPresupuestoDePrueba([
        ['5', '2203002000', 'Para Maquinarias', '22', '1400', 'PE', 'GV', '01', '01', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 124085],
    ]);

    $response = $this->actingAs($usuario)->post(route('presupuesto.importaciones.store'), [
        'archivo' => $archivo,
        'anio' => 2026,
    ]);

    $response->assertRedirect(route('presupuesto.importaciones.index'));

    expect(ImportacionPresupuesto::count())->toBe(1);
    $importacion = ImportacionPresupuesto::firstOrFail();
    expect($importacion->estado)->toBe('completado');
    expect($importacion->total_creados)->toBe(1);
    expect($importacion->nro_version)->toBe('5');
    expect($importacion->snapshot_datos_externo_id)->not->toBeNull();

    expect(SnapshotDatosExterno::count())->toBe(1);

    $linea = Presupuesto::firstOrFail();
    expect((float) $linea->monto_asignado)->toEqual(124085.0);
    expect($linea->anio)->toBe(2026);
});

test('un excel con formato de columnas inesperado se rechaza sin crear líneas', function () {
    prepararDatosBasePresupuesto();
    $usuario = usuarioConPermisoImportar();

    $encabezados = [
        'Nro.Versión', 'Cuenta', 'Descripción', 'P.Pptario.', 'U.Ejecutora',
        'PROG', 'SUBPR', 'ACTIV', 'TAREA',
        'Enero', 'Febrero', 'Marzo', 'Abril', 'Mayo', 'Junio', 'Julio', 'Agosto', 'Septiembre', 'Octubre', 'Noviembre', 'Diciembre',
        'Total Proyectado', 'Ppto.Vigente',
    ];

    $spreadsheet = new Spreadsheet;
    $spreadsheet->getActiveSheet()->fromArray($encabezados, null, 'A1');
    $ruta = tempnam(sys_get_temp_dir(), 'presupuesto_cgu_').'.xlsx';
    (new Xlsx($spreadsheet))->save($ruta);
    $archivo = new UploadedFile($ruta, 'presupuesto_cgu.xlsx', null, null, true);

    $response = $this->actingAs($usuario)->post(route('presupuesto.importaciones.store'), [
        'archivo' => $archivo,
        'anio' => 2026,
    ]);

    $response->assertInertiaFlash('toast');
    expect(ImportacionPresupuesto::count())->toBe(0);
    expect(Presupuesto::count())->toBe(0);
});

test('una fila con cuenta inexistente se omite y queda contada', function () {
    prepararDatosBasePresupuesto();
    $usuario = usuarioConPermisoImportar();

    $archivo = crearArchivoExcelPresupuestoDePrueba([
        ['5', '9999999999', 'Cuenta inexistente', '22', '1400', 'PE', 'GV', '01', '01', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 1000],
    ]);

    $this->actingAs($usuario)->post(route('presupuesto.importaciones.store'), [
        'archivo' => $archivo,
        'anio' => 2026,
    ]);

    $importacion = ImportacionPresupuesto::firstOrFail();
    expect($importacion->total_omitidos)->toBe(1);
    expect($importacion->total_creados)->toBe(0);
    expect(Presupuesto::count())->toBe(0);
});

test('reimportar una versión posterior del mismo año actualiza el monto asignado sin perder historial', function () {
    prepararDatosBasePresupuesto();
    $usuario = usuarioConPermisoImportar();

    $primerArchivo = crearArchivoExcelPresupuestoDePrueba([
        ['5', '2203002000', 'Para Maquinarias', '22', '1400', 'PE', 'GV', '01', '01', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 100000],
    ]);

    $this->actingAs($usuario)->post(route('presupuesto.importaciones.store'), [
        'archivo' => $primerArchivo,
        'anio' => 2026,
    ]);

    $segundoArchivo = crearArchivoExcelPresupuestoDePrueba([
        ['6', '2203002000', 'Para Maquinarias', '22', '1400', 'PE', 'GV', '01', '01', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 150000],
    ]);

    $this->actingAs($usuario)->post(route('presupuesto.importaciones.store'), [
        'archivo' => $segundoArchivo,
        'anio' => 2026,
    ]);

    expect(ImportacionPresupuesto::count())->toBe(2);
    expect(SnapshotDatosExterno::count())->toBe(2);
    expect(Presupuesto::count())->toBe(1);

    $linea = Presupuesto::firstOrFail();
    expect((float) $linea->monto_asignado)->toEqual(150000.0);
});

test('un mismo plan de tarea bajo cuentas presupuestarias distintas no se duplica', function () {
    prepararDatosBasePresupuesto();
    $usuario = usuarioConPermisoImportar();

    $archivo = crearArchivoExcelPresupuestoDePrueba([
        ['5', '2203002000', 'Para Maquinarias', '22', '1400', 'PE', 'GV', '01', '01', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 100000],
        ['5', '2203003000', 'Para Calefacción', '22', '1400', 'PE', 'GV', '01', '01', 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 0, 200000],
    ]);

    $this->actingAs($usuario)->post(route('presupuesto.importaciones.store'), [
        'archivo' => $archivo,
        'anio' => 2026,
    ]);

    expect(PlanTarea::count())->toBe(1);
    expect(Presupuesto::count())->toBe(2);
});
