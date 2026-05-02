@extends('layouts.app')

@section('title', 'Dashboard Perusahaan')
@section('page-title', 'Dashboard')
@section('page-subtitle', auth()->user()->company->name ?? auth()->user()->name)

@section('content')

{{-- Stat cards --}}
<div class="row g-3 mb-4">
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card p-3 bg-white rounded-3 border d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#eff6ff;color:#1a56db">
                <i class="bi bi-briefcase-fill"></i>
            </div>
            <div>
                <div class="stat-value">{{ $stats['total_programs'] }}</div>
                <div class="stat-label">Total Lowongan</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card p-3 bg-white rounded-3 border d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#d1fae5;color:#065f46">
                <i class="bi bi-megaphone-fill"></i>
            </div>
            <div>
                <div class="stat-value">{{ $stats['open_programs'] }}</div>
                <div class="stat-label">Lowongan Aktif</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card p-3 bg-white rounded-3 border d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#fef3c7;color:#92400e">
                <i class="bi bi-inbox-fill"></i>
            </div>
            <div>
                <div class="stat-value">{{ $stats['pending_applications'] }}</div>
                <div class="stat-label">Lamaran Pending</div>
            </div>
        </div>
    </div>
    <div class="col-sm-6 col-xl-3">
        <div class="stat-card p-3 bg-white rounded-3 border d-flex align-items-center gap-3">
            <div class="stat-icon" style="background:#ede9fe;color:#4c1d95">
                <i class="bi bi-person-workspace"></i>
            </div>
            <div>
                <div class="stat-value">{{ $stats['active_internships'] }}</div>
                <div class="stat-label">Peserta Aktif</div>
            </div>
        </div>
    </div>
</div>

<div class="alert border-0 mb-4 d-flex flex-wrap align-items-center justify-content-between gap-3" style="background:#f8fafc;border-radius:14px">
    <div>
        <div style="font-size:14px;font-weight:700;color:#0f172a">Status Akun Marketplace</div>
        <div style="font-size:13px;color:#64748b">
            {{ auth()->user()->company?->is_verified ? 'Company Anda sudah terverifikasi dan bisa mengelola lowongan.' : 'Lengkapi profil dan upload dokumen agar admin bisa memverifikasi company Anda.' }}
        </div>
    </div>
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge {{ auth()->user()->company?->is_verified ? 'bg-success' : 'bg-warning text-dark' }}">
            {{ auth()->user()->company?->is_verified ? 'Verified' : 'Pending Verification' }}
        </span>
        <span class="badge {{ auth()->user()->company?->hasActivePremium() ? 'bg-primary' : 'bg-secondary' }}">
            {{ auth()->user()->company?->hasActivePremium() ? 'Premium' : 'Free' }}
        </span>
    </div>
</div>

<div class="row g-3">
    {{-- Lamaran pending --}}
    <div class="col-xl-7">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-inbox-fill text-warning me-2"></i>Lamaran Menunggu Seleksi</span>
                <a href="{{ route('company.applications.index') }}?status=pending" class="btn btn-sm btn-outline-warning">
                    Lihat Semua
                </a>
            </div>
            <div class="card-body p-0">
                @forelse($pendingApplications as $app)
                <div class="d-flex align-items-center gap-3 px-4 py-3 border-bottom">
                    @php
                        $internUser = $app->applicantUser;
                        $intern = $app->intern;
                    @endphp
                    <div style="width:38px;height:38px;border-radius:10px;background:#eff6ff;color:#1a56db;display:flex;align-items:center;justify-content:center;font-size:14px;font-weight:700;flex-shrink:0">
                        {{ strtoupper(substr($internUser?->name ?? '?', 0, 1)) }}
                    </div>
                    <div class="flex-fill">
                        <div style="font-size:13.5px;font-weight:600;color:#0f172a">
                            {{ $internUser?->name ?? 'Nama Tidak Tersedia' }}
                        </div>
                        <div style="font-size:12px;color:#94a3b8">
                            {{ $intern?->major ?: ($internUser?->headline ?: 'Pelamar marketplace') }} &bull;
                            {{ Str::limit($app->program->title, 30) }}
                        </div>
                    </div>
                    <div style="font-size:11.5px;color:#94a3b8;white-space:nowrap">
                        {{ \Carbon\Carbon::parse($app->applied_at)->diffForHumans() }}
                    </div>
                    <a href="{{ route('company.applications.show', $app) }}"
                       class="btn btn-sm btn-primary py-1 ms-2">
                        Tinjau
                    </a>
                </div>
                @empty
                <div class="text-center py-5" style="color:#94a3b8">
                    <i class="bi bi-inbox" style="font-size:36px;display:block;margin-bottom:8px"></i>
                    <span style="font-size:14px">Tidak ada lamaran yang menunggu seleksi.</span>
                </div>
                @endforelse
            </div>
        </div>
    </div>

    {{-- Sidebar kanan --}}
    <div class="col-xl-5 d-flex flex-column gap-3">

        {{-- Peserta magang aktif --}}
        <div class="card flex-fill">
            <div class="card-header d-flex justify-content-between align-items-center">
                <span><i class="bi bi-person-workspace text-primary me-2"></i>Peserta Aktif</span>
                <a href="{{ route('company.internships.index') }}" class="btn btn-sm btn-outline-primary">Semua</a>
            </div>
            <div class="card-body p-0">
                @forelse($activeInternships as $internship)
                <a href="{{ route('company.internships.show', $internship) }}"
                   class="d-flex align-items-center gap-3 px-4 py-3 border-bottom text-decoration-none"
                   onmouseover="this.style.background='#f8fafc'" onmouseout="this.style.background=''">
                    <div style="width:34px;height:34px;border-radius:9px;background:#ede9fe;color:#4c1d95;display:flex;align-items:center;justify-content:center;font-size:13px;font-weight:700;flex-shrink:0">
                        {{ strtoupper(substr($internship->application->intern->user->name, 0, 1)) }}
                    </div>
                    <div class="flex-fill">
                        <div style="font-size:13px;font-weight:600;color:#0f172a">
                            {{ $internship->application->intern->user->name }}
                        </div>
                        <div style="font-size:11.5px;color:#94a3b8">
                            {{ Str::limit($internship->application->program->title, 28) }}
                        </div>
                    </div>
                    <div style="font-size:11px;color:#94a3b8;white-space:nowrap">
                        Selesai {{ \Carbon\Carbon::parse($internship->end_date)->format('d M') }}
                    </div>
                </a>
                @empty
                <div class="text-center py-4" style="color:#94a3b8;font-size:13px">
                    Tidak ada peserta magang aktif.
                </div>
                @endforelse
            </div>
        </div>

        {{-- Quick actions --}}
        <div class="card">
            <div class="card-header"><i class="bi bi-lightning-charge-fill text-warning me-2"></i>Aksi Cepat</div>
            <div class="card-body d-flex flex-column gap-2">
                @if(!auth()->user()->company?->hasActivePremium())
                    <form method="POST" action="{{ route('subscriptions.checkout') }}">
                        @csrf
                        <input type="hidden" name="target" value="company">
                        <button type="submit" class="btn btn-warning w-100">
                            <i class="bi bi-stars me-2"></i>Upgrade Company Premium
                        </button>
                    </form>
                @else
                    <div class="alert alert-success mb-1 py-2" style="font-size:13px">
                        <i class="bi bi-patch-check-fill me-2"></i>
                        Company Premium aktif sampai
                        {{ auth()->user()->company->premium_until?->format('d M Y') }}
                    </div>
                @endif
                <a href="{{ route('company.programs.create') }}" class="btn btn-primary">
                    <i class="bi bi-plus-circle me-2"></i>Buka Lowongan Magang Baru
                </a>
                <a href="{{ route('company.profile.edit') }}" class="btn btn-outline-primary mt-1">
                    <i class="bi bi-building-check me-2"></i>Profil & Verifikasi Company
                </a>
                <a href="{{ route('company.applications.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-inbox me-2"></i>Kelola Semua Lamaran
                </a>
                <a href="{{ route('company.evaluations.index') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-patch-check me-2"></i>Nilai Peserta Magang
                </a>
            </div>
        </div>

    </div>
</div>

@endsection
