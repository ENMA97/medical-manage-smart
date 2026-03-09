<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. جدول المخالفات حسب نظام العمل السعودي
        // Violation Types (Saudi Labor Law Reference)
        // ─────────────────────────────────────────────
        Schema::create('violation_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code', 20)->unique();                     // رمز المخالفة
            $table->string('name');                                     // اسم المخالفة EN
            $table->string('name_ar');                                  // اسم المخالفة AR
            $table->string('category');                                 // التصنيف
            $table->string('category_ar');                              // التصنيف AR
            $table->text('description')->nullable();                    // وصف EN
            $table->text('description_ar')->nullable();                 // وصف AR
            $table->string('labor_law_article')->nullable();           // مادة نظام العمل
            $table->string('severity')->default('minor');              // minor, moderate, major, critical
            $table->json('penalties')->nullable();                      // العقوبات التدريجية (أول مرة، ثاني، ثالث، رابع)
            $table->boolean('requires_investigation')->default(false); // يتطلب تحقيق
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 2. المخالفات المسجلة على الموظفين
        // Violations / Disciplinary Records
        // ─────────────────────────────────────────────
        Schema::create('violations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('violation_number', 30)->unique();          // رقم المخالفة
            $table->uuid('employee_id');                                // الموظف المخالف
            $table->uuid('violation_type_id');                          // نوع المخالفة
            $table->date('violation_date');                              // تاريخ المخالفة
            $table->time('violation_time')->nullable();                 // وقت المخالفة
            $table->string('location')->nullable();                     // مكان المخالفة
            $table->text('description');                                 // وصف المخالفة
            $table->text('description_ar')->nullable();                 // وصف بالعربي
            $table->integer('occurrence_number')->default(1);          // رقم التكرار (أول، ثاني...)
            $table->string('status')->default('reported');             // reported, under_investigation, decided, appealed, closed
            $table->uuid('reported_by');                                 // المبلّغ
            $table->json('evidence')->nullable();                       // المرفقات/الأدلة
            $table->json('witnesses')->nullable();                      // الشهود
            $table->text('employee_statement')->nullable();             // إفادة الموظف
            $table->text('employee_statement_ar')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('violation_type_id')->references('id')->on('violation_types');
            $table->foreign('reported_by')->references('id')->on('users');
        });

        // ─────────────────────────────────────────────
        // 3. لجان التحقيق
        // Investigation Committees
        // ─────────────────────────────────────────────
        Schema::create('investigation_committees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('committee_number', 30)->unique();          // رقم اللجنة
            $table->string('name');                                     // اسم اللجنة EN
            $table->string('name_ar');                                  // اسم اللجنة AR
            $table->uuid('violation_id');                               // المخالفة المرتبطة
            $table->uuid('chairman_id');                                // رئيس اللجنة
            $table->date('formation_date');                              // تاريخ التشكيل
            $table->date('deadline')->nullable();                       // الموعد النهائي
            $table->string('status')->default('formed');               // formed, in_progress, completed, dissolved
            $table->text('mandate')->nullable();                        // صلاحيات اللجنة
            $table->text('mandate_ar')->nullable();
            $table->uuid('formed_by');                                  // مُشكّل اللجنة
            $table->timestamps();

            $table->foreign('violation_id')->references('id')->on('violations')->cascadeOnDelete();
            $table->foreign('chairman_id')->references('id')->on('employees');
            $table->foreign('formed_by')->references('id')->on('users');
        });

        // ─────────────────────────────────────────────
        // 4. أعضاء لجنة التحقيق
        // Committee Members
        // ─────────────────────────────────────────────
        Schema::create('committee_members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('committee_id');
            $table->uuid('employee_id');
            $table->string('role')->default('member');                 // chairman, member, secretary, observer
            $table->string('role_ar')->nullable();
            $table->timestamps();

            $table->foreign('committee_id')->references('id')->on('investigation_committees')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->unique(['committee_id', 'employee_id']);
        });

        // ─────────────────────────────────────────────
        // 5. جلسات التحقيق
        // Investigation Sessions
        // ─────────────────────────────────────────────
        Schema::create('investigation_sessions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('committee_id');
            $table->integer('session_number');                          // رقم الجلسة
            $table->dateTime('session_date');                            // تاريخ ووقت الجلسة
            $table->string('location')->nullable();                     // مكان الجلسة
            $table->text('agenda')->nullable();                         // جدول الأعمال
            $table->text('agenda_ar')->nullable();
            $table->text('minutes')->nullable();                        // محضر الجلسة
            $table->text('minutes_ar')->nullable();
            $table->text('employee_response')->nullable();             // رد الموظف
            $table->text('employee_response_ar')->nullable();
            $table->boolean('employee_attended')->default(false);      // حضر الموظف؟
            $table->string('employee_absence_reason')->nullable();     // سبب الغياب
            $table->json('attendees')->nullable();                      // الحضور
            $table->json('attachments')->nullable();                    // مرفقات الجلسة
            $table->string('status')->default('scheduled');            // scheduled, completed, postponed, cancelled
            $table->timestamps();

            $table->foreign('committee_id')->references('id')->on('investigation_committees')->cascadeOnDelete();
        });

        // ─────────────────────────────────────────────
        // 6. القرارات التأديبية
        // Disciplinary Decisions
        // ─────────────────────────────────────────────
        Schema::create('disciplinary_decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('decision_number', 30)->unique();           // رقم القرار
            $table->uuid('violation_id');                               // المخالفة
            $table->uuid('committee_id')->nullable();                   // اللجنة (إن وجدت)
            $table->uuid('employee_id');                                // الموظف
            $table->string('penalty_type');                             // نوع العقوبة
            $table->string('penalty_type_ar');                          // نوع العقوبة AR
            $table->text('penalty_details')->nullable();               // تفاصيل العقوبة
            $table->text('penalty_details_ar')->nullable();
            $table->decimal('deduction_amount', 10, 2)->nullable();    // مبلغ الخصم (إن وجد)
            $table->integer('deduction_days')->nullable();             // أيام الخصم
            $table->integer('suspension_days')->nullable();            // أيام الإيقاف
            $table->date('effective_date');                              // تاريخ سريان القرار
            $table->date('end_date')->nullable();                       // تاريخ انتهاء (للإيقاف)
            $table->text('justification');                               // المبررات
            $table->text('justification_ar')->nullable();
            $table->string('labor_law_reference')->nullable();         // مرجع نظام العمل
            $table->string('suggested_penalty')->nullable();           // العقوبة المقترحة تلقائياً
            $table->string('suggested_penalty_ar')->nullable();
            $table->string('status')->default('draft');                // draft, issued, notified, acknowledged, appealed, final
            $table->uuid('decided_by');                                 // مصدر القرار
            $table->dateTime('decided_at')->nullable();
            $table->uuid('approved_by')->nullable();                   // معتمد القرار
            $table->dateTime('approved_at')->nullable();
            $table->dateTime('notified_at')->nullable();               // تاريخ التبليغ
            $table->boolean('employee_acknowledged')->default(false); // اطلع الموظف
            $table->dateTime('acknowledged_at')->nullable();
            $table->text('appeal_text')->nullable();                    // نص التظلم
            $table->dateTime('appeal_date')->nullable();
            $table->string('appeal_status')->nullable();               // pending, accepted, rejected
            $table->text('appeal_result')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('violation_id')->references('id')->on('violations');
            $table->foreign('committee_id')->references('id')->on('investigation_committees')->nullOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('decided_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('disciplinary_decisions');
        Schema::dropIfExists('investigation_sessions');
        Schema::dropIfExists('committee_members');
        Schema::dropIfExists('investigation_committees');
        Schema::dropIfExists('violations');
        Schema::dropIfExists('violation_types');
    }
};
