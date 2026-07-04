<?php

test('la raíz del sitio redirige al login', function () {
    $response = $this->get(route('home'));

    $response->assertRedirect(route('login'));
});
