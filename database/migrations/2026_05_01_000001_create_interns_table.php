<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interns', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('nisn', 20)->nullable()->unique()->comment('Nomor identitas intern');
            $table->string('nim', 30)->nullable()->unique()->comment('Nomor identitas kampus');
            $table->string('institution')->nullable()->comment('Nama institusi asal');
            $table->string('education_level', 20)->nullable()->comment('SMK, SMA, D3, S1, dll');
            $table->string('major')->nullable()->comment('Jurusan, contoh: Rekayasa Perangkat Lunak');
            $table->string('phone', 20)->nullable();
            $table->text('address')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->string('photo')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interns');
    }
};
