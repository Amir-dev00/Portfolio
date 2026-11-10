<?php

use App\Models\User;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Hash;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('portfolio:create-owner {email}', function (string $email) {
    if (! filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $this->error('Invalid email address.');

        return 1;
    }

    if (User::query()->where('email', $email)->exists()) {
        $this->error('A user with this email already exists. Nothing was changed.');

        return 1;
    }

    $password = (string) $this->secret('Password (min 12 chars)');
    if (strlen($password) < 12) {
        $this->error('Password must be at least 12 characters.');

        return 1;
    }

    (new User)->forceFill([
        'name' => 'Owner',
        'email' => $email,
        'password' => Hash::make($password),
        'is_owner' => true,
    ])->save();

    $this->info("Owner {$email} created. Sign in at /admin/login.");

    return 0;
})->purpose('Create the admin owner account (local use; refuses to overwrite existing users)');
