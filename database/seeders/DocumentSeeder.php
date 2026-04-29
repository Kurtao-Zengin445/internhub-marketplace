<?php

namespace Database\Seeders;

use App\Models\Document;
use App\Models\Internship;
use Illuminate\Database\Seeder;

class DocumentSeeder extends Seeder
{
    public function run(): void
    {
        $internships = Internship::where('status', 'completed')
            ->with('application.intern.user')
            ->get();

        $count = 0;

        foreach ($internships as $internship) {
            $intern = $internship->application->intern;
            $internUserId = $intern?->user_id;
            
            if (!$internUserId) {
                continue;
            }
            
            $internName   = str_replace(' ', '_', strtolower($intern->user->name ?? 'intern'));

            $documents = [
                [
                    'document_type' => 'introduction_letter',
                    'title'         => 'Surat Pengantar Magang dari institusi',
                    'file_name'     => "surat_pengantar_{$internName}.pdf",
                    'file_type'     => 'pdf',
                    'file_size'     => rand(150000, 400000),
                    'status'        => 'approved',
                    'uploaded_by'   => $internUserId,
                ],
                [
                    'document_type' => 'acceptance_letter',
                    'title'         => 'Surat Penerimaan Magang dari Perusahaan',
                    'file_name'     => "surat_penerimaan_{$internName}.pdf",
                    'file_type'     => 'pdf',
                    'file_size'     => rand(100000, 300000),
                    'status'        => 'approved',
                    'uploaded_by'   => $internUserId,
                ],
                [
                    'document_type' => 'final_report',
                    'title'         => 'Laporan Akhir Magang',
                    'file_name'     => "laporan_akhir_{$internName}.pdf",
                    'file_type'     => 'pdf',
                    'file_size'     => rand(500000, 2000000),
                    'status'        => 'approved',
                    'uploaded_by'   => $internUserId,
                ],
                [
                    'document_type' => 'certificate',
                    'title'         => 'Sertifikat Magang',
                    'file_name'     => "sertifikat_{$internName}.pdf",
                    'file_type'     => 'pdf',
                    'file_size'     => rand(200000, 600000),
                    'status'        => 'approved',
                    'uploaded_by'   => $internship->company_supervisor_id ?? $internUserId,
                ],
            ];

            foreach ($documents as $doc) {
                Document::create(array_merge($doc, [
                    'internship_id' => $internship->id,
                    'file_path'     => "documents/{$internship->id}/{$doc['file_name']}",
                    'uploaded_at'   => $internship->end_date,
                ]));
                $count++;
            }
        }

        $this->command->info("  DocumentSeeder: {$count} dokumen dibuat.");
    }
}
