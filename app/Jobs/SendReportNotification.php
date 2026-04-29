<?php

namespace App\Jobs;

use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class SendReportNotification implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public int $reportId,
        public string $action // 'approved' or 'rejected'
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $report = \App\Models\DailyReport::with(['internship.application.user', 'internship.supervisor.user'])
            ->findOrFail($this->reportId);

        $applicant = $report->internship->application->user;
        $supervisor = $report->internship->supervisor->user;

        if ($this->action === 'approved') {
            \App\Mail\ReportApprovedMailable::to($applicant->email)
                ->queue(new \App\Mail\ReportApprovedMailable(
                    $applicant->name,
                    $supervisor->name,
                    $report->report_date->format('Y-m-d'),
                    $report->report_date->translatedFormat('d F Y'),
                    Str::limit($report->activity, 100),
                    $report->feedback,
                    url('/intern/internship')
                ));
        } else {
            \App\Mail\ReportRejectedMailable::to($applicant->email)
                ->queue(new \App\Mail\ReportRejectedMailable(
                    $applicant->name,
                    $supervisor->name,
                    $report->report_date->format('Y-m-d'),
                    $report->report_date->translatedFormat('d F Y'),
                    $report->feedback,
                    url('/intern/reports/' . $report->id . '/edit')
                ));
        }
    }
}
