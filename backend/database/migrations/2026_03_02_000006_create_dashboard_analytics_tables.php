<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الموديول الخامس: لوحة القيادة والتقارير (Dashboards & Analytics)
 *
 * يغطي هذا الملف:
 * 1. التقارير المحفوظة (saved_reports)
 * 2. لقطات إحصائية دورية (hr_snapshots)
 * 3. مؤشرات الأداء (kpi_definitions + kpi_values)
 * 4. تقارير مجدولة (scheduled_reports)
 * 5. تنبيهات لوحة القيادة (dashboard_alerts)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. التقارير المحفوظة (Saved Reports)
        // ─────────────────────────────────────────────
        Schema::create('saved_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                                // اسم التقرير
            $table->string('name_ar')->nullable();
            $table->enum('report_type', [
                'employee_list',           // قائمة الموظفين
                'employee_by_department',  // موظفون حسب الأقسام
                'employee_by_nationality', // موظفون حسب الجنسية
                'employee_by_position',    // موظفون حسب المسمى
                'leave_summary',           // ملخص إجازات
                'leave_conflicts',         // تعارض إجازات
                'current_on_leave',        // الموظفون في إجازة حالياً
                'upcoming_leaves',         // إجازات قادمة
                'contract_expiry',         // عقود توشك على الانتهاء
                'payroll_summary',         // ملخص رواتب
                'attendance_summary',      // ملخص حضور
                'turnover_rate',           // معدل دوران العمالة
                'absence_rate',            // معدل الغياب
                'headcount',              // عدد الموظفين
                'custom'                   // مخصص
            ]);
            $table->json('filters')->nullable();                   // فلاتر التقرير
            $table->json('columns')->nullable();                   // الأعمدة المختارة
            $table->json('sort_config')->nullable();               // ترتيب البيانات
            $table->boolean('is_public')->default(false);          // متاح للجميع
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index('report_type');
        });

        // ─────────────────────────────────────────────
        // 2. لقطات إحصائية دورية (HR Snapshots)
        // صورة يومية/شهرية لحالة الموارد البشرية
        // ─────────────────────────────────────────────
        Schema::create('hr_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->date('snapshot_date');                          // تاريخ اللقطة
            $table->enum('period_type', [
                'daily', 'weekly', 'monthly', 'quarterly', 'yearly'
            ]);

            // إحصائيات الموظفين
            $table->integer('total_employees')->default(0);        // إجمالي الموظفين
            $table->integer('active_employees')->default(0);       // الموظفون النشطون
            $table->integer('on_leave_count')->default(0);         // في إجازة
            $table->integer('new_hires')->default(0);              // تعيينات جديدة (خلال الفترة)
            $table->integer('terminations')->default(0);           // منتهي الخدمة
            $table->integer('resignations')->default(0);           // استقالات

            // إحصائيات حسب التصنيف (JSON)
            $table->json('by_department')->nullable();             // التوزيع حسب الأقسام
            $table->json('by_nationality')->nullable();            // التوزيع حسب الجنسية
            $table->json('by_position')->nullable();               // التوزيع حسب المسمى
            $table->json('by_gender')->nullable();                 // التوزيع حسب الجنس
            $table->json('by_employment_type')->nullable();        // التوزيع حسب نوع التوظيف

            // مؤشرات الإجازات
            $table->integer('pending_leave_requests')->default(0);
            $table->integer('approved_leave_requests')->default(0);
            $table->integer('rejected_leave_requests')->default(0);
            $table->json('leaves_by_type')->nullable();            // الإجازات حسب النوع

            // مؤشرات العقود
            $table->integer('expiring_contracts_30')->default(0);  // عقود تنتهي خلال 30 يوم
            $table->integer('expiring_contracts_60')->default(0);  // عقود تنتهي خلال 60 يوم
            $table->integer('expiring_contracts_90')->default(0);  // عقود تنتهي خلال 90 يوم

            // مؤشرات الأداء
            $table->decimal('absence_rate', 5, 2)->default(0);     // نسبة الغياب
            $table->decimal('turnover_rate', 5, 2)->default(0);    // معدل دوران العمالة
            $table->decimal('average_tenure_months', 8, 2)->default(0); // متوسط مدة الخدمة

            $table->timestamp('created_at');

            $table->unique(['snapshot_date', 'period_type']);
            $table->index('snapshot_date');
        });

        // ─────────────────────────────────────────────
        // 3. تعريفات مؤشرات الأداء (KPI Definitions)
        // ─────────────────────────────────────────────
        Schema::create('kpi_definitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_ar');
            $table->enum('category', [
                'hr',               // موارد بشرية
                'attendance',       // حضور
                'leave',            // إجازات
                'turnover',         // دوران عمالة
                'payroll',          // رواتب
                'compliance',       // التزام
                'performance'       // أداء
            ]);
            $table->text('description')->nullable();
            $table->string('unit');                                // وحدة القياس (%, عدد, أيام...)
            $table->decimal('target_value', 12, 2)->nullable();    // القيمة المستهدفة
            $table->decimal('warning_threshold', 12, 2)->nullable();// حد التحذير
            $table->decimal('critical_threshold', 12, 2)->nullable();// حد الخطورة
            $table->enum('direction', ['higher_is_better', 'lower_is_better'])->default('higher_is_better');
            $table->string('calculation_formula')->nullable();     // صيغة الحساب
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 4. قيم مؤشرات الأداء (KPI Values)
        // القيم الفعلية لكل مؤشر بشكل دوري
        // ─────────────────────────────────────────────
        Schema::create('kpi_values', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('kpi_definition_id');
            $table->uuid('department_id')->nullable();             // null = على مستوى المنشأة
            $table->date('period_date');                             // تاريخ الفترة
            $table->enum('period_type', ['monthly', 'quarterly', 'yearly']);
            $table->decimal('actual_value', 12, 2);                 // القيمة الفعلية
            $table->decimal('target_value', 12, 2)->nullable();     // القيمة المستهدفة
            $table->decimal('previous_value', 12, 2)->nullable();   // القيمة السابقة
            $table->decimal('change_percentage', 8, 2)->nullable(); // نسبة التغيير
            $table->enum('status', [
                'on_target',       // ضمن الهدف
                'warning',         // تحذير
                'critical',        // حرج
                'no_target'        // بدون هدف
            ])->default('no_target');
            $table->json('breakdown')->nullable();                  // تفصيل القيمة
            $table->timestamp('created_at');

            $table->foreign('kpi_definition_id')->references('id')->on('kpi_definitions');
            $table->foreign('department_id')->references('id')->on('departments');

            $table->unique(['kpi_definition_id', 'department_id', 'period_date', 'period_type'], 'kpi_values_unique');
            $table->index(['period_date', 'period_type']);
        });

        // ─────────────────────────────────────────────
        // 5. التقارير المجدولة (Scheduled Reports)
        // تقارير تصدر تلقائياً
        // ─────────────────────────────────────────────
        Schema::create('scheduled_reports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('saved_report_id')->nullable();
            $table->string('name');
            $table->enum('frequency', [
                'daily', 'weekly', 'biweekly', 'monthly', 'quarterly', 'yearly'
            ]);
            $table->enum('export_format', ['pdf', 'csv', 'excel']);
            $table->json('recipients');                             // قائمة المستلمين (email / user_id)
            $table->boolean('send_email')->default(true);
            $table->time('send_at')->default('08:00:00');          // وقت الإرسال
            $table->integer('day_of_week')->nullable();            // يوم الأسبوع (للأسبوعي)
            $table->integer('day_of_month')->nullable();           // يوم الشهر (للشهري)
            $table->timestamp('last_sent_at')->nullable();
            $table->timestamp('next_send_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('saved_report_id')->references('id')->on('saved_reports')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
        });

        // ─────────────────────────────────────────────
        // 6. تنبيهات لوحة القيادة (Dashboard Alerts)
        // تنبيهات ذكية يولدها النظام
        // ─────────────────────────────────────────────
        Schema::create('dashboard_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('alert_type', [
                'contract_expiry',         // انتهاء عقد
                'document_expiry',         // انتهاء مستند
                'leave_conflict',          // تعارض إجازات
                'low_staff',               // نقص موظفين
                'high_absence',            // غياب مرتفع
                'high_turnover',           // دوران عمالة مرتفع
                'kpi_warning',             // تحذير مؤشر أداء
                'payroll_due',             // موعد الرواتب
                'probation_end',           // انتهاء فترة تجربة
                'system'                   // تنبيه نظام
            ]);
            $table->enum('severity', [
                'info', 'warning', 'critical'
            ])->default('info');
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->text('message');
            $table->text('message_ar')->nullable();
            $table->string('action_url')->nullable();
            $table->json('related_data')->nullable();              // بيانات مرتبطة (IDs)
            $table->boolean('is_read')->default(false);
            $table->boolean('is_resolved')->default(false);
            $table->uuid('resolved_by')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->json('target_roles')->nullable();              // الأدوار المستهدفة
            $table->json('target_users')->nullable();              // المستخدمون المستهدفون
            $table->timestamps();

            $table->foreign('resolved_by')->references('id')->on('users');
            $table->index(['alert_type', 'is_resolved']);
            $table->index('severity');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dashboard_alerts');
        Schema::dropIfExists('scheduled_reports');
        Schema::dropIfExists('kpi_values');
        Schema::dropIfExists('kpi_definitions');
        Schema::dropIfExists('hr_snapshots');
        Schema::dropIfExists('saved_reports');
    }
};
