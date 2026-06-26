<?php

use App\Models\Funcionario;
use Illuminate\Database\QueryException;

test('un funcionario puede registrarse sin user_id, ccosto_id ni cfinanciero_id', function () {
    $funcionario = Funcionario::create(['rut' => '12345678-9', 'nombre' => 'Funcionario de Prueba']);

    expect($funcionario->user_id)->toBeNull();
    expect($funcionario->ccosto_id)->toBeNull();
    expect($funcionario->cfinanciero_id)->toBeNull();
    expect($funcionario->refresh()->activo)->toBeTrue();
});

test('el rut del funcionario es único', function () {
    Funcionario::create(['rut' => '12345678-9', 'nombre' => 'Uno']);
    Funcionario::create(['rut' => '12345678-9', 'nombre' => 'Otro']);
})->throws(QueryException::class);
