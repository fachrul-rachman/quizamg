<?php

use App\Models\User;

use function Pest\Laravel\get;

test('guests are redirected to the login page for admin dashboard', function () {
    $response = get('/admin/dashboard');
    $response->assertRedirect(route('login'));
});

test('authenticated users can visit the admin dashboard', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/dashboard');
    $response->assertOk();
});
