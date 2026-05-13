<?php

use App\Models\User;

use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('renders the admin login screen', function () {
    get('/admin/login')
        ->assertOk()
        ->assertSee('Login Admin');
});

it('rejects inactive admin login', function () {
    $user = User::factory()->create([
        'email' => 'inactive@example.com',
        'is_active' => false,
    ]);

    post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHas('error', 'Akun tidak aktif.');

    $this->assertGuest();
});

it('rejects soft deleted admin login', function () {
    $user = User::factory()->create([
        'email' => 'deleted@example.com',
    ]);
    $user->delete();

    post('/admin/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertSessionHas('error', 'Akun tidak aktif.');

    $this->assertGuest();
});
