<?php

use App\Services\Cmf\CmfClient;
use Illuminate\Support\Facades\Http;

test('parsea números en formato chileno a float', function () {
    expect(CmfClient::parseNumeroChileno('40.809,44'))->toBe(40809.44);
    expect(CmfClient::parseNumeroChileno('71.506'))->toBe(71506.0);
    expect(CmfClient::parseNumeroChileno('0,2'))->toBe(0.2);
});

test('uf() pide el endpoint correcto y normaliza la respuesta', function () {
    Http::fake([
        '*/uf/2026/6*' => Http::response([
            'UFs' => [
                ['Valor' => '40.765,97', 'Fecha' => '2026-06-10'],
            ],
        ]),
    ]);

    $resultado = app(CmfClient::class)->uf(2026, 6);

    expect($resultado['data'])->toBe([
        ['fecha' => '2026-06-10', 'valor' => 40765.97],
    ]);

    Http::assertSent(fn ($request) => str_contains($request->url(), '/uf/2026/6')
        && $request['apikey'] === config('services.cmf.api_key'));
});
