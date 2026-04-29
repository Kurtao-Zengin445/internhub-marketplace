<?php

namespace App\Http\Controllers;

use App\Models\InternshipProgram;
use Illuminate\Http\Request;

class MarketplaceController extends Controller
{
    public function index(Request $request)
    {
        $jobPosts = InternshipProgram::with('company')
            ->where('status', 'open')
            ->whereHas('company', fn ($query) => $query->where('is_verified', true))
            ->when($request->filled('keyword'), function ($query) use ($request) {
                $query->where(function ($builder) use ($request) {
                    $builder->where('title', 'like', '%'.$request->keyword.'%')
                        ->orWhere('field', 'like', '%'.$request->keyword.'%')
                        ->orWhere('description', 'like', '%'.$request->keyword.'%');
                });
            })
            ->orderByDesc('is_featured')
            ->latest()
            ->paginate(12)
            ->withQueryString();

        return view('welcome', compact('jobPosts'));
    }
}
