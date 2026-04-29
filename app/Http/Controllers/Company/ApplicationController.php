<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Mail\ApplicationStatusUpdatedMail;
use App\Models\Application;
use App\Models\Company;
use App\Models\Internship;
use App\Models\InternshipProgram;
use App\Models\Notification;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class ApplicationController extends Controller
{
    public function index(Request $request)
    {
        $company = $this->currentCompany();

        $applications = Application::with(['user', 'program'])
            ->whereHas('program', fn ($query) => $query->where('company_id', $company->id))
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('program_id'), fn ($query) => $query->where('internship_program_id', $request->program_id))
            ->priorityOrder()
            ->paginate(15)
            ->withQueryString();

        $programs = InternshipProgram::where('company_id', $company->id)
            ->orderBy('title')
            ->get();

        return view('company.applications.index', compact('applications', 'programs'));
    }

    public function show(Application $application)
    {
        $this->authorizeApplication($application);

        $application->load([
            'user',
            'program.company',
            'internship.companySupervisor',
        ]);

        return view('company.applications.show', compact('application'));
    }

    public function accept(Request $request, Application $application)
    {
        $this->authorizeApplication($application);

        abort_if(!$application->isPending(), 422, 'Lamaran ini sudah diproses sebelumnya.');

        $program = $application->program;
        if ($program->remainingQuota() <= 0) {
            return back()->with('error', 'Kuota program sudah penuh. Tidak dapat menerima lamaran baru.');
        }

        $request->validate([
            'company_supervisor_id' => ['nullable', Rule::exists('users', 'id')->where('role', 'company')],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        DB::transaction(function () use ($application, $request, $program) {
            $application->update([
                'status' => Application::STATUS_ACCEPTED,
                'status_note' => $request->notes,
                'reviewed_at' => now(),
            ]);

            Internship::create([
                'application_id' => $application->id,
                'company_supervisor_id' => $request->company_supervisor_id,
                'start_date' => $program->start_date,
                'end_date' => $program->end_date,
                'status' => 'active',
                'notes' => $request->notes,
            ]);

            if ($program->remainingQuota() <= 1) {
                $program->update(['status' => 'closed']);
            }

            Notification::send(
                $application->user_id,
                'Lamaran Diterima!',
                "Selamat! Lamaran Anda untuk program {$program->title} di {$program->company->name} telah diterima.",
                'approval',
                $application,
                route('intern.internship.show')
            );
        });

        if ($application->user?->email) {
            Mail::to($application->user->email)
                ->queue(new ApplicationStatusUpdatedMail($application->fresh(['program.company', 'user']), 'Diterima'));
        }

        return redirect()
            ->route('company.applications.show', $application)
            ->with('success', 'Lamaran berhasil diterima dan data magang telah dibuat.');
    }

    public function reject(Request $request, Application $application)
    {
        $this->authorizeApplication($application);

        abort_if(!$application->isPending(), 422, 'Lamaran ini sudah diproses sebelumnya.');

        $request->validate([
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $application->update([
            'status' => Application::STATUS_REJECTED,
            'rejection_reason' => $request->rejection_reason,
            'status_note' => $request->rejection_reason,
            'reviewed_at' => now(),
        ]);

        Notification::send(
            $application->user_id,
            'Lamaran Tidak Diterima',
            "Lamaran Anda untuk program {$application->program->title} tidak dapat kami terima saat ini.",
            'warning',
            $application,
            route('intern.applications.show', $application)
        );

        if ($application->user?->email) {
            Mail::to($application->user->email)
                ->queue(new ApplicationStatusUpdatedMail($application->fresh(['program.company', 'user']), 'Ditolak'));
        }

        return redirect()
            ->route('company.applications.index')
            ->with('success', 'Lamaran berhasil ditolak.');
    }

    public function bulkReject(Request $request)
    {
        $company = $this->currentCompany();

        $request->validate([
            'application_ids' => ['required', 'array'],
            'application_ids.*' => ['exists:applications,id'],
            'rejection_reason' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        $applications = Application::whereIn('id', $request->application_ids)
            ->whereHas('program', fn ($query) => $query->where('company_id', $company->id))
            ->where('status', Application::STATUS_PENDING)
            ->get();

        foreach ($applications as $application) {
            $application->update([
                'status' => Application::STATUS_REJECTED,
                'rejection_reason' => $request->rejection_reason,
                'reviewed_at' => now(),
            ]);

            Notification::send(
                $application->user_id,
                'Lamaran Tidak Diterima',
                "Lamaran Anda untuk program {$application->program->title} tidak dapat kami terima saat ini.",
                'warning',
                $application,
                route('intern.applications.show', $application)
            );

            if ($application->user?->email) {
                Mail::to($application->user->email)
                    ->queue(new ApplicationStatusUpdatedMail($application->fresh(['program.company', 'user']), 'Ditolak'));
            }
        }

        return back()->with('success', "{$applications->count()} lamaran berhasil ditolak.");
    }

    private function authorizeApplication(Application $application): void
    {
        abort_if($application->program->company_id !== $this->currentCompany()->id, 403);
    }

    private function currentCompany(): Company
    {
        $company = auth()->guard('web')->user()?->company;

        abort_if(!$company, 403, 'Profil perusahaan belum tersedia. Silakan lengkapi profil terlebih dahulu.');

        return $company;
    }
}

