<?php

use App\Models\User;

test('un login exitoso actualiza last_login_at', function () {
    $usuario = User::factory()->create(['password' => bcrypt('password')]);

    expect($usuario->last_login_at)->toBeNull();

    $this->post(route('login.store'), [
        'email' => $usuario->email,
        'password' => 'password',
    ]);

    expect($usuario->refresh()->last_login_at)->not->toBeNull();
});
