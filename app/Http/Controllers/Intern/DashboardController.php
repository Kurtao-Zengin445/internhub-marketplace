<?php

namespace App\Http\Controllers\Intern;

use App\Http\Controllers\Controller;
use App\Models\Internship;
use App\Models\InternshipProgram;

class DashboardController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $internship = $this->currentInternship();

        $stats = [
            'approved_reports' => 0,
            'pending_reports' => 0,
            'total_reports' => 0,
            'attendance_pct' => 0,
            'days_remaining' => 0,
        ];
        $recentReports = collect();

        if ($internship) {
            $stats = [
                'approved_reports' => $internship->dailyReports()->where('status', 'approved')->count(),
                'pending_reports' => $internship->dailyReports()->where('status', 'submitted')->count(),
                'total_reports' => $internship->dailyReports()->count(),
                'attendance_pct' => $internship->attendancePercentage(),
                'days_remaining' => max(0, now()->diffInDays($internship->end_date, false)),
            ];

            $recentReports = $internship->dailyReports()
                ->latest('report_date')
                ->take(5)
                ->get();
        }

        $availablePrograms = InternshipProgram::with('company')
            ->where('status', 'open')
            ->whereHas('company', fn ($query) => $query->where('is_verified', true))
            ->whereDoesntHave('applications', fn ($query) => $query->where('user_id', $user->id))
            ->orderByDesc('is_featured')
            ->latest()
            ->take(4)
            ->get();

        return view('intern.dashboard', compact('internship', 'stats', 'recentReports', 'availablePrograms'));
    }

    private function currentInternship(): ?Internship
    {
        return Internship::with(['application.program.company', 'supervisor.user', 'companySupervisor'])
            ->whereHas('application', fn ($query) => $query->where('user_id', auth()->id()))
            ->where('status', Internship::STATUS_ACTIVE)
            ->latest('start_date')
            ->first();
    }
}
