<?php

use App\Models\User;
use Inertia\Testing\AssertableInertia as Assert;

test('guests cannot access authenticated customer pages', function (string $uri) {
    $this->get($uri)->assertRedirect(route('login'));
})->with([
    'dashboard' => '/dashboard',
    'settings' => '/profile',
]);

test('authenticated customer pages receive the user required by the app shell', function (
    string $uri,
    string $component,
) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get($uri)
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component($component)
            ->where('auth.user.id', $user->id)
            ->where('auth.user.name', $user->name)
            ->where('auth.user.email', $user->email));
})->with([
    'dashboard' => ['/dashboard', 'Dashboard'],
    'settings' => ['/profile', 'Profile/Edit'],
]);
