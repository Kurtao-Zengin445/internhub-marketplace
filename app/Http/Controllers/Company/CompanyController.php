<?php

namespace App\Http\Controllers\Company;

use App\Http\Controllers\Controller;
use App\Models\Company;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class CompanyController extends Controller
{
    public function edit()
    {
        $company = Auth::user()->company;
        
        if (!$company) {
            return redirect()->route('company.dashboard')
                ->with('error', 'Profil perusahaan belum dibuat. Lengkapi registrasi terlebih dahulu.');
        }

        return view('company.profile', compact('company'));
    }

    public function update(Request $request)
    {
        $user = Auth::user();
        $company = $user->company;
        
        if (!$user->isCompany()) {
            return redirect()->route('company.dashboard')
                ->with('error', 'Hanya perusahaan yang dapat mengubah pengaturan lokasi presensi.');
        }

        if (!$company) {
            return redirect()->route('company.dashboard')
                ->with('error', 'Profil perusahaan tidak ditemukan.');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'address' => ['required', 'string', 'max:500'],
            'phone' => ['nullable', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255'],
            'contact_person' => ['nullable', 'string', 'max:255'],
            'contact_person_phone' => ['nullable', 'string', 'max:20'],
            'industry' => ['nullable', 'string', 'max:100'],
            'description' => ['nullable', 'string'],
            'website' => ['nullable', 'url', 'max:255'],
            'verification_document' => ['nullable', 'file', 'mimes:pdf,jpg,jpeg,png', 'max:5120'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
            'allowed_radius' => ['required', 'integer', 'min:100', 'max:5000'],
        ], [
            'latitude.required_with' => 'Latitude dan longitude harus diisi bersama.',
            'longitude.required_with' => 'Latitude dan longitude harus diisi bersama.',
        ]);

        if ($request->filled('latitude') xor $request->filled('longitude')) {
            return redirect()->back()
                ->withInput()
                ->withErrors(['latitude' => 'Latitude dan longitude harus diisi berpasangan.']);
        }

        if ($request->hasFile('verification_document')) {
            if ($company->verification_document) {
                Storage::disk('public')->delete($company->verification_document);
            }

            $validated['verification_document'] = $request->file('verification_document')
                ->store('verification-documents/companies', 'public');
            $validated['is_verified'] = false;
            $validated['verified_at'] = null;
        }

        $company->update($validated);

        return redirect()->route('company.profile.edit')
            ->with('success', 'Profil perusahaan berhasil diperbarui. Jika dokumen verifikasi diubah, status verifikasi akan menunggu persetujuan admin kembali.');
    }
}
