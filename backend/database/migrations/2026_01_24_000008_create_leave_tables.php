<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // أنواع الإجازات
        Schema::create('leave_types', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique(); // annual, sick, emergency, etc.
            $table->string('name');
            $table->string('name_ar');
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->enum('category', [
                'annual',           // سنوية
                'sick',             // مرضية
                'emergency',        // طارئة
                'unpaid',           // بدون راتب
                'maternity',        // أمومة
                'paternity',        // أبوة
                'hajj',             // حج
                'marriage',         // زواج
                'bereavement',      // وفاة
                'study',            // دراسية
                'compensatory',     // تعويضية
                'other'             // أخرى
            ]);
            $table->integer('default_days_per_year')->default(0);
            $table->boolean('is_paid')->default(true);
            $table->boolean('requires_attachment')->default(false); // مرفقات مطلوبة (تقرير طبي مثلاً)
            $table->boolean('requires_hr_approval')->default(true);
            $table->boolean('requires_manager_approval')->default(true);
            $table->integer('min_days')->default(1);
            $table->integer('max_days')->nullable();
            $table->integer('advance_notice_days')->default(0); // أيام الإشعار المسبق
            $table->boolean('can_be_carried_over')->default(false); // ترحيل للسنة التالية
            $table->integer('max_carry_over_days')->default(0);
            $table->json('applicable_contract_types')->nullable(); // أنواع العقود المؤهلة
            $table->json('gender_restriction')->nullable(); // ['male'], ['female'], or null for both
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // أرصدة الإجازات
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('leave_type_id');
            $table->integer('year');
            $table->decimal('entitled_days', 5, 2)->default(0);      // الرصيد المستحق
            $table->decimal('carried_over_days', 5, 2)->default(0);  // المرحل من السنة السابقة
            $table->decimal('additional_days', 5, 2)->default(0);    // أيام إضافية ممنوحة
            $table->decimal('used_days', 5, 2)->default(0);          // المستخدم
            $table->decimal('pending_days', 5, 2)->default(0);       // قيد الانتظار (طلبات معلقة)
            $table->decimal('remaining_days', 5, 2)->default(0);     // المتبقي
            $table->text('notes')->nullable();
            $table->uuid('last_updated_by')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('leave_type_id')->references('id')->on('leave_types');
            $table->foreign('last_updated_by')->references('id')->on('users');

            $table->unique(['employee_id', 'leave_type_id', 'year']);
            $table->index(['employee_id', 'year']);
        });

        // طلبات الإجازة (المرحلة الأولى: نموذج الطلب)
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_number')->unique();
            $table->uuid('employee_id');
            $table->uuid('leave_type_id');
            $table->date('start_date');
            $table->date('end_date');
            $table->decimal('total_days', 5, 2);
            $table->boolean('is_half_day')->default(false);
            $table->enum('half_day_period', ['morning', 'afternoon'])->nullable();
            $table->text('reason')->nullable();
            $table->text('reason_ar')->nullable();
            $table->string('contact_during_leave')->nullable();      // رقم التواصل أثناء الإجازة
            $table->string('address_during_leave')->nullable();      // العنوان أثناء الإجازة

            // القائم بالعمل والمهام الوظيفية
            $table->uuid('delegate_employee_id')->nullable();        // القائم بالعمل أثناء الغياب
            $table->text('job_tasks')->nullable();                   // المهام الوظيفية المسندة للقائم بالعمل
            $table->text('job_tasks_ar')->nullable();
            $table->boolean('delegate_confirmed')->default(false);   // تأكيد القائم بالعمل
            $table->timestamp('delegate_confirmed_at')->nullable();

            // حالة نموذج الطلب
            $table->enum('status', [
                'draft',                    // مسودة
                'pending_supervisor',       // بانتظار توصية المشرف المباشر
                'pending_admin_manager',    // بانتظار موافقة المدير الإداري
                'pending_hr',               // بانتظار تعميد الموارد البشرية
                'pending_delegate',         // بانتظار اعتماد القائم بالعمل
                'form_completed',           // اكتمل النموذج - جاهز لإنشاء القرار
                'decision_pending',         // قرار الإجازة قيد الاعتماد
                'approved',                 // معتمدة نهائياً
                'rejected',                 // مرفوضة
                'cancelled',                // ملغاة
                'in_progress',              // جارية (الموظف في إجازة)
                'completed',                // منتهية
                'cut_short'                 // مقطوعة (عودة مبكرة)
            ])->default('draft');

            $table->date('actual_return_date')->nullable();          // تاريخ العودة الفعلي
            $table->decimal('actual_days_taken', 5, 2)->nullable();  // الأيام الفعلية
            $table->text('cancellation_reason')->nullable();
            $table->uuid('cancelled_by')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->json('attachments')->nullable();                  // المرفقات (تقارير طبية، إلخ)
            $table->uuid('created_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('leave_type_id')->references('id')->on('leave_types');
            $table->foreign('delegate_employee_id')->references('id')->on('employees');
            $table->foreign('cancelled_by')->references('id')->on('users');
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('status');
        });

        // سلسلة الموافقات على نموذج الطلب (المرحلة الأولى)
        Schema::create('leave_approvals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leave_request_id');
            $table->integer('sequence')->default(1);                  // ترتيب الموافقة
            $table->enum('approver_type', [
                'supervisor',               // المشرف المباشر (توصية)
                'admin_manager',            // المدير الإداري (موافقة)
                'hr_officer',               // موظف الموارد البشرية (تعميد)
                'delegate',                 // القائم بالعمل (اعتماد التغطية)
                'department_head',          // رئيس القسم
                'hr_manager'                // مدير الموارد البشرية
            ]);
            $table->enum('action_type', [
                'recommendation',           // توصية (المشرف)
                'approval',                 // موافقة (المدير الإداري)
                'endorsement',              // تعميد (الموارد البشرية)
                'coverage_confirmation'     // اعتماد التغطية (القائم بالعمل)
            ])->default('approval');
            $table->uuid('approver_id');
            $table->enum('status', [
                'pending',      // بانتظار
                'recommended',  // موصى به
                'approved',     // موافق عليه
                'endorsed',     // معتمد (للتعميد)
                'confirmed',    // مؤكد (للقائم بالعمل)
                'rejected',     // مرفوض
                'skipped'       // تم تجاوزه
            ])->default('pending');
            $table->text('comment')->nullable();
            $table->text('comment_ar')->nullable();
            $table->text('job_tasks_assigned')->nullable();           // المهام المسندة (يملأها المشرف)
            $table->timestamp('action_at')->nullable();
            $table->string('ip_address')->nullable();
            $table->timestamps();

            $table->foreign('leave_request_id')->references('id')->on('leave_requests')->cascadeOnDelete();
            $table->foreign('approver_id')->references('id')->on('users');

            $table->unique(['leave_request_id', 'sequence']);
            $table->index(['approver_id', 'status']);
        });

        // قرارات الإجازة (المرحلة الثانية)
        Schema::create('leave_decisions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('decision_number')->unique();              // رقم القرار
            $table->uuid('leave_request_id');
            $table->enum('employee_type', [
                'doctor',                   // طبيب
                'medical_staff',            // كادر طبي
                'administrative',           // إداري
                'other'                     // أخرى
            ]);
            $table->enum('status', [
                'draft',                    // مسودة
                'pending_admin_manager',    // بانتظار اعتماد المدير الإداري
                'pending_medical_director', // بانتظار اعتماد المدير الطبي (للأطباء)
                'pending_general_manager',  // بانتظار اعتماد المدير العام (اختياري)
                'approved',                 // معتمد
                'rejected'                  // مرفوض
            ])->default('draft');

            // خيار تحويل للمدير العام
            $table->boolean('requires_gm_approval')->default(false);  // هل يتطلب موافقة المدير العام؟
            $table->boolean('forwarded_to_gm')->default(false);       // هل تم تحويله للمدير العام؟
            $table->text('forward_reason')->nullable();               // سبب التحويل للمدير العام

            $table->uuid('created_by');

            // اعتماد المدير الإداري (للموظفين)
            $table->enum('admin_manager_action', ['approved', 'rejected', 'forwarded_to_gm'])->nullable();
            $table->timestamp('admin_manager_action_at')->nullable();
            $table->uuid('admin_manager_id')->nullable();
            $table->text('admin_manager_comment')->nullable();

            // اعتماد المدير الطبي (للأطباء)
            $table->enum('medical_director_action', ['approved', 'rejected', 'forwarded_to_gm'])->nullable();
            $table->timestamp('medical_director_action_at')->nullable();
            $table->uuid('medical_director_id')->nullable();
            $table->text('medical_director_comment')->nullable();

            // اعتماد المدير العام (اختياري - حسب الحاجة)
            $table->enum('general_manager_action', ['approved', 'rejected'])->nullable();
            $table->timestamp('general_manager_action_at')->nullable();
            $table->uuid('general_manager_id')->nullable();
            $table->text('general_manager_comment')->nullable();

            $table->timestamp('final_approved_at')->nullable();
            $table->uuid('final_approved_by')->nullable();
            $table->timestamps();

            $table->foreign('leave_request_id')->references('id')->on('leave_requests');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('admin_manager_id')->references('id')->on('users');
            $table->foreign('medical_director_id')->references('id')->on('users');
            $table->foreign('general_manager_id')->references('id')->on('users');
            $table->foreign('final_approved_by')->references('id')->on('users');

            $table->index('status');
        });

        // سياسات الإجازات حسب نوع العقد
        Schema::create('leave_policies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('name_ar');
            $table->enum('contract_type', [
                'full_time',    // دوام كامل
                'part_time',    // دوام جزئي
                'tamheer',      // تمهير
                'percentage',   // نسبة
                'locum'         // بديل
            ]);
            $table->uuid('leave_type_id');
            $table->integer('days_per_year');
            $table->integer('accrual_start_month')->default(1);      // شهر بدء الاستحقاق
            $table->enum('accrual_method', [
                'yearly',       // سنوي دفعة واحدة
                'monthly',      // شهري تدريجي
                'daily'         // يومي
            ])->default('yearly');
            $table->integer('min_service_months')->default(0);       // الحد الأدنى لمدة الخدمة
            $table->json('additional_rules')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('leave_type_id')->references('id')->on('leave_types');

            $table->unique(['contract_type', 'leave_type_id']);
        });

        // سجل تعديلات الرصيد
        Schema::create('leave_balance_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('leave_balance_id');
            $table->uuid('leave_request_id')->nullable();
            $table->enum('adjustment_type', [
                'initial',          // رصيد أولي
                'accrual',          // استحقاق دوري
                'carry_over',       // ترحيل
                'used',             // استخدام
                'cancelled',        // إلغاء طلب
                'manual_add',       // إضافة يدوية
                'manual_deduct',    // خصم يدوي
                'expired',          // انتهاء صلاحية
                'correction'        // تصحيح
            ]);
            $table->decimal('days_amount', 5, 2);                     // موجب للإضافة، سالب للخصم
            $table->decimal('balance_before', 5, 2);
            $table->decimal('balance_after', 5, 2);
            $table->text('reason')->nullable();
            $table->uuid('performed_by');
            $table->timestamp('created_at');
            // لا يوجد updated_at - السجل غير قابل للتعديل

            $table->foreign('leave_balance_id')->references('id')->on('leave_balances');
            $table->foreign('leave_request_id')->references('id')->on('leave_requests');
            $table->foreign('performed_by')->references('id')->on('users');

            $table->index(['leave_balance_id', 'created_at']);
        });

        // الإجازات الرسمية والعطل
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('name_ar');
            $table->date('date');
            $table->integer('year');
            $table->boolean('is_recurring')->default(false);         // متكررة سنوياً
            $table->enum('calendar_type', ['gregorian', 'hijri'])->default('gregorian');
            $table->integer('hijri_month')->nullable();
            $table->integer('hijri_day')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['date', 'year']);
            $table->index(['year', 'is_active']);
        });

        // إعدادات الإجازات للأقسام
        Schema::create('department_leave_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('department_id');
            $table->integer('max_concurrent_leaves')->default(2);     // الحد الأقصى للإجازات المتزامنة
            $table->decimal('max_concurrent_percentage', 5, 2)->default(20); // نسبة الموظفين
            $table->json('blackout_periods')->nullable();             // فترات محظورة
            $table->json('peak_periods')->nullable();                 // فترات الذروة (قيود إضافية)
            $table->boolean('require_coverage')->default(false);      // يتطلب تغطية
            $table->uuid('default_approver_id')->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('default_approver_id')->references('id')->on('users');

            $table->unique('department_id');
        });

        // إعدادات سلسلة الموافقات حسب نوع الموظف
        Schema::create('leave_approval_workflows', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('name_ar');
            $table->enum('employee_type', [
                'doctor',               // طبيب
                'medical_staff',        // كادر طبي (تمريض، فني)
                'administrative',       // إداري
                'support',              // خدمات مساندة
                'management'            // إدارة
            ]);

            // سلسلة موافقات نموذج الطلب (المرحلة الأولى)
            $table->json('request_approval_chain');
            /*
             * سلسلة نموذج الطلب لجميع الموظفين:
             * [
             *   {"sequence": 1, "type": "supervisor", "action": "recommendation", "required": true},
             *   {"sequence": 2, "type": "admin_manager", "action": "approval", "required": true},
             *   {"sequence": 3, "type": "hr_officer", "action": "endorsement", "required": true},
             *   {"sequence": 4, "type": "delegate", "action": "coverage_confirmation", "required": true}
             * ]
             */

            // سلسلة موافقات قرار الإجازة (المرحلة الثانية)
            $table->json('decision_approval_chain');
            /*
             * للموظفين الإداريين:
             * [
             *   {"sequence": 1, "type": "admin_manager", "action": "approval", "required": true, "can_forward_to_gm": true},
             *   {"sequence": 2, "type": "general_manager", "action": "approval", "required": false}
             * ]
             *
             * للأطباء والكادر الطبي:
             * [
             *   {"sequence": 1, "type": "medical_director", "action": "approval", "required": true, "can_forward_to_gm": true},
             *   {"sequence": 2, "type": "general_manager", "action": "approval", "required": false}
             * ]
             *
             * ملاحظة: المدير الإداري/الطبي يمكنه:
             * 1. الاعتماد مباشرة (approved) - القرار نهائي
             * 2. التحويل للمدير العام (forwarded_to_gm) - حسب الحاجة
             * 3. الرفض (rejected)
             */

            $table->boolean('gm_approval_optional')->default(true);  // موافقة المدير العام اختيارية
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique('employee_type');
        });

        // جدول المدراء المعتمدين (لربط الموظفين بمدرائهم)
        Schema::create('employee_approvers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('approver_id');
            $table->enum('approver_role', [
                'direct_manager',       // المدير المباشر
                'admin_manager',        // المدير الإداري
                'medical_director',     // المدير الطبي
                'department_head',      // رئيس القسم
                'backup_approver'       // المعتمد البديل
            ]);
            $table->boolean('is_primary')->default(true);     // المعتمد الرئيسي
            $table->date('effective_from')->nullable();
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('approver_id')->references('id')->on('users');

            $table->index(['employee_id', 'approver_role', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_approvers');
        Schema::dropIfExists('leave_approval_workflows');
        Schema::dropIfExists('department_leave_settings');
        Schema::dropIfExists('public_holidays');
        Schema::dropIfExists('leave_balance_adjustments');
        Schema::dropIfExists('leave_policies');
        Schema::dropIfExists('leave_decisions');
        Schema::dropIfExists('leave_approvals');
        Schema::dropIfExists('leave_requests');
        Schema::dropIfExists('leave_balances');
        Schema::dropIfExists('leave_types');
    }
};
