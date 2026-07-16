<?php

namespace Database\Factories;

use App\Models\Admin;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\Hash;

class AdminFactory extends Factory
{
    protected $model = Admin::class;

    public function definition(): array
    {
        return [
            'name' => 'Test Admin',
            'username' => 'testadmin',
            'email' => 'test@example.com',
            'password' => Hash::make('password'),
            'force_password_change' => false,
        ];
    }
}
