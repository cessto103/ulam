<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $barangays = [
            'Cogeo', 'Dela Paz', 'San Roque', 'Mayamot', 'Dalig',
            'Mambugan', 'San Jose', 'Inarawan', 'Cupang', 'Sta. Cruz',
            'Calawis', 'San Luis', 'Binoto', 'Bagong Nayon', 'Kutitang',
            'Simona', 'Muntingdilaw',
        ];

        $users = [
            ['name' => 'Maria Santos',      'username' => 'mariasantos'],
            ['name' => 'Juan dela Cruz',    'username' => 'juandelacruz'],
            ['name' => 'Ana Reyes',         'username' => 'anareyes'],
            ['name' => 'Pedro Villanueva',  'username' => 'pedrovillanueva'],
            ['name' => 'Liza Ramos',        'username' => 'lizaramos'],
            ['name' => 'Carlo Mendoza',     'username' => 'carlomendoza'],
            ['name' => 'Grace Tolentino',   'username' => 'gracetolentino'],
            ['name' => 'Ramon Castillo',    'username' => 'ramoncastillo'],
            ['name' => 'Nena Bautista',     'username' => 'nenabautista'],
            ['name' => 'Isko Navarro',      'username' => 'iskonavarro'],
            ['name' => 'Cora Pascual',      'username' => 'corapascual'],
            ['name' => 'Ben Aguilar',       'username' => 'benaguilar'],
            ['name' => 'Josie Aquino',      'username' => 'josieaquino'],
            ['name' => 'Raffy Guevara',     'username' => 'raffyguevara'],
            ['name' => 'Tess Delos Reyes',  'username' => 'tessdreyes'],
            ['name' => 'Boyette Cruz',      'username' => 'boyettecruz'],
            ['name' => 'Sheila Magpantay',  'username' => 'sheilamagpantay'],
        ];

        foreach ($users as $idx => $data) {
            $email = strtolower(str_replace(' ', '.', $data['name'])) . '@example.com';
            $email = preg_replace('/[^a-z0-9.@]/', '', $email);

            if (User::where('email', $email)->orWhere('username', $data['username'])->exists()) {
                continue;
            }

            User::create([
                'name'                 => $data['name'],
                'username'             => $data['username'],
                'email'                => $email,
                'password'             => Hash::make('password123'),
                'barangay'             => $barangays[$idx % count($barangays)],
                'municipality'         => 'Antipolo City',
                'province'             => 'Rizal',
                'region'               => 'IV-A',
                'household_size'       => rand(2, 6),
                'plan'                 => 'libre',
                'onboarding_completed' => true,
                'xp'                   => rand(50, 800),
                'level'                => rand(1, 5),
                'streak_days'          => rand(0, 30),
            ]);
        }

        User::updateOrCreate(
            ['email' => 'test@ulam.local'],
            [
                'name' => 'uLam Test User',
                'username' => 'test_user',
                'password' => Hash::make('password123'),
                'barangay' => 'Cogeo',
                'municipality' => 'Antipolo City',
                'province' => 'Rizal',
                'region' => 'IV-A',
                'household_size' => 4,
                'plan' => 'libre',
                'onboarding_completed' => true,
                'xp' => 100,
                'level' => 1,
                'streak_days' => 0,
            ]
        );

        $this->command->info('UserSeeder done. Total users: ' . User::count());
    }
}
