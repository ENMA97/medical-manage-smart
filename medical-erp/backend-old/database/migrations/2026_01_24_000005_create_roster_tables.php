<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // أنماط الدوام
        Schema::create('shift_patterns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('name_ar');
            $table->enum('type', [
                'single',     // شفت واحد
                'split',      // فترتين
                'on_call',    // استدعاء
                'rotating'    // متناوب
            ]);
            $table->time('start_time');
            $table->time('end_time');
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->integer('total_hours');
            $table->boolean('overnight')->default(false);
            $table->json('applicable_days')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // جداول المناوبات الشهرية
        Schema::create('rosters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('department_id');
            $table->integer('month');
            $table->integer('year');
            $table->enum('status', [
                'draft',
                'pending_review',
                'approved',
                'published',
                'archived'
            ])->default('draft');
            $table->boolean('has_sterilization_coverage')->default(false); // تغطية التعقيم
            $table->json('gap_analysis')->nullable();
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            
            $table->unique(['department_id', 'month', 'year']);
        });

        // مناوبات الموظفين
        Schema::create('roster_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('roster_id');
            $table->uuid('employee_id');
            $table->date('assignment_date');
            $table->uuid('shift_pattern_id');
            $table->time('actual_start')->nullable();
            $table->time('actual_end')->nullable();
            $table->boolean('is_sterilization_responsible')->default(false); // مسؤول التعقيم
            $table->enum('status', [
                'scheduled',
                'confirmed',
                'completed',
                'absent',
                'late',
                'early_leave',
                'swapped'
            ])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('roster_id')->references('id')->on('rosters')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('shift_pattern_id')->references('id')->on('shift_patterns');
            
            $table->unique(['roster_id', 'employee_id', 'assignment_date']);
            $table->index(['assignment_date', 'status']);
        });

        // طلبات تبديل المناوبات
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('requester_assignment_id');
            $table->uuid('target_assignment_id');
            $table->text('reason')->nullable();
            $table->enum('status', [
                'pending',
                'accepted',
                'rejected',
                'cancelled'
            ])->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('requester_assignment_id')->references('id')->on('roster_assignments');
            $table->foreign('target_assignment_id')->references('id')->on('roster_assignments');
            $table->foreign('approved_by')->references('id')->on('users');
        });

        // سجلات البصمة
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->date('attendance_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('check_in_device')->nullable();
            $table->string('check_out_device')->nullable();
            $table->integer('late_minutes')->default(0);
            $table->integer('early_leave_minutes')->default(0);
            $table->integer('overtime_minutes')->default(0);
            $table->integer('total_worked_minutes')->default(0);
            $table->enum('source', ['biometric', 'manual', 'system'])->default('biometric');
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('verified_by')->references('id')->on('users');
            
            $table->unique(['employee_id', 'attendance_date']);
            $table->index('attendance_date');
        });

        // إعدادات الربط مع أجهزة البصمة
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('serial_number')->unique();
            $table->string('ip_address');
            $table->integer('port')->default(4370);
            $table->enum('type', ['ZKTeco', 'Hikvision', 'Other'])->default('ZKTeco');
            $table->uuid('location_id')->nullable();
            $table->enum('status', ['active', 'inactive', 'maintenance'])->default('active');
            $table->timestamp('last_sync_at')->nullable();
            $table->json('sync_settings')->nullable();
            $table->timestamps();
        });

        // قواعد التحقق من الجدول
        Schema::create('roster_validation_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('department_id')->nullable(); // null = applies to all
            $table->string('rule_code')->unique();
            $table->string('name');
            $table->string('name_ar');
            $table->text('description')->nullable();
            $table->enum('severity', ['error', 'warning', 'info'])->default('error');
            $table->json('rule_config'); // معايير القاعدة
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('roster_validation_rules');
        Schema::dropIfExists('biometric_devices');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('shift_swap_requests');
        Schema::dropIfExists('roster_assignments');
        Schema::dropIfExists('rosters');
        Schema::dropIfExists('shift_patterns');
    }
};
