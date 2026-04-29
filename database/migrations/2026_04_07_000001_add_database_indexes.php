<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->index('role');
            $table->index('is_active');
        });

        Schema::table('supervisors', function (Blueprint $table) {
            $table->index('user_id');
        });

        Schema::table('applications', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('internship_program_id');
            $table->index('status');
            $table->index('applied_at');
        });

        Schema::table('internships', function (Blueprint $table) {
            $table->index('application_id');
            $table->index('supervisor_id');
            $table->index('status');
            $table->index(['start_date', 'end_date']);
        });

        Schema::table('daily_reports', function (Blueprint $table) {
            $table->index('internship_id');
            $table->index('report_date');
            $table->index('status');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->index('internship_id');
            $table->index('attendance_date');
            $table->index('status');
        });

        Schema::table('evaluations', function (Blueprint $table) {
            $table->index('internship_id');
            $table->index('evaluator_id');
            $table->index('evaluator_type');
        });

        Schema::table('documents', function (Blueprint $table) {
            $table->index('internship_id');
            $table->index('document_type');
            $table->index('status');
        });

        Schema::table('notifications', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('read_at');
        });

        Schema::table('internship_programs', function (Blueprint $table) {
            $table->index('company_id');
            $table->index('status');
        });
    }

    public function down(): void
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0');

        $this->safeDropIndex('users', 'users_role_index', ['role']);
        $this->safeDropIndex('users', 'users_is_active_index', ['is_active']);
        $this->safeDropIndex('supervisors', 'supervisors_user_id_index', ['user_id']);
        $this->safeDropIndex('applications', 'applications_user_id_index', ['user_id']);
        $this->safeDropIndex('applications', 'applications_internship_program_id_index', ['internship_program_id']);
        $this->safeDropIndex('applications', 'applications_status_index', ['status']);
        $this->safeDropIndex('applications', 'applications_applied_at_index', ['applied_at']);
        $this->safeDropIndex('internships', 'internships_application_id_index', ['application_id']);
        $this->safeDropIndex('internships', 'internships_supervisor_id_index', ['supervisor_id']);
        $this->safeDropIndex('internships', 'internships_status_index', ['status']);
        $this->safeDropIndex('internships', 'internships_start_date_end_date_index', ['start_date', 'end_date']);
        $this->safeDropIndex('daily_reports', 'daily_reports_internship_id_index', ['internship_id']);
        $this->safeDropIndex('daily_reports', 'daily_reports_report_date_index', ['report_date']);
        $this->safeDropIndex('daily_reports', 'daily_reports_status_index', ['status']);
        $this->safeDropIndex('attendances', 'attendances_internship_id_index', ['internship_id']);
        $this->safeDropIndex('attendances', 'attendances_attendance_date_index', ['attendance_date']);
        $this->safeDropIndex('attendances', 'attendances_status_index', ['status']);
        $this->safeDropIndex('evaluations', 'evaluations_internship_id_index', ['internship_id']);
        $this->safeDropIndex('evaluations', 'evaluations_evaluator_id_index', ['evaluator_id']);
        $this->safeDropIndex('evaluations', 'evaluations_evaluator_type_index', ['evaluator_type']);
        $this->safeDropIndex('documents', 'documents_internship_id_index', ['internship_id']);
        $this->safeDropIndex('documents', 'documents_document_type_index', ['document_type']);
        $this->safeDropIndex('documents', 'documents_status_index', ['status']);
        $this->safeDropIndex('notifications', 'notifications_user_id_index', ['user_id']);
        $this->safeDropIndex('notifications', 'notifications_read_at_index', ['read_at']);
        $this->safeDropIndex('internship_programs', 'internship_programs_company_id_index', ['company_id']);
        $this->safeDropIndex('internship_programs', 'internship_programs_status_index', ['status']);

        DB::statement('SET FOREIGN_KEY_CHECKS=1');
    }

    private function safeDropIndex(string $table, string $indexName, array $columns): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        $exists = DB::select("SHOW INDEX FROM `{$table}` WHERE Key_name = '{$indexName}'");

        if (!empty($exists)) {
            Schema::table($table, function (Blueprint $table) use ($indexName) {
                $table->dropIndex($indexName);
            });
        }
    }
};
