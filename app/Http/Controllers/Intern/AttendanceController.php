<?php

namespace App\Http\Controllers\Intern;

use App\Exports\InternAttendanceExport;
use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\Internship;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Maatwebsite\Excel\Facades\Excel;

class AttendanceController extends Controller
{
    public function index(Request $request)
    {
        $internship = $this->currentInternship();
        $hasActiveInternship = (bool) $internship;

        $attendances = Attendance::where('internship_id', $internship?->id ?? 0)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('notes', 'like', '%' . $request->search . '%')
                    ->orWhere('status', 'like', '%' . $request->search . '%')
                    ->orWhereDate('attendance_date', $request->search);
            })
            ->latest('attendance_date')
            ->paginate(15)
            ->withQueryString();

        $summary = [
            'present' => $internship?->attendances()->where('status', 'present')->count() ?? 0,
            'sick' => $internship?->attendances()->where('status', 'sick')->count() ?? 0,
            'permission' => $internship?->attendances()->where('status', 'permission')->count() ?? 0,
            'absent' => $internship?->attendances()->where('status', 'absent')->count() ?? 0,
            'percentage' => 0,
        ];

        $total = array_sum(array_slice($summary, 0, 4));
        $summary['percentage'] = $total > 0 ? round(($summary['present'] / $total) * 100) : 0;

        return view('intern.attendance.index', compact('internship', 'hasActiveInternship', 'attendances', 'summary'));
    }

    public function today()
    {
        $internship = $this->currentInternship();
        $hasActiveInternship = (bool) $internship;
        $today = $internship?->attendances()->whereDate('attendance_date', today())->first();
        $company = $internship?->application?->program?->company;
        $hasCompanyCoord = $company && $company->latitude && $company->longitude;
        $allowedRadius = $company?->allowed_radius ?? 200;

        return view('intern.attendance.today', compact(
            'internship',
            'hasActiveInternship',
            'today',
            'hasCompanyCoord',
            'allowedRadius'
        ));
    }

    public function show(Attendance $attendance)
    {
        $this->authorizeAttendance($attendance);

        return view('intern.attendance.show', compact('attendance'));
    }

    public function checkin(Request $request)
    {
        $internship = $this->currentInternship();
        abort_if(!$internship, 403, 'Belum ada magang aktif.');

        $request->validate([
            'selfie' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'address' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string', 'max:500'],
        ]);

        abort_if($internship->attendances()->whereDate('attendance_date', today())->exists(), 422, 'Presensi hari ini sudah tercatat.');

        $company = $internship->application->program->company;
        $distance = $this->distanceFromCompany($company, $request->latitude, $request->longitude);

        Attendance::create([
            'internship_id' => $internship->id,
            'attendance_date' => today(),
            'check_in' => now()->format('H:i:s'),
            'status' => 'present',
            'notes' => $request->notes,
            'check_in_photo' => $this->storeSelfie($request->selfie, 'checkin'),
            'checkin_latitude' => $request->latitude,
            'checkin_longitude' => $request->longitude,
            'checkin_address' => $request->address,
            'checkin_distance' => $distance,
        ]);

        return redirect()->route('intern.attendance.today')->with('success', 'Check in berhasil dicatat.');
    }

    public function checkout(Request $request)
    {
        $internship = $this->currentInternship();
        abort_if(!$internship, 403, 'Belum ada magang aktif.');

        $attendance = $internship->attendances()->whereDate('attendance_date', today())->firstOrFail();
        abort_if($attendance->check_out, 422, 'Check out hari ini sudah tercatat.');

        $request->validate([
            'selfie' => ['required', 'string'],
            'latitude' => ['required', 'numeric'],
            'longitude' => ['required', 'numeric'],
            'address' => ['nullable', 'string', 'max:255'],
        ]);

        $company = $internship->application->program->company;
        $distance = $this->distanceFromCompany($company, $request->latitude, $request->longitude);

        $attendance->update([
            'check_out' => now()->format('H:i:s'),
            'check_out_photo' => $this->storeSelfie($request->selfie, 'checkout'),
            'checkout_latitude' => $request->latitude,
            'checkout_longitude' => $request->longitude,
            'checkout_address' => $request->address,
            'checkout_distance' => $distance,
        ]);

        return redirect()->route('intern.attendance.today')->with('success', 'Check out berhasil dicatat.');
    }

    public function leave(Request $request)
    {
        $internship = $this->currentInternship();
        abort_if(!$internship, 403, 'Belum ada magang aktif.');

        $request->validate([
            'status' => ['required', 'in:sick,izin,permission'],
            'notes' => ['required', 'string', 'min:10', 'max:500'],
        ]);

        abort_if($internship->attendances()->whereDate('attendance_date', today())->exists(), 422, 'Presensi hari ini sudah tercatat.');

        Attendance::create([
            'internship_id' => $internship->id,
            'attendance_date' => today(),
            'status' => $request->status === 'izin' ? 'permission' : $request->status,
            'notes' => $request->notes,
        ]);

        return redirect()->route('intern.attendance.today')->with('success', 'Keterangan tidak hadir berhasil dikirim.');
    }

    public function export()
    {
        $internship = $this->currentInternship();
        abort_if(!$internship, 403, 'Belum ada magang aktif.');

        return Excel::download(new InternAttendanceExport($internship->id), 'presensi-intern.xlsx');
    }

    private function currentInternship(): ?Internship
    {
        return Internship::with('application.program.company')
            ->whereHas('application', fn ($query) => $query->where('user_id', auth()->id()))
            ->where('status', Internship::STATUS_ACTIVE)
            ->latest('start_date')
            ->first();
    }

    private function authorizeAttendance(Attendance $attendance): void
    {
        abort_if($attendance->internship?->application?->user_id !== auth()->id(), 403);
    }

    private function storeSelfie(string $selfie, string $prefix): string
    {
        $image = preg_replace('/^data:image\/\w+;base64,/', '', $selfie);
        $path = 'attendance/' . $prefix . '-' . auth()->id() . '-' . now()->format('YmdHis') . '.jpg';

        Storage::disk('public')->put($path, base64_decode($image));

        return $path;
    }

    private function distanceFromCompany($company, $latitude, $longitude): ?int
    {
        if (!$company?->latitude || !$company?->longitude) {
            return null;
        }

        $earthRadius = 6371000;
        $latFrom = deg2rad((float) $company->latitude);
        $lonFrom = deg2rad((float) $company->longitude);
        $latTo = deg2rad((float) $latitude);
        $lonTo = deg2rad((float) $longitude);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $angle = 2 * asin(sqrt(pow(sin($latDelta / 2), 2) + cos($latFrom) * cos($latTo) * pow(sin($lonDelta / 2), 2)));

        return (int) round($angle * $earthRadius);
    }
}
