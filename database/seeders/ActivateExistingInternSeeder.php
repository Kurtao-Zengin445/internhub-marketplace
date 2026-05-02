<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\DailyReport;
use App\Models\Intern;
use App\Models\Internship;
use App\Models\InternshipProgram;
use App\Models\Supervisor;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class ActivateExistingInternSeeder extends Seeder
{
    public function run(): void
    {
        $internUser = $this->targetInternUser();

        if (!$internUser) {
            $this->command->error('Tidak ada user role intern. Buat akun intern terlebih dahulu, lalu jalankan seeder ini lagi.');
            return;
        }

        $internUser->update([
            'role' => User::ROLE_INTERN,
            'is_active' => true,
            'email_verified_at' => $internUser->email_verified_at ?? now(),
        ]);

        Intern::updateOrCreate(
            ['user_id' => $internUser->id],
            [
                'nisn' => 'ACTIVE-' . str_pad((string) $internUser->id, 6, '0', STR_PAD_LEFT),
                'institution' => 'InternHub Demo',
                'education_level' => 'Umum',
                'major' => $internUser->headline ?: 'Web Development',
                'phone' => '081200000001',
                'address' => 'Jakarta',
                'date_of_birth' => '2004-05-02',
                'gender' => 'male',
            ]
        );

        $password = Hash::make('Password1');

        $companyUser = User::updateOrCreate(
            ['email' => 'demo.company@internhub.test'],
            [
                'name' => 'Demo Company',
                'password' => $password,
                'role' => User::ROLE_COMPANY,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $company = Company::updateOrCreate(
            ['user_id' => $companyUser->id],
            [
                'name' => 'PT Demo Internship Marketplace',
                'address' => 'Jl. Demo InternHub No. 1, Jakarta',
                'phone' => '021-123456',
                'email' => 'hr@demo-internhub.test',
                'contact_person' => 'Demo HR',
                'contact_person_phone' => '081200000002',
                'description' => 'Perusahaan demo untuk mencoba presensi, laporan harian, dan evaluasi magang.',
                'industry' => 'Teknologi Informasi',
                'website' => config('app.url'),
                'is_verified' => true,
                'verified_at' => now(),
                'latitude' => -6.20000000,
                'longitude' => 106.81666600,
                'allowed_radius' => 500,
            ]
        );

        $supervisorUser = User::updateOrCreate(
            ['email' => 'demo.supervisor@internhub.test'],
            [
                'name' => 'Demo Supervisor',
                'password' => $password,
                'role' => User::ROLE_SUPERVISOR,
                'is_active' => true,
                'email_verified_at' => now(),
            ]
        );

        $supervisor = Supervisor::updateOrCreate(
            ['user_id' => $supervisorUser->id],
            [
                'nip' => 'DEMO-SUP-001',
                'position' => 'Supervisor Demo',
                'phone' => '081200000003',
            ]
        );

        $program = InternshipProgram::updateOrCreate(
            [
                'company_id' => $company->id,
                'title' => 'Demo Magang Aktif Hari Ini',
            ],
            [
                'description' => 'Lowongan demo aktif agar intern existing bisa mencoba presensi GPS/kamera dan membuat laporan harian hari ini.',
                'requirements' => "- User role intern existing\n- Siap mencoba presensi dan laporan harian",
                'quota' => 10,
                'field' => 'Web Development',
                'start_date' => now()->subDays(7)->toDateString(),
                'end_date' => now()->addDays(30)->toDateString(),
                'registration_start' => now()->subDays(14)->toDateString(),
                'registration_end' => now()->addDays(7)->toDateString(),
                'status' => 'open',
                'is_featured' => true,
                'featured_until' => now()->addDays(30),
            ]
        );

        $application = Application::updateOrCreate(
            [
                'user_id' => $internUser->id,
                'internship_program_id' => $program->id,
            ],
            [
                'motivation_letter' => 'Lamaran demo untuk mengaktifkan presensi dan laporan harian pada user intern existing.',
                'cv_file' => null,
                'status' => Application::STATUS_ACCEPTED,
                'status_note' => 'Diterima otomatis untuk demo presensi dan laporan.',
                'rejection_reason' => null,
                'applied_at' => now()->subDays(2),
                'reviewed_at' => now()->subDay(),
            ]
        );

        $internship = Internship::updateOrCreate(
            ['application_id' => $application->id],
            [
                'supervisor_id' => $supervisor->id,
                'company_supervisor_id' => $companyUser->id,
                'start_date' => now()->subDays(7)->toDateString(),
                'end_date' => now()->addDays(30)->toDateString(),
                'status' => Internship::STATUS_ACTIVE,
                'notes' => 'Internship aktif untuk presensi dan laporan hari ini.',
            ]
        );

        Attendance::where('internship_id', $internship->id)
            ->whereDate('attendance_date', today())
            ->delete();

        DailyReport::where('internship_id', $internship->id)
            ->whereDate('report_date', today())
            ->delete();

        $this->command->info("  ActivateExistingInternSeeder: {$internUser->email} sekarang punya internship aktif.");
        $this->command->info('  Presensi dan laporan hari ini sudah dikosongkan agar bisa dicoba.');
    }

    private function targetInternUser(): ?User
    {
        $email = env('ACTIVE_INTERN_EMAIL');

        if ($email) {
            return User::where('email', $email)
                ->whereIn('role', [User::ROLE_INTERN, User::ROLE_USER])
                ->first();
        }

        return User::whereIn('role', [User::ROLE_INTERN, User::ROLE_USER])
            ->where('is_active', true)
            ->where('email', '!=', 'demo.intern@internhub.test')
            ->oldest('id')
            ->first();
    }
}
