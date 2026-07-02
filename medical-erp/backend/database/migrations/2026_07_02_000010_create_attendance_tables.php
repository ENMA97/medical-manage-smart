<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الموديول العاشر: الحضور والانصراف وإدارة الورديات (Attendance & Shift Management)
 *
 * يغطي هذا الملف:
 * 1. الورديات (shifts) - تعريف ورديات العمل (صباحية/مسائية/ليلية...)
 * 2. إسناد الورديات (shift_assignments) - ربط الموظفين بالورديات
 * 3. سجلات الحضور (attendance_records) - تسجيل الحضور والانصراف اليومي
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. الورديات (Shifts)
        // ─────────────────────────────────────────────
        Schema::create('shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();                       // رمز الوردية
            $table->string('name');                                  // اسم الوردية (EN)
            $table->string('name_ar');                               // اسم الوردية (AR)
            $table->time('start_time');                              // وقت البداية
            $table->time('end_time');                                // وقت النهاية
            $table->integer('break_minutes')->default(0);            // مدة الاستراحة (دقائق)
            $table->integer('grace_period_minutes')->default(15);    // سماحية التأخير (دقائق)
            $table->boolean('is_overnight')->default(false);         // وردية ليلية تمتد لليوم التالي
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 2. إسناد الورديات (Shift Assignments)
        // إسناد موظف إلى وردية خلال فترة زمنية
        // ─────────────────────────────────────────────
        Schema::create('shift_assignments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('shift_id');
            $table->date('effective_from');                          // بداية سريان الإسناد
            $table->date('effective_to')->nullable();                // نهاية السريان (null = مفتوح)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('shift_id')->references('id')->on('shifts');
            $table->index(['employee_id', 'effective_from']);
        });

        // ─────────────────────────────────────────────
        // 3. سجلات الحضور (Attendance Records)
        // سجل واحد لكل موظف لكل يوم
        // ─────────────────────────────────────────────
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('shift_id')->nullable();                    // الوردية المطبقة يوم التسجيل
            $table->date('attendance_date');                         // تاريخ اليوم
            $table->dateTime('check_in_time')->nullable();           // وقت الحضور
            $table->dateTime('check_out_time')->nullable();          // وقت الانصراف
            $table->enum('status', [
                'present',      // حاضر
                'late',         // متأخر
                'absent',       // غائب
                'on_leave',     // في إجازة
                'holiday',      // عطلة رسمية
                'mission',      // مهمة عمل خارجية
            ])->default('present');
            $table->integer('late_minutes')->default(0);             // دقائق التأخير
            $table->integer('early_leave_minutes')->default(0);      // دقائق الانصراف المبكر
            $table->integer('overtime_minutes')->default(0);         // دقائق العمل الإضافي
            $table->integer('worked_minutes')->default(0);           // دقائق العمل الفعلية
            $table->enum('source', ['self', 'manual', 'import'])->default('manual'); // مصدر التسجيل
            $table->text('notes')->nullable();
            $table->uuid('recorded_by')->nullable();                 // من قام بالتسجيل اليدوي
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('shift_id')->references('id')->on('shifts');
            $table->foreign('recorded_by')->references('id')->on('users');
            $table->unique(['employee_id', 'attendance_date']);
            $table->index('attendance_date');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('shift_assignments');
        Schema::dropIfExists('shifts');
    }
};
