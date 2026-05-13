<?php

use App\Models\User;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

test('login screen can be rendered', function () {
    get('/admin/login')
        ->assertOk()
        ->assertSee('Login Admin');
});

test('users can authenticate using the admin login screen', function () {
    $user = User::factory()->create();

    $response = post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect('/admin/dashboard');

    $this->assertAuthenticated();
});

test('users can not authenticate with invalid password', function () {
    $user = User::factory()->create();

    $response = post('/admin/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHas('error', 'Login gagal.');

    $this->assertGuest();
});

test('users can logout', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect(route('home'));

    $this->assertGuest();
});
