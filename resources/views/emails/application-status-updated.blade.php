<p>Halo {{ $application->applicantUser?->name ?? 'pengguna' }},</p>
<p>Status lamaran Anda untuk posisi <strong>{{ $application->program?->title }}</strong> di <strong>{{ $application->program?->company?->name }}</strong> telah diperbarui menjadi <strong>{{ $statusLabel }}</strong>.</p>
@if($application->status_note)
<p>Catatan: {{ $application->status_note }}</p>
@endif
@if($application->rejection_reason)
<p>Alasan: {{ $application->rejection_reason }}</p>
@endif
<p>Silakan login ke dashboard untuk melihat detail terbaru.</p>
