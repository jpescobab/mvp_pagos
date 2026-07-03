<?php

use App\Models\IndicadorEconomico;
use App\Models\IndicadorEconomicoImportacion;
use App\Models\User;
use Illuminate\Support\Facades\RateLimiter;
use Inertia\Testing\AssertableInertia as Assert;
use Laravel\Fortify\Features;

test('login screen can be rendered', function () {
    $response = $this->get(route('login'));

    $response->assertOk();
});

test('login screen incluye el ultimo valor por tipo de indicador cuando hay datos', function () {
    $this->withoutVite();
    $importacion = IndicadorEconomicoImportacion::create(['tipo' => 'diario', 'estado' => 'ok']);

    IndicadorEconomico::create([
        'importacion_id' => $importacion->id,
        'tipo' => 'UF',
        'periodicidad_valor' => 'diaria',
        'fuente' => 'CMF',
        'fecha_valor' => '2026-06-10',
        'valor' => 40765.97,
    ]);
    IndicadorEconomico::create([
        'importacion_id' => $importacion->id,
        'tipo' => 'UF',
        'periodicidad_valor' => 'diaria',
        'fuente' => 'CMF',
        'fecha_valor' => '2026-06-11',
        'valor' => 40800.12,
    ]);

    $response = $this->get(route('login'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/login')
        ->has('indicadores', 1)
        ->where('indicadores.0.tipo', 'UF')
        ->where('indicadores.0.valor', '40800.1200')
    );
});

test('login screen incluye un arreglo vacio de indicadores sin datos', function () {
    $this->withoutVite();

    $response = $this->get(route('login'));

    $response->assertInertia(fn (Assert $page) => $page
        ->component('auth/login')
        ->has('indicadores', 0)
    );
});

test('users can authenticate using the login screen', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));
});

test('users with two factor enabled are redirected to two factor challenge', function () {
    $this->skipUnlessFortifyHas(Features::twoFactorAuthentication());

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->withTwoFactor()->create();

    $response = $this->post(route('login'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $response->assertSessionHas('login.id', $user->id);
    $this->assertGuest();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});

test('users are rate limited', function () {
    $user = User::factory()->create();

    RateLimiter::increment(md5('login'.implode('|', [$user->email, '127.0.0.1'])), amount: 5);

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertTooManyRequests();
});
