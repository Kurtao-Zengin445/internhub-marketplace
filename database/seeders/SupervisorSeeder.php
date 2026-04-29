<?php

namespace Database\Seeders;

use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class SupervisorSeeder extends Seeder
{
    public function run(): void
    {
        $supervisors = [
            [
                'user' => [
                    'name'  => 'Agus Widodo',
                    'email' => 'agus.supervisor@intern.test',
                ],
                'supervisor' => [
                    'nip'       => '198501012010011001',
                    'position'  => 'Supervisor Engineering',
                    'phone'     => '081298765432',
                ],
            ],
            [
                'user' => [
                    'name'  => 'Rina Marlina',
                    'email' => 'rina.supervisor@intern.test',
                ],
                'supervisor' => [
                    'nip'       => '197803152005012002',
                    'position'  => 'Supervisor Operations',
                    'phone'     => '082198765433',
                ],
            ],
            [
                'user' => [
                    'name'  => 'Bambang Haryanto',
                    'email' => 'bambang.supervisor@intern.test',
                ],
                'supervisor' => [
                    'nip'       => '198709202012011003',
                    'position'  => 'Supervisor Finance',
                    'phone'     => '083198765434',
                ],
            ],
        ];

        foreach ($supervisors as $data) {
            $user = User::create([
                'name'      => $data['user']['name'],
                'email'     => $data['user']['email'],
                'password'  => Hash::make('Password1'),
                'role'      => 'supervisor',
                'is_active' => true,
            ]);

            Supervisor::create(array_merge($data['supervisor'], ['user_id' => $user->id]));
        }

        $this->command->info('  SupervisorSeeder: ' . count($supervisors) . ' pembimbing dibuat.');
    }
}
