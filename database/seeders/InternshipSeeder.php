<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Internship;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Database\Seeder;

class InternshipSeeder extends Seeder
{
    public function run(): void
    {
        $agus    = Supervisor::whereHas('user', fn($q) => $q->where('email', 'agus.supervisor@intern.test'))->first();
        $rina    = Supervisor::whereHas('user', fn($q) => $q->where('email', 'rina.supervisor@intern.test'))->first();
        $bambang = Supervisor::whereHas('user', fn($q) => $q->where('email', 'bambang.supervisor@intern.test'))->first();

        // Pembimbing dari perusahaan (user dengan role company)
        $techcorpUser  = User::where('email', 'techcorp@intern.test')->first();
        $financeUser   = User::where('email', 'majubersama@intern.test')->first();

        // Ambil lamaran yang accepted
        $acceptedApps = Application::where('status', 'accepted')
            ->with('program.company.user', 'user.intern')
            ->get();

        foreach ($acceptedApps as $application) {
            $companyEmail = $application->program->company->user->email;

            $intern = $application->user?->intern;
            $major = $intern?->major;

            $supervisor = match (true) {
                str_contains((string) $major, 'Jaringan') => $rina,
                str_contains((string) $major, 'Akuntansi') => $bambang,
                default => $agus,
            };

            // Pembimbing perusahaan
            $companySupervisorId = match ($companyEmail) {
                'techcorp@intern.test'       => $techcorpUser->id,
                'majubersama@intern.test'    => $financeUser->id,
                default                      => null,
            };

            Internship::create([
                'application_id'        => $application->id,
                'supervisor_id'         => $supervisor?->id,
                'company_supervisor_id' => $companySupervisorId,
                'start_date'            => $application->program->start_date,
                'end_date'              => $application->program->end_date,
                'status'                => 'completed',
            ]);
        }

        $count = $acceptedApps->count();
        $this->command->info("  InternshipSeeder: {$count} data magang dibuat.");
    }
}
