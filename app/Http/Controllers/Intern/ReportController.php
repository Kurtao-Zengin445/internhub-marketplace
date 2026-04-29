<?php

namespace App\Http\Controllers\Intern;

use App\Exports\InternDailyReportsExport;
use App\Http\Controllers\Controller;
use App\Models\DailyReport;
use App\Models\Internship;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $internship = $this->currentInternship();
        $hasActiveInternship = (bool) $internship;

        $reports = DailyReport::where('internship_id', $internship?->id ?? 0)
            ->when($request->filled('status'), fn ($query) => $query->where('status', $request->status))
            ->when($request->filled('search'), function ($query) use ($request) {
                $query->where('activity', 'like', '%' . $request->search . '%')
                    ->orWhere('status', 'like', '%' . $request->search . '%');
            })
            ->latest('report_date')
            ->paginate(10)
            ->withQueryString();

        return view('intern.reports.index', compact('internship', 'hasActiveInternship', 'reports'));
    }

    public function create()
    {
        abort_if(!$this->currentInternship(), 403, 'Belum ada magang aktif.');

        return view('intern.reports.create');
    }

    public function store(Request $request)
    {
        $internship = $this->currentInternship();
        abort_if(!$internship, 403, 'Belum ada magang aktif.');

        $data = $this->validatedData($request);
        $data['internship_id'] = $internship->id;
        $data['status'] = $request->boolean('send') ? 'submitted' : 'draft';
        $data['submitted_at'] = $request->boolean('send') ? now() : null;

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('reports/photos', 'public');
        }

        DailyReport::create($data);

        return redirect()->route('intern.reports.index')->with('success', 'Laporan berhasil disimpan.');
    }

    public function show(DailyReport $dailyReport)
    {
        $this->authorizeReport($dailyReport);

        return view('intern.reports.show', ['report' => $dailyReport]);
    }

    public function edit(DailyReport $dailyReport)
    {
        $this->authorizeReport($dailyReport);
        abort_if(!in_array($dailyReport->status, ['draft', 'revision'], true), 422, 'Laporan ini tidak bisa diedit.');

        return view('intern.reports.edit', ['report' => $dailyReport]);
    }

    public function update(Request $request, DailyReport $dailyReport)
    {
        $this->authorizeReport($dailyReport);
        abort_if(!in_array($dailyReport->status, ['draft', 'revision'], true), 422, 'Laporan ini tidak bisa diedit.');

        $data = $this->validatedData($request, false);
        $data['status'] = $request->boolean('send') ? 'submitted' : 'draft';
        $data['submitted_at'] = $request->boolean('send') ? now() : $dailyReport->submitted_at;

        if ($request->input('keep_photo') === '0') {
            $data['photo'] = null;
        }

        if ($request->hasFile('photo')) {
            $data['photo'] = $request->file('photo')->store('reports/photos', 'public');
        }

        $dailyReport->update($data);

        return redirect()->route('intern.reports.show', $dailyReport)->with('success', 'Laporan berhasil diperbarui.');
    }

    public function destroy(DailyReport $dailyReport)
    {
        $this->authorizeReport($dailyReport);
        abort_if($dailyReport->status !== 'draft', 422, 'Hanya draft yang bisa dihapus.');

        $dailyReport->delete();

        return redirect()->route('intern.reports.index')->with('success', 'Draft laporan berhasil dihapus.');
    }

    public function export()
    {
        $internship = $this->currentInternship();
        abort_if(!$internship, 403, 'Belum ada magang aktif.');

        return Excel::download(new InternDailyReportsExport($internship->id), 'laporan-intern.xlsx');
    }

    private function validatedData(Request $request, bool $validateDate = true): array
    {
        return $request->validate([
            'report_date' => [$validateDate ? 'required' : 'sometimes', 'date', 'before_or_equal:today'],
            'activity' => ['required', 'string', 'min:20', 'max:2000'],
            'problems' => ['nullable', 'string', 'max:1000'],
            'solutions' => ['nullable', 'string', 'max:1000'],
            'photo' => ['nullable', 'image', 'max:3072'],
        ]);
    }

    private function currentInternship(): ?Internship
    {
        return Internship::whereHas('application', fn ($query) => $query->where('user_id', auth()->id()))
            ->where('status', Internship::STATUS_ACTIVE)
            ->latest('start_date')
            ->first();
    }

    private function authorizeReport(DailyReport $report): void
    {
        abort_if($report->internship?->application?->user_id !== auth()->id(), 403);
    }
}
