<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Company;
use App\Models\Internship;
use App\Models\Application;
use App\Models\InternshipProgram;

class DashboardController extends Controller
{
    public function index()
    {
        $stats = [
            'total_users'       => User::whereIn('role', ['intern', 'user'])->count(),
            'verified_companies'=> Company::where('is_verified', true)->count(),
            'total_companies'   => Company::count(),
            'total_programs'    => InternshipProgram::count(),
            'active_internships' => Internship::where('status', 'active')->count(),
            'pending_applications' => Application::where('status', 'pending')->count(),
        ];

        // Lamaran terbaru
        $recentApplications = Application::with([
            'applicantUser',
            'intern',
            'program.company',
        ])
        ->latest('applied_at')
        ->take(8)
        ->get();

        // Program magang yang sedang buka
        $openPrograms = InternshipProgram::with('company')
            ->where('status', 'open')
            ->withCount('applications')
            ->latest()
            ->take(5)
            ->get();

        // User terbaru bergabung
        $recentUsers = User::where('role', '!=', 'admin')
            ->latest()
            ->take(5)
            ->get();

        return view('admin.dashboard', compact(
            'stats',
            'recentApplications',
            'openPrograms',
            'recentUsers',
        ));
    }
}
