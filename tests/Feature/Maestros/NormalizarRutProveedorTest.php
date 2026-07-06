<?php

use App\Models\Proveedor;

test('normalizarRut quita puntos, agrega guion y deja el digito verificador en mayuscula', function (string $entrada, string $esperado) {
    expect(Proveedor::normalizarRut($entrada))->toBe($esperado);
})->with([
    ['76.123.456-7', '76123456-7'],
    ['76123456-7', '76123456-7'],
    ['76123456K', '76123456-K'],
    ['76.123.456-k', '76123456-K'],
    ['  76.123.456-7  ', '76123456-7'],
]);

test('el atributo rutproveedor se normaliza automaticamente al guardar el modelo', function () {
    $proveedor = Proveedor::create(['rutproveedor' => '89.862.200-2', 'nombre' => 'LATAM AIRLINES GROUP S.A.']);

    expect($proveedor->rutproveedor)->toBe('89862200-2');
    expect($proveedor->refresh()->rutproveedor)->toBe('89862200-2');
});
