<?php

use App\Services\Sgf\ReparadorNombreArchivoSgf;

dataset('nombres_con_mojibake', [
    'grado' => ['CT NÂ°957_433 CONST.EHG.pdf', 'CT N°957_433 CONST.EHG.pdf'],
    'e con tilde' => ['pasaje_aÃ©reo (8).pdf', 'pasaje_aéreo (8).pdf'],
    'a con tilde' => ['SOLICITUD_Mabel_CÃ¡rdenas.pdf', 'SOLICITUD_Mabel_Cárdenas.pdf'],
    'enie' => ["PROYECCIÃ\u{0091}N.pdf", 'PROYECCIÑN.pdf'],
    'vocal con tilde minuscula' => ["GarantÃ\u{00AD}a.pdf", 'Garantía.pdf'],
]);

dataset('nombres_intactos', [
    'ascii' => ['Factura 433.pdf'],
    'tilde legitima' => ['Garantía.pdf'],
    'enie legitima' => ['Cañería.pdf'],
    'grado legitimo' => ['N°123.pdf'],
    'vacio' => [''],
    'caracter fuera de latin1' => ['Resumen – final.pdf'],
    'degradado a signo de interrogacion (E con tilde)' => ['PASAJES_AÃ¿REOS.pdf'],
    'degradado a signo de interrogacion (enie)' => ['C.JAÃ¿A.pdf'],
    'degradado junto a otro mojibake reparable' => ['AÃ¿REOS_CÃ¡rdenas.pdf'],
]);

test('repara nombres con mojibake', function (string $corrupto, string $esperado) {
    expect(app(ReparadorNombreArchivoSgf::class)->reparar($corrupto))->toBe($esperado);
})->with('nombres_con_mojibake');

test('conserva intactos los nombres sin mojibake', function (string $nombre) {
    expect(app(ReparadorNombreArchivoSgf::class)->reparar($nombre))->toBe($nombre);
})->with('nombres_intactos');

test('es idempotente: reparar un nombre ya reparado no lo cambia', function () {
    $reparador = app(ReparadorNombreArchivoSgf::class);

    $una = $reparador->reparar('CT NÂ°957_433.pdf');

    expect($reparador->reparar($una))->toBe($una);
});

test('no repara cuando la re-decodificacion no produce UTF-8 valido', function () {
    expect(app(ReparadorNombreArchivoSgf::class)->reparar('Ã solo.pdf'))->toBe('Ã solo.pdf');
});

test('repara la ruta completa conservando directorios', function () {
    expect(app(ReparadorNombreArchivoSgf::class)->repararRuta('sgf-documentos/1032/CT NÂ°957.pdf'))
        ->toBe('sgf-documentos/1032/CT N°957.pdf');
});
