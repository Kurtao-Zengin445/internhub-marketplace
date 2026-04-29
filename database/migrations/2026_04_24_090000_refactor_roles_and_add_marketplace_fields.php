<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->enum('plan_type', ['free', 'premium'])->default('free')->after('role');
            $table->timestamp('premium_until')->nullable()->after('plan_type');
            $table->string('headline')->nullable()->after('avatar');
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->boolean('is_verified')->default(false)->after('website');
            $table->string('verification_document')->nullable()->after('is_verified');
            $table->timestamp('verified_at')->nullable()->after('verification_document');
            $table->enum('plan_type', ['free', 'premium'])->default('free')->after('verified_at');
            $table->timestamp('premium_until')->nullable()->after('plan_type');
        });

        Schema::table('internship_programs', function (Blueprint $table): void {
            $table->boolean('is_featured')->default(false)->after('status');
            $table->timestamp('featured_until')->nullable()->after('is_featured');
        });
    }

    public function down(): void
    {
        Schema::table('internship_programs', function (Blueprint $table): void {
            $table->dropColumn(['is_featured', 'featured_until']);
        });

        Schema::table('companies', function (Blueprint $table): void {
            $table->dropColumn([
                'is_verified',
                'verification_document',
                'verified_at',
                'plan_type',
                'premium_until',
            ]);
        });

        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn(['plan_type', 'premium_until', 'headline']);
        });
    }
};
