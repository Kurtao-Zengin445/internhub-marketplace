<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('internship_program_id')->constrained()->cascadeOnDelete();
            $table->text('motivation_letter')->nullable();
            $table->string('cv_file')->nullable()->comment('Path file CV');
            $table->enum('status', [
                'pending',      // Menunggu review
                'reviewed',     // Sudah direview perusahaan
                'accepted',     // Diterima
                'rejected',     // Ditolak
                'cancelled',    // Dibatalkan oleh intern
            ])->default('pending');
            $table->text('rejection_reason')->nullable();
            $table->timestamp('applied_at')->useCurrent();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            // Satu user hanya boleh melamar satu program yang sama sekali
            $table->unique(['user_id', 'internship_program_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('applications');
    }
};
