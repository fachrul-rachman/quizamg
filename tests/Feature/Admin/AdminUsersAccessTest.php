<?php

use App\Models\User;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\delete;
use function Pest\Laravel\get;
use function Pest\Laravel\put;

it('forbids regular admin from accessing admin users page', function () {
    $user = User::factory()->create(['role' => 'admin']);

    actingAs($user)
        ->get('/admin/users')
        ->assertForbidden();
});

it('allows super admin to access admin users page', function () {
    $user = User::factory()->create(['role' => 'super_admin']);

    actingAs($user)
        ->get('/admin/users')
        ->assertOk()
        ->assertSee('Admin Users');
});

it('prevents super admin from deactivating own account', function () {
    $user = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

    actingAs($user)
        ->from('/admin/users/'.$user->id.'/edit')
        ->put('/admin/users/'.$user->id, [
            'name' => $user->name,
            'email' => $user->email,
            'role' => 'super_admin',
            'is_active' => '0',
        ])
        ->assertSessionHas('error', 'Anda tidak bisa menonaktifkan akun sendiri.');

    expect($user->fresh()->is_active)->toBeTrue();
});

it('prevents removing the last active super admin', function () {
    $superAdmin = User::factory()->create(['role' => 'super_admin', 'is_active' => true]);

    actingAs($superAdmin)
        ->from('/admin/users/'.$superAdmin->id.'/edit')
        ->put('/admin/users/'.$superAdmin->id, [
            'name' => $superAdmin->name,
            'email' => $superAdmin->email,
            'role' => 'admin',
            'is_active' => '1',
        ])
        ->assertSessionHas('error', 'Anda tidak bisa menghapus role super admin dari akun sendiri.');

    expect($superAdmin->fresh()->role)->toBe('super_admin');

    actingAs($superAdmin)
        ->delete('/admin/users/'.$superAdmin->id)
        ->assertSessionHas('error', 'Anda tidak bisa menghapus akun sendiri.');
});
