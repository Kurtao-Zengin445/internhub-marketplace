<?php

namespace App\Http\Controllers\Intern;

use App\Http\Controllers\Controller;
use App\Models\Document;
use App\Models\Internship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class InternshipController extends Controller
{
    public function show()
    {
        $internship = $this->currentOrLatestInternship();

        return view('intern.internship.show', compact('internship'));
    }

    public function documents()
    {
        $internship = $this->currentOrLatestInternship();
        $hasActiveInternship = (bool) $internship;
        $documents = $internship
            ? $internship->documents()->latest('uploaded_at')->get()->groupBy('document_type')
            : collect();

        return view('intern.internship.documents', compact('internship', 'hasActiveInternship', 'documents'));
    }

    public function uploadDocument(Request $request)
    {
        $internship = $this->currentOrLatestInternship();
        abort_if(!$internship, 403, 'Belum ada magang aktif.');

        $request->validate([
            'document_type' => ['required', 'in:introduction_letter,acceptance_letter,activity_plan,progress_report,final_report,certificate,other'],
            'title' => ['required', 'string', 'max:255'],
            'file' => ['required', 'file', 'mimes:pdf,doc,docx,jpg,jpeg,png', 'max:10240'],
        ]);

        $file = $request->file('file');
        $path = $file->store('internship/documents', 'public');

        Document::create([
            'internship_id' => $internship->id,
            'document_type' => $request->document_type,
            'title' => $request->title,
            'file_path' => $path,
            'file_name' => $file->getClientOriginalName(),
            'file_type' => $file->getClientOriginalExtension(),
            'file_size' => $file->getSize(),
            'uploaded_by' => auth()->id(),
            'status' => 'pending',
            'uploaded_at' => now(),
        ]);

        return back()->with('success', 'Dokumen berhasil diunggah.');
    }

    public function downloadDocument(Document $document)
    {
        $this->authorizeDocument($document);

        abort_if(!Storage::disk('public')->exists($document->file_path), 404, 'File tidak ditemukan.');

        return Storage::disk('public')->download($document->file_path, $document->file_name);
    }

    public function evaluation()
    {
        $internship = $this->currentOrLatestInternship();
        $supervisorEval = $internship?->evaluations()->where('evaluator_type', 'supervisor')->latest()->first();
        $companyEval = $internship?->evaluations()->where('evaluator_type', 'company')->latest()->first();
        $finalScore = null;

        if ($supervisorEval && $companyEval) {
            $finalScore = ($supervisorEval->final_score * 0.4) + ($companyEval->final_score * 0.6);
        }

        return view('intern.internship.evaluation', compact('internship', 'supervisorEval', 'companyEval', 'finalScore'));
    }

    private function currentOrLatestInternship(): ?Internship
    {
        return Internship::with([
            'application.program.company',
            'supervisor.user',
            'companySupervisor',
            'documents',
            'evaluations.evaluator',
        ])
            ->whereHas('application', fn ($query) => $query->where('user_id', auth()->id()))
            ->orderByRaw("status = 'active' DESC")
            ->latest('start_date')
            ->first();
    }

    private function authorizeDocument(Document $document): void
    {
        abort_if($document->internship?->application?->user_id !== auth()->id(), 403);
    }
}
