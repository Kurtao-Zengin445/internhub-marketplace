<?php

use App\Models\User;

test('registration screen can be rendered', function () {
    $response = $this->get('/register');

    $response->assertStatus(200);
});

test('new intern users can register', function () {
    $response = $this->post('/register', [
        'role' => 'intern',
        'name' => 'Test User',
        'email' => 'test@example.com',
        'nis' => '1234567890',
        'class' => 'XII RPL 1',
        'major' => 'Rekayasa Perangkat Lunak',
        'password' => 'password',
        'password_confirmation' => 'password',
    ]);

    $this->assertAuthenticated();
    $response->assertRedirect(route('dashboard', absolute: false));

    $user = User::where('email', 'test@example.com')->first();

    expect($user)->not->toBeNull();
    expect($user->role)->toBe('intern');
    expect($user->intern)->not->toBeNull();
    expect($user->intern->nim)->toBe('1234567890');
    expect($user->intern->education_level)->toBe('XII RPL 1');
});
