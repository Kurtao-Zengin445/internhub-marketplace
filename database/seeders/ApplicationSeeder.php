<?php

namespace Database\Seeders;

use App\Models\Application;
use App\Models\Intern;
use App\Models\InternshipProgram;
use Illuminate\Database\Seeder;

class ApplicationSeeder extends Seeder
{
    public function run(): void
    {
        // Ambil program dan intern berdasarkan data seeder sebelumnya
        $webDev    = InternshipProgram::where('title', 'Magang Web Developer')->first();
        $itSupport = InternshipProgram::where('title', 'Magang IT Support & Networking')->first();
        $finance   = InternshipProgram::where('title', 'Magang Administrasi Keuangan')->first();
        $design    = InternshipProgram::where('title', 'Magang Desain Grafis & Konten Kreatif')->first();

        $budi   = Intern::whereHas('user', fn ($q) => $q->where('email', 'intern@intern.test'))->first();
        $sari   = Intern::whereHas('user', fn ($q) => $q->where('email', 'sari.dewi@intern.test'))->first();
        $riko   = Intern::whereHas('user', fn ($q) => $q->where('email', 'riko.f@intern.test'))->first();
        $nisa   = Intern::whereHas('user', fn ($q) => $q->where('email', 'nisa.a@intern.test'))->first();
        $dimas  = Intern::whereHas('user', fn ($q) => $q->where('email', 'dimas.a@intern.test'))->first();
        $fitri  = Intern::whereHas('user', fn ($q) => $q->where('email', 'fitri.w@intern.test'))->first();
        $galih  = Intern::whereHas('user', fn ($q) => $q->where('email', 'galih.s@intern.test'))->first();
        $hana   = Intern::whereHas('user', fn ($q) => $q->where('email', 'hana.p@intern.test'))->first();

        $motivasi = 'Saya sangat tertarik untuk mengikuti program magang ini karena sesuai dengan bidang keahlian yang saya pelajari di institusi. Saya yakin pengalaman ini akan memberikan wawasan praktis yang sangat berharga untuk karier saya ke depan. Saya berkomitmen untuk belajar dengan sungguh-sungguh dan memberikan kontribusi terbaik selama masa magang.';

        $applications = [
            // Web Developer — 3 diterima (sesuai kuota)
            ['intern' => $budi,  'program' => $webDev,    'status' => 'accepted', 'applied_at' => '2026-04-10'],
            ['intern' => $sari,  'program' => $webDev,    'status' => 'accepted', 'applied_at' => '2026-04-12'],
            ['intern' => $riko,  'program' => $webDev,    'status' => 'accepted', 'applied_at' => '2026-04-14'],

            // IT Support — 1 diterima, 1 ditolak
            ['intern' => $nisa,  'program' => $itSupport, 'status' => 'accepted', 'applied_at' => '2026-05-11'],
            ['intern' => $riko,  'program' => $itSupport, 'status' => 'rejected', 'applied_at' => '2026-05-13',
             'rejection_reason' => 'Kuota program sudah terpenuhi oleh kandidat dengan kualifikasi lebih sesuai.'],

            // Finance — 2 diterima
            ['intern' => $dimas, 'program' => $finance,   'status' => 'accepted', 'applied_at' => '2026-05-10'],
            ['intern' => $fitri, 'program' => $finance,   'status' => 'accepted', 'applied_at' => '2026-05-11'],

            // Design (open) — 2 pending untuk testing alur seleksi
            ['intern' => $galih, 'program' => $design,    'status' => 'pending',  'applied_at' => now()->subDays(3)->format('Y-m-d')],
            ['intern' => $hana,  'program' => $design,    'status' => 'pending',  'applied_at' => now()->subDays(1)->format('Y-m-d')],
        ];

        foreach ($applications as $data) {
            Application::create([
                'user_id'               => $data['intern']->user_id,
                'internship_program_id' => $data['program']->id,
                'motivation_letter'    => $motivasi,
                'cv_file'              => 'seed/cv-sample.pdf',
                'status'               => $data['status'],
                'rejection_reason'     => $data['rejection_reason'] ?? null,
                'applied_at'           => $data['applied_at'].' 08:00:00',
                'reviewed_at'          => in_array($data['status'], ['accepted', 'rejected'])
                                           ? date('Y-m-d H:i:s', strtotime($data['applied_at'] . ' +3 days 09:00:00'))
                                           : null,
            ]);
        }

        $this->command->info('  ApplicationSeeder: ' . count($applications) . ' lamaran dibuat.');
    }
}
