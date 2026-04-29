<?php

namespace App\Http\Controllers\Intern;

use App\Http\Controllers\Controller;
use App\Models\Application;
use App\Models\InternshipProgram;
use App\Services\SubscriptionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index()
    {
        $applications = Application::with(['program.company', 'internship'])
            ->where('user_id', auth()->id())
            ->latest('applied_at')
            ->paginate(10);

        return view('intern.applications.index', compact('applications'));
    }

    public function create(Request $request)
    {
        $program = null;
        if ($request->filled('program_id')) {
            $program = $this->openProgramQuery()
                ->findOrFail($request->integer('program_id'));
        }

        $programs = $this->openProgramQuery()
            ->whereDoesntHave('applications', fn ($query) => $query->where('user_id', auth()->id()))
            ->paginate(10)
            ->withQueryString();

        return view('intern.applications.create', compact('program', 'programs'));
    }

    public function store(Request $request, SubscriptionService $subscriptionService)
    {
        $request->validate([
            'internship_program_id' => [
                'required',
                Rule::exists('internship_programs', 'id')->where('status', 'open'),
            ],
            'motivation_letter' => ['required', 'string', 'min:100'],
            'cv_file' => ['nullable', 'file', 'mimes:pdf,doc,docx', 'max:5120'],
        ]);

        $user = $request->user();

        if ($subscriptionService->userApplicationLimitReached($user)) {
            return back()
                ->withInput()
                ->with('error', 'Akun free maksimal 2 lamaran. Upgrade ke premium untuk melamar tanpa batas.');
        }

        $program = $this->openProgramQuery()->findOrFail($request->integer('internship_program_id'));

        if ($program->remainingQuota() <= 0) {
            return back()->withInput()->with('error', 'Kuota lowongan ini sudah penuh.');
        }

        $cvPath = $request->file('cv_file')?->store('applications/cv', 'public');

        Application::create([
            'user_id' => $user->id,
            'internship_program_id' => $program->id,
            'motivation_letter' => $request->motivation_letter,
            'cv_file' => $cvPath,
            'status' => Application::STATUS_PENDING,
            'applied_at' => now(),
        ]);

        return redirect()
            ->route('intern.applications.index')
            ->with('success', 'Lamaran berhasil dikirim.');
    }

    public function show(Application $application)
    {
        $this->authorizeApplication($application);

        $application->load(['program.company', 'internship']);

        return view('intern.applications.show', compact('application'));
    }

    public function destroy(Application $application)
    {
        $this->authorizeApplication($application);

        abort_if(!$application->isPending(), 422, 'Hanya lamaran yang masih menunggu yang bisa dibatalkan.');

        if ($application->cv_file) {
            Storage::disk('public')->delete($application->cv_file);
        }

        $application->update(['status' => Application::STATUS_CANCELLED]);

        return redirect()
            ->route('intern.applications.index')
            ->with('success', 'Lamaran berhasil dibatalkan.');
    }

    private function openProgramQuery()
    {
        return InternshipProgram::with('company')
            ->where('status', 'open')
            ->whereHas('company', fn ($query) => $query->where('is_verified', true))
            ->orderByDesc('is_featured')
            ->latest();
    }

    private function authorizeApplication(Application $application): void
    {
        abort_if($application->user_id !== auth()->id(), 403);
    }
}
