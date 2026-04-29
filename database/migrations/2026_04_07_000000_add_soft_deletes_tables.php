<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('applications', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('companies', function (Blueprint $table) {
            $table->softDeletes();
        });

        Schema::table('supervisors', function (Blueprint $table) {
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('applications', 'deleted_at')) {
            Schema::table('applications', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('internships', 'deleted_at')) {
            Schema::table('internships', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('companies', 'deleted_at')) {
            Schema::table('companies', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }

        if (Schema::hasColumn('supervisors', 'deleted_at')) {
            Schema::table('supervisors', function (Blueprint $table) {
                $table->dropSoftDeletes();
            });
        }
    }
};
