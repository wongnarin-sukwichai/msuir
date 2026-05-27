<?php

namespace Database\Seeders;

use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();
        User::factory()->create([
            'name' => 'Wongnarin Sukwichai',
            'email' => 'wongnarin.s@msu.ac.th',
            'password' => null,
            'role_level' => 3
        ]);

        User::factory()->create([
            'name' => 'Chaiwat',
            'email' => 'chavarit.w@msu.ac.th',
            'password' => null,
            'role_level' => 2
        ]);

        User::factory()->create([
            'name' => 'นิสิตมมส.',
            'email' => 'library@msu.ac.th',
            'password' => null,
            'role_level' => 1
        ]);

        User::factory()->create([
            'name' => 'นิสิตต่างสถาบัน',
            'email' => 'libmsu02@gmail.com',
            'password' => Hash::make('1234'),
            'role_level' => 1
        ]);
    }
}
