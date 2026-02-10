<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // أنماط الورديات
        Schema::create('shift_patterns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();

            $table->enum('type', [
                'morning',      // صباحي
                'evening',      // مسائي
                'night',        // ليلي
                'split'         // متقطع
            ]);

            $table->time('start_time');
            $table->time('end_time');
            $table->time('break_start')->nullable();
            $table->time('break_end')->nullable();
            $table->decimal('break_duration_minutes', 5, 2)->default(0);
            $table->decimal('scheduled_hours', 4, 2); // ساعات العمل المجدولة

            $table->string('color_code', 7)->default('#3B82F6'); // للعرض في الجدولة
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // الجداول الشهرية
        Schema::create('rosters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('roster_number')->unique();
            $table->uuid('department_id');
            $table->integer('year');
            $table->integer('month');

            $table->enum('status', [
                'draft',        // مسودة
                'published',    // منشور
                'locked'        // مغلق
            ])->default('draft');

            $table->uuid('created_by');
            $table->uuid('published_by')->nullable();
            $table->timestamp('published_at')->nullable();
            $table->uuid('locked_by')->nullable();
            $table->timestamp('locked_at')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('created_by')->references('id')->on('users');

            $table->unique(['department_id', 'year', 'month']);
        });

        // تعيينات الورديات (الجدولة اليومية)
        Schema::create('roster_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('roster_id')->nullable();
            $table->uuid('employee_id');
            $table->uuid('shift_pattern_id')->nullable();
            $table->date('assignment_date');

            $table->enum('type', [
                'regular',      // عادي
                'overtime',     // إضافي
                'on_call',      // تحت الطلب
                'off'           // إجازة/راحة
            ])->default('regular');

            // أوقات الوردية
            $table->time('scheduled_start')->nullable();
            $table->time('scheduled_end')->nullable();
            $table->decimal('scheduled_hours', 4, 2)->default(0);

            // الحضور الفعلي
            $table->timestamp('actual_start')->nullable();
            $table->timestamp('actual_end')->nullable();
            $table->decimal('actual_hours', 5, 2)->nullable();
            $table->integer('late_minutes')->default(0);
            $table->integer('early_leave_minutes')->default(0);

            // الوقت الإضافي
            $table->boolean('is_overtime')->default(false);
            $table->decimal('overtime_hours', 5, 2)->default(0);
            $table->decimal('overtime_rate', 3, 2)->default(1.5);

            // الحالة
            $table->enum('status', [
                'scheduled',    // مجدول
                'present',      // حاضر
                'absent',       // غائب
                'late',         // متأخر
                'on_leave',     // في إجازة
                'sick',         // مريض
                'completed'     // مكتمل
            ])->default('scheduled');

            $table->text('notes')->nullable();
            $table->uuid('updated_by')->nullable();
            $table->timestamps();

            $table->foreign('roster_id')->references('id')->on('rosters')->nullOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('shift_pattern_id')->references('id')->on('shift_patterns')->nullOnDelete();

            $table->unique(['employee_id', 'assignment_date']);
            $table->index(['employee_id', 'assignment_date', 'status']);
            $table->index(['assignment_date', 'status']);
        });

        // طلبات تبديل الورديات
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_number')->unique();

            $table->uuid('requester_id');
            $table->uuid('requester_assignment_id');
            $table->uuid('target_employee_id');
            $table->uuid('target_assignment_id');

            $table->text('reason')->nullable();

            $table->enum('status', [
                'pending',          // قيد الانتظار
                'target_approved',  // وافق الطرف الآخر
                'supervisor_approved', // وافق المشرف
                'approved',         // معتمد
                'rejected',         // مرفوض
                'cancelled'         // ملغي
            ])->default('pending');

            $table->uuid('target_response_by')->nullable();
            $table->timestamp('target_response_at')->nullable();
            $table->text('target_response_notes')->nullable();

            $table->uuid('supervisor_response_by')->nullable();
            $table->timestamp('supervisor_response_at')->nullable();
            $table->text('supervisor_response_notes')->nullable();

            $table->timestamps();

            $table->foreign('requester_id')->references('id')->on('employees');
            $table->foreign('requester_assignment_id')->references('id')->on('roster_assignments');
            $table->foreign('target_employee_id')->references('id')->on('employees');
            $table->foreign('target_assignment_id')->references('id')->on('roster_assignments');
        });

        // سجلات الحضور (من أجهزة البصمة)
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('device_id')->nullable();

            $table->enum('type', ['check_in', 'check_out', 'break_start', 'break_end']);
            $table->timestamp('punched_at');
            $table->string('source')->default('device'); // device, manual, mobile

            $table->decimal('latitude', 10, 8)->nullable();
            $table->decimal('longitude', 11, 8)->nullable();
            $table->string('location_name')->nullable();

            $table->string('device_serial')->nullable();
            $table->boolean('is_valid')->default(true);
            $table->text('notes')->nullable();

            $table->uuid('processed_by')->nullable();
            $table->timestamp('processed_at')->nullable();

            $table->timestamp('created_at')->useCurrent();
            // لا يوجد updated_at - السجل غير قابل للتعديل

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();

            $table->index(['employee_id', 'punched_at']);
            $table->index(['punched_at', 'type']);
        });

        // أجهزة البصمة
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('serial_number')->unique();
            $table->string('name');
            $table->string('model')->nullable();
            $table->string('ip_address')->nullable();
            $table->integer('port')->default(4370);
            $table->string('location')->nullable();
            $table->uuid('department_id')->nullable();

            $table->enum('status', ['online', 'offline', 'maintenance'])->default('offline');
            $table->timestamp('last_sync_at')->nullable();
            $table->integer('last_sync_records')->default(0);

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });

        // قواعد التحقق من الجدولة
        Schema::create('roster_validation_rules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();

            $table->string('rule_type'); // max_hours, min_rest, consecutive_days, etc.
            $table->json('parameters'); // {"max_hours_per_week": 48, etc.}

            $table->uuid('department_id')->nullable(); // null = ينطبق على الجميع
            $table->uuid('position_id')->nullable();

            $table->enum('severity', ['error', 'warning', 'info'])->default('warning');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
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
