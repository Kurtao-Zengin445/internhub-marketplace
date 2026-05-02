<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AdminSeeder::class,
            SupervisorSeeder::class,
            CompanySeeder::class,
            InternSeeder::class,
            InternshipProgramSeeder::class,
            ApplicationSeeder::class,
            InternshipSeeder::class,
            DailyReportSeeder::class,
            AttendanceSeeder::class,
            EvaluationSeeder::class,
            DocumentSeeder::class,
            ActiveInternDemoSeeder::class,
        ]);

        $this->command->info('Database seeding selesai.');
    }
}
