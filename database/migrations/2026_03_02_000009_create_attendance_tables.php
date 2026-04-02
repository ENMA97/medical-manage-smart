<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الموديول التاسع: الحضور والانصراف (Attendance & Time Tracking)
 *
 * يغطي هذا الملف:
 * 1. ورديات العمل (work_shifts)
 * 2. جداول الدوام (work_schedules)
 * 3. تعيين الموظفين لجداول الدوام (employee_schedules)
 * 4. سجلات الحضور والانصراف (attendance_records)
 * 5. طلبات العمل الإضافي (overtime_requests)
 * 6. الأذونات (permission_requests)
 * 7. ملخصات الحضور الشهرية (attendance_summaries)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. ورديات العمل (Work Shifts)
        // تعريف أوقات العمل (صباحي، مسائي، ليلي، مرن)
        // ─────────────────────────────────────────────
        Schema::create('work_shifts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                                    // اسم الوردية (EN)
            $table->string('name_ar');                                 // اسم الوردية (AR)
            $table->string('code')->unique();                          // رمز الوردية
            $table->enum('type', [
                'fixed',          // ثابت
                'flexible',       // مرن
                'rotating',       // متناوب
                'split'           // منقسم
            ])->default('fixed');
            $table->time('start_time');                                // وقت بداية العمل
            $table->time('end_time');                                  // وقت نهاية العمل
            $table->time('break_start')->nullable();                   // بداية الاستراحة
            $table->time('break_end')->nullable();                     // نهاية الاستراحة
            $table->decimal('break_duration_minutes', 5, 2)->default(0); // مدة الاستراحة بالدقائق
            $table->decimal('total_hours', 5, 2);                      // إجمالي ساعات العمل
            $table->integer('grace_period_minutes')->default(15);      // فترة السماح بالدقائق
            $table->integer('early_departure_minutes')->default(15);   // سماح الانصراف المبكر
            $table->boolean('next_day_end')->default(false);           // الوردية تنتهي في اليوم التالي
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 2. جداول الدوام (Work Schedules)
        // تعريف أنماط العمل الأسبوعية
        // ─────────────────────────────────────────────
        Schema::create('work_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                                    // اسم الجدول (EN)
            $table->string('name_ar');                                 // اسم الجدول (AR)
            $table->enum('type', [
                'weekly',         // أسبوعي ثابت
                'rotating',       // متناوب
                'custom'          // مخصص
            ])->default('weekly');
            $table->integer('rotation_days')->nullable();              // أيام الدورة (للمتناوب)
            $table->json('weekly_pattern')->nullable();                // نمط أيام العمل الأسبوعي
            // مثال: {"sunday": "shift_uuid", "monday": "shift_uuid", "friday": null}
            $table->boolean('is_default')->default(false);             // جدول الدوام الافتراضي
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 3. تعيين الموظفين لجداول الدوام (Employee Schedules)
        // ─────────────────────────────────────────────
        Schema::create('employee_schedules', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('work_schedule_id');
            $table->date('effective_from');                            // تاريخ بداية السريان
            $table->date('effective_to')->nullable();                  // تاريخ نهاية السريان
            $table->boolean('is_current')->default(true);              // الجدول الحالي
            $table->uuid('assigned_by')->nullable();                   // من قام بالتعيين
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('work_schedule_id')->references('id')->on('work_schedules')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['employee_id', 'is_current']);
        });

        // ─────────────────────────────────────────────
        // 4. سجلات الحضور والانصراف (Attendance Records)
        // تسجيل دخول/خروج الموظفين يومياً
        // ─────────────────────────────────────────────
        Schema::create('attendance_records', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->date('date');                                      // تاريخ اليوم
            $table->uuid('work_shift_id')->nullable();                 // الوردية المطبقة
            $table->enum('status', [
                'present',        // حاضر
                'absent',         // غائب
                'late',           // متأخر
                'early_departure',// انصراف مبكر
                'late_and_early', // تأخر + انصراف مبكر
                'on_leave',       // في إجازة
                'holiday',        // إجازة رسمية
                'day_off',        // يوم راحة
                'business_trip',  // مأمورية عمل
                'work_from_home'  // عمل من المنزل
            ])->default('absent');

            // أوقات الحضور
            $table->timestamp('check_in')->nullable();                 // وقت الحضور
            $table->timestamp('check_out')->nullable();                // وقت الانصراف
            $table->decimal('actual_hours', 5, 2)->nullable();         // ساعات العمل الفعلية
            $table->decimal('overtime_hours', 5, 2)->default(0);       // ساعات العمل الإضافي
            $table->integer('late_minutes')->default(0);               // دقائق التأخير
            $table->integer('early_departure_minutes')->default(0);    // دقائق الانصراف المبكر

            // طريقة التسجيل
            $table->enum('check_in_method', [
                'biometric',      // بصمة
                'card',           // بطاقة
                'mobile_gps',     // جوال + GPS
                'web',            // عبر الويب
                'manual',         // يدوي
                'qr_code'         // رمز QR
            ])->nullable();
            $table->enum('check_out_method', [
                'biometric', 'card', 'mobile_gps', 'web', 'manual', 'qr_code'
            ])->nullable();

            // بيانات الموقع (GPS)
            $table->decimal('check_in_latitude', 10, 7)->nullable();
            $table->decimal('check_in_longitude', 10, 7)->nullable();
            $table->decimal('check_out_latitude', 10, 7)->nullable();
            $table->decimal('check_out_longitude', 10, 7)->nullable();

            // ملاحظات وتعديلات
            $table->text('notes')->nullable();
            $table->boolean('is_manually_adjusted')->default(false);   // تم تعديله يدوياً
            $table->uuid('adjusted_by')->nullable();                   // من قام بالتعديل
            $table->text('adjustment_reason')->nullable();             // سبب التعديل
            $table->uuid('leave_request_id')->nullable();              // مرتبط بطلب إجازة
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('work_shift_id')->references('id')->on('work_shifts')->nullOnDelete();
            $table->foreign('adjusted_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('leave_request_id')->references('id')->on('leave_requests')->nullOnDelete();
            $table->unique(['employee_id', 'date']);
            $table->index(['date', 'status']);
            $table->index(['employee_id', 'date']);
        });

        // ─────────────────────────────────────────────
        // 5. طلبات العمل الإضافي (Overtime Requests)
        // ─────────────────────────────────────────────
        Schema::create('overtime_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_number')->unique();                // رقم الطلب
            $table->uuid('employee_id');
            $table->date('date');                                      // تاريخ العمل الإضافي
            $table->time('start_time');                                // وقت البدء
            $table->time('end_time');                                  // وقت الانتهاء
            $table->decimal('hours', 5, 2);                            // عدد الساعات
            $table->decimal('rate_multiplier', 3, 2)->default(1.50);   // معامل الأجر (1.5x, 2x)
            $table->text('reason');                                    // سبب العمل الإضافي
            $table->text('reason_ar')->nullable();
            $table->enum('status', [
                'pending',        // بانتظار الموافقة
                'approved',       // معتمد
                'rejected',       // مرفوض
                'cancelled'       // ملغي
            ])->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['employee_id', 'date']);
        });

        // ─────────────────────────────────────────────
        // 6. الأذونات / الاستئذانات (Permission Requests)
        // طلبات الخروج أثناء الدوام
        // ─────────────────────────────────────────────
        Schema::create('permission_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_number')->unique();                // رقم الطلب
            $table->uuid('employee_id');
            $table->date('date');                                      // تاريخ الإذن
            $table->time('departure_time');                            // وقت المغادرة
            $table->time('return_time');                               // وقت العودة المتوقع
            $table->time('actual_return_time')->nullable();            // وقت العودة الفعلي
            $table->decimal('hours', 5, 2);                            // عدد ساعات الإذن
            $table->enum('type', [
                'personal',       // شخصي
                'medical',        // طبي
                'official',       // رسمي
                'family'          // عائلي
            ])->default('personal');
            $table->text('reason');
            $table->text('reason_ar')->nullable();
            $table->enum('status', [
                'pending',        // بانتظار الموافقة
                'approved',       // معتمد
                'rejected',       // مرفوض
                'cancelled'       // ملغي
            ])->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->decimal('monthly_total_hours', 5, 2)->default(0);  // إجمالي ساعات الشهر
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['employee_id', 'date']);
        });

        // ─────────────────────────────────────────────
        // 7. ملخصات الحضور الشهرية (Attendance Summaries)
        // إحصائيات مجمّعة للحضور شهرياً
        // ─────────────────────────────────────────────
        Schema::create('attendance_summaries', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->integer('month');
            $table->integer('year');
            $table->integer('working_days')->default(0);               // أيام العمل المطلوبة
            $table->integer('present_days')->default(0);               // أيام الحضور
            $table->integer('absent_days')->default(0);                // أيام الغياب
            $table->integer('late_days')->default(0);                  // أيام التأخر
            $table->integer('early_departure_days')->default(0);       // أيام الانصراف المبكر
            $table->integer('leave_days')->default(0);                 // أيام الإجازة
            $table->integer('holiday_days')->default(0);               // أيام الإجازات الرسمية
            $table->integer('day_off_days')->default(0);               // أيام الراحة
            $table->decimal('total_working_hours', 8, 2)->default(0);  // إجمالي ساعات العمل
            $table->decimal('total_overtime_hours', 8, 2)->default(0); // إجمالي ساعات العمل الإضافي
            $table->integer('total_late_minutes')->default(0);         // إجمالي دقائق التأخير
            $table->integer('total_early_minutes')->default(0);        // إجمالي دقائق الانصراف المبكر
            $table->decimal('total_permission_hours', 5, 2)->default(0); // إجمالي ساعات الأذونات
            $table->decimal('attendance_rate', 5, 2)->default(0);      // نسبة الحضور %
            $table->decimal('deduction_amount', 14, 2)->default(0);    // مبلغ الخصم
            $table->decimal('overtime_amount', 14, 2)->default(0);     // مبلغ العمل الإضافي
            $table->boolean('is_finalized')->default(false);           // تم اعتماد الملخص
            $table->uuid('finalized_by')->nullable();
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('finalized_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['employee_id', 'month', 'year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendance_summaries');
        Schema::dropIfExists('permission_requests');
        Schema::dropIfExists('overtime_requests');
        Schema::dropIfExists('attendance_records');
        Schema::dropIfExists('employee_schedules');
        Schema::dropIfExists('work_schedules');
        Schema::dropIfExists('work_shifts');
    }
};
