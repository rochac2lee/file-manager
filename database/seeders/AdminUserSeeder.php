<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::create([
            'name' => 'Administrador',
            'email' => 'admin@expert-seg.com',
            'sector' => 'TI',
            'position' => 'Administrador do Sistema',
            'role' => 'administrador',
            'password' => Hash::make('admin123'),
        ]);
    }
}
