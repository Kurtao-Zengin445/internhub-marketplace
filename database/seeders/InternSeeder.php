<?php

namespace Database\Seeders;

use App\Models\Intern;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class InternSeeder extends Seeder
{
    public function run(): void
    {
        $interns = [
            [
                'user' => ['name' => 'Budi Prasetyo',    'email' => 'intern@intern.test'],
                'intern' => ['nisn' => '1020304001', 'nim' => null, 'institution' => 'SMKN 1 Jakarta', 'education_level' => 'SMK', 'major' => 'Rekayasa Perangkat Lunak',    'gender' => 'male',   'date_of_birth' => '2006-03-15', 'phone' => '081311111111'],
            ],
            [
                'user' => ['name' => 'Sari Dewi Lestari', 'email' => 'sari.dewi@intern.test'],
                'intern' => ['nisn' => '1020304002', 'nim' => null, 'institution' => 'SMKN 1 Jakarta', 'education_level' => 'SMK', 'major' => 'Rekayasa Perangkat Lunak',    'gender' => 'female', 'date_of_birth' => '2006-07-22', 'phone' => '081322222222'],
            ],
            [
                'user' => ['name' => 'Riko Firmansyah',   'email' => 'riko.f@intern.test'],
                'intern' => ['nisn' => '1020304003', 'nim' => null, 'institution' => 'SMKN 1 Jakarta', 'education_level' => 'SMK', 'major' => 'Rekayasa Perangkat Lunak',    'gender' => 'male',   'date_of_birth' => '2006-11-08', 'phone' => '081333333333'],
            ],
            [
                'user' => ['name' => 'Nisa Auliandari',   'email' => 'nisa.a@intern.test'],
                'intern' => ['nisn' => '1020304004', 'nim' => null, 'institution' => 'SMKN 1 Jakarta', 'education_level' => 'SMK', 'major' => 'Teknik Komputer & Jaringan', 'gender' => 'female', 'date_of_birth' => '2006-05-19', 'phone' => '081344444444'],
            ],
            [
                'user' => ['name' => 'Dimas Arya Putra',  'email' => 'dimas.a@intern.test'],
                'intern' => ['nisn' => null, 'nim' => '2101001', 'institution' => 'Politeknik Surabaya', 'education_level' => 'D3', 'major' => 'Akuntansi & Keuangan Lembaga', 'gender' => 'male',   'date_of_birth' => '2006-01-30', 'phone' => '081355555555'],
            ],
            [
                'user' => ['name' => 'Fitri Wahyuni',     'email' => 'fitri.w@intern.test'],
                'intern' => ['nisn' => null, 'nim' => '2101002', 'institution' => 'Politeknik Surabaya', 'education_level' => 'D3', 'major' => 'Akuntansi & Keuangan Lembaga', 'gender' => 'female', 'date_of_birth' => '2006-09-14', 'phone' => '081366666666'],
            ],
            [
                'user' => ['name' => 'Galih Setiawan',    'email' => 'galih.s@intern.test'],
                'intern' => ['nisn' => '1020305001', 'nim' => null, 'institution' => 'SMK Muhammadiyah Bandung', 'education_level' => 'SMK', 'major' => 'Multimedia', 'gender' => 'male',   'date_of_birth' => '2006-06-25', 'phone' => '081377777777'],
            ],
            [
                'user' => ['name' => 'Hana Pertiwi',      'email' => 'hana.p@intern.test'],
                'intern' => ['nisn' => '1020305002', 'nim' => null, 'institution' => 'SMK Muhammadiyah Bandung', 'education_level' => 'SMK', 'major' => 'Multimedia', 'gender' => 'female', 'date_of_birth' => '2006-04-11', 'phone' => '081388888888'],
            ],
        ];

        foreach ($interns as $data) {
            $user = User::create([
                'name'      => $data['user']['name'],
                'email'     => $data['user']['email'],
                'password'  => Hash::make('Password1'),
                'role'      => 'intern',
                'is_active' => true,
            ]);

            Intern::create(array_merge($data['intern'], ['user_id' => $user->id]));
        }

        $this->command->info('  InternSeeder: ' . count($interns) . ' peserta magang dibuat.');
    }
}
