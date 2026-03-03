<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الموديول الرابع: أتمتة نظام الإجازات (Leave Management System)
 *
 * يغطي هذا الملف:
 * 1. أنواع الإجازات (leave_types)
 * 2. أرصدة الإجازات (leave_balances)
 * 3. طلبات الإجازات (leave_requests)
 * 4. مرفقات طلبات الإجازات (leave_attachments)
 * 5. مصفوفة الاعتمادات (leave_approvals) - سلسلة الموافقات
 * 6. إعدادات مصفوفة الاعتمادات (approval_matrix_settings)
 * 7. سجل رصيد الإجازات (leave_balance_transactions)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. أنواع الإجازات (Leave Types)
        // ─────────────────────────────────────────────
        Schema::create('leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();                      // رمز نوع الإجازة
            $table->string('name');                                 // اسم النوع (EN)
            $table->string('name_ar');                              // اسم النوع (AR)
            $table->enum('category', [
                'annual',           // سنوية
                'sick',             // مرضية
                'emergency',        // اضطرارية
                'maternity',        // أمومة
                'paternity',        // أبوة
                'bereavement',      // وفاة
                'marriage',         // زواج
                'hajj',             // حج
                'unpaid',           // بدون راتب
                'compensatory',     // تعويضية
                'study',            // دراسية
                'other'             // أخرى
            ]);
            $table->integer('default_days_per_year')->nullable();  // الأيام الافتراضية سنوياً
            $table->integer('max_days_per_request')->nullable();   // الحد الأقصى لكل طلب
            $table->integer('min_days_per_request')->default(1);   // الحد الأدنى لكل طلب
            $table->boolean('is_paid')->default(true);             // مدفوعة الراتب
            $table->decimal('pay_percentage', 5, 2)->default(100); // نسبة الراتب (100% = كامل)
            $table->boolean('requires_attachment')->default(false);// تتطلب مرفق
            $table->boolean('requires_substitute')->default(false);// تتطلب بديل
            $table->integer('advance_notice_days')->default(0);    // مدة الإشعار المسبق (أيام)
            $table->boolean('carries_forward')->default(false);    // ترحيل الرصيد
            $table->integer('max_carry_forward_days')->nullable(); // أقصى أيام مرحّلة
            $table->boolean('is_active')->default(true);
            $table->text('description')->nullable();
            $table->text('policy_notes')->nullable();              // ملاحظات السياسة
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 2. أرصدة الإجازات (Leave Balances)
        // رصيد كل موظف لكل نوع إجازة سنوياً
        // ─────────────────────────────────────────────
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('leave_type_id');
            $table->integer('year');                               // السنة
            $table->decimal('total_entitled', 8, 2);               // الرصيد المستحق
            $table->decimal('carried_forward', 8, 2)->default(0);  // المرحّل من السنة السابقة
            $table->decimal('additional_granted', 8, 2)->default(0);// أيام إضافية ممنوحة
            $table->decimal('used', 8, 2)->default(0);             // المستخدم
            $table->decimal('pending', 8, 2)->default(0);          // طلبات معلقة
            $table->decimal('remaining', 8, 2);                     // الرصيد المتبقي
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('leave_type_id')->references('id')->on('leave_types');

            $table->unique(['employee_id', 'leave_type_id', 'year']);
            $table->index(['employee_id', 'year']);
        });

        // ─────────────────────────────────────────────
        // 3. طلبات الإجازات (Leave Requests)
        // مسار العمل الرقمي الكامل
        // ─────────────────────────────────────────────
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_number')->unique();            // رقم الطلب (تلقائي)
            $table->uuid('employee_id');                            // الموظف مقدم الطلب
            $table->uuid('leave_type_id');                          // نوع الإجازة
            $table->uuid('leave_balance_id')->nullable();          // ربط بالرصيد

            // التواريخ
            $table->date('start_date');                              // تاريخ بداية الإجازة
            $table->date('end_date');                                // تاريخ نهاية الإجازة
            $table->integer('total_days');                           // عدد أيام الإجازة
            $table->date('resume_date')->nullable();                // تاريخ العودة المتوقع

            // البديل
            $table->uuid('substitute_employee_id')->nullable();    // الموظف البديل
            $table->boolean('substitute_approved')->default(false);// موافقة البديل

            // التفاصيل
            $table->text('reason')->nullable();                     // سبب الإجازة
            $table->text('reason_ar')->nullable();
            $table->string('contact_during_leave')->nullable();    // رقم التواصل أثناء الإجازة
            $table->text('address_during_leave')->nullable();      // العنوان أثناء الإجازة

            // الحالة العامة للطلب
            $table->enum('status', [
                'draft',                   // مسودة
                'submitted',               // مقدم
                'pending_substitute',      // بانتظار موافقة البديل
                'pending_supervisor',      // بانتظار المشرف المباشر
                'pending_hr',              // بانتظار الموارد البشرية
                'pending_admin_manager',   // بانتظار المدير الإداري
                'pending_general_manager', // بانتظار المدير العام
                'approved',                // معتمد نهائياً
                'rejected',                // مرفوض
                'cancelled',               // ملغي (بواسطة الموظف)
                'in_progress',             // جارية (الموظف في إجازة)
                'completed',               // منتهية (الموظف عاد)
                'cut_short'                // مقطوعة (عودة مبكرة)
            ])->default('draft');

            // المرحلة الحالية في مصفوفة الاعتماد
            $table->integer('current_approval_step')->default(0);
            $table->integer('total_approval_steps')->default(5);

            $table->uuid('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->text('cancellation_reason')->nullable();
            $table->date('actual_return_date')->nullable();        // تاريخ العودة الفعلي
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('leave_type_id')->references('id')->on('leave_types');
            $table->foreign('leave_balance_id')->references('id')->on('leave_balances');
            $table->foreign('substitute_employee_id')->references('id')->on('employees');
            $table->foreign('cancelled_by')->references('id')->on('users');

            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('status');
            $table->index('substitute_employee_id');
        });

        // ─────────────────────────────────────────────
        // 4. مرفقات طلبات الإجازات (Leave Attachments)
        // مستندات داعمة (مثل موعد الاستقدام، تقرير طبي...)
        // ─────────────────────────────────────────────
        Schema::create('leave_attachments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leave_request_id');
            $table->string('file_name');                           // اسم الملف الأصلي
            $table->string('file_path');                            // مسار التخزين
            $table->string('mime_type');
            $table->bigInteger('file_size');
            $table->string('description')->nullable();
            $table->uuid('uploaded_by');
            $table->timestamps();

            $table->foreign('leave_request_id')->references('id')->on('leave_requests')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users');
        });

        // ─────────────────────────────────────────────
        // 5. مصفوفة الاعتمادات (Leave Approvals)
        // سلسلة الموافقات التسلسلية لكل طلب إجازة
        //
        // الترتيب الافتراضي:
        // 1. الموظف البديل (substitute)
        // 2. المشرف المباشر / المدير الطبي (supervisor)
        // 3. الموارد البشرية (hr)
        // 4. المدير الإداري (admin_manager)
        // 5. المدير العام (general_manager)
        // ─────────────────────────────────────────────
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leave_request_id');
            $table->integer('step_order');                         // ترتيب الخطوة (1, 2, 3, 4, 5)
            $table->enum('approval_role', [
                'substitute',          // الموظف البديل
                'supervisor',          // المشرف المباشر
                'medical_director',    // المدير الطبي (للأطباء)
                'hr',                  // الموارد البشرية
                'admin_manager',       // المدير الإداري
                'general_manager'      // المدير العام
            ]);
            $table->uuid('approver_id')->nullable();              // المعتمد (المستخدم)
            $table->enum('status', [
                'pending',             // بانتظار
                'approved',            // موافق
                'rejected',            // مرفوض
                'skipped',             // تم تخطيه
                'delegated'            // مفوّض
            ])->default('pending');
            $table->text('comment')->nullable();                   // ملاحظات المعتمد
            $table->text('comment_ar')->nullable();
            $table->timestamp('actioned_at')->nullable();          // وقت اتخاذ القرار

            // بيانات إضافية (لقسم HR)
            $table->decimal('balance_before', 8, 2)->nullable();   // الرصيد قبل الطلب
            $table->decimal('balance_after', 8, 2)->nullable();    // الرصيد بعد الموافقة
            $table->boolean('balance_sufficient')->nullable();     // هل الرصيد كافٍ

            // التفويض
            $table->uuid('delegated_to')->nullable();              // مفوّض إلى
            $table->uuid('delegated_by')->nullable();              // مفوّض بواسطة

            $table->timestamps();

            $table->foreign('leave_request_id')->references('id')->on('leave_requests')->cascadeOnDelete();
            $table->foreign('approver_id')->references('id')->on('users');
            $table->foreign('delegated_to')->references('id')->on('users');
            $table->foreign('delegated_by')->references('id')->on('users');

            $table->unique(['leave_request_id', 'step_order']);
            $table->index(['approver_id', 'status']);
            $table->index('status');
        });

        // ─────────────────────────────────────────────
        // 6. إعدادات مصفوفة الاعتمادات (Approval Matrix Settings)
        // تكوين سلسلة الموافقات حسب القسم أو نوع الإجازة
        // ─────────────────────────────────────────────
        Schema::create('approval_matrix_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('department_id')->nullable();             // null = ينطبق على الكل
            $table->uuid('leave_type_id')->nullable();             // null = ينطبق على الكل
            $table->uuid('position_id')->nullable();               // null = ينطبق على الكل
            $table->integer('step_order');                          // ترتيب الخطوة
            $table->enum('approval_role', [
                'substitute',
                'supervisor',
                'medical_director',
                'hr',
                'admin_manager',
                'general_manager'
            ]);
            $table->boolean('is_required')->default(true);         // خطوة إلزامية
            $table->boolean('can_skip_if_absent')->default(false); // تخطي إذا غائب
            $table->integer('auto_approve_after_hours')->nullable(); // موافقة تلقائية بعد X ساعة
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('leave_type_id')->references('id')->on('leave_types');
            $table->foreign('position_id')->references('id')->on('positions');

            $table->index(['department_id', 'leave_type_id']);
        });

        // ─────────────────────────────────────────────
        // 7. سجل حركات رصيد الإجازات (Leave Balance Transactions)
        // سجل غير قابل للتعديل - يوثق كل تغيير في الرصيد
        // ─────────────────────────────────────────────
        Schema::create('leave_balance_transactions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leave_balance_id');
            $table->uuid('leave_request_id')->nullable();
            $table->enum('transaction_type', [
                'initial_allocation',   // تخصيص أولي
                'carry_forward',        // ترحيل
                'additional_grant',     // منح إضافي
                'deduction',            // خصم (إجازة معتمدة)
                'restoration',          // استرداد (إلغاء إجازة)
                'adjustment',           // تعديل يدوي
                'expiry'                // انتهاء صلاحية
            ]);
            $table->decimal('days', 8, 2);                         // عدد الأيام (+ إضافة / - خصم)
            $table->decimal('balance_before', 8, 2);               // الرصيد قبل
            $table->decimal('balance_after', 8, 2);                // الرصيد بعد
            $table->text('description')->nullable();
            $table->uuid('performed_by');
            $table->timestamp('created_at');
            // لا يوجد updated_at - السجل غير قابل للتعديل

            $table->foreign('leave_balance_id')->references('id')->on('leave_balances');
            $table->foreign('leave_request_id')->references('id')->on('leave_requests');
            $table->foreign('performed_by')->references('id')->on('users');

            $table->index(['leave_balance_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balance_transactions');
        Schema::dropIfExists('approval_matrix_settings');
        Schema::dropIfExists('leave_approvals');
        Schema::dropIfExists('leave_attachments');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
