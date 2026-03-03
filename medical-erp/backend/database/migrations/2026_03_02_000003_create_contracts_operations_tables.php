<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الموديول الثاني: إدارة العقود والعمليات (Contracts & Operations)
 *
 * يغطي هذا الملف:
 * 1. العقود (contracts) - بيانات العقد الكاملة
 * 2. تنبيهات العقود (contract_alerts) - تذكير تلقائي قبل انتهاء العقد
 * 3. إجراءات تجديد العقود (contract_renewals) - سجل التجديد والاستجابة
 * 4. قوالب الخطابات (letter_templates) - قوالب الخطابات الرسمية
 * 5. الخطابات المُصدَرة (generated_letters) - الخطابات المولّدة
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. جدول العقود (Contracts)
        // ─────────────────────────────────────────────
        Schema::create('contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->string('contract_number')->unique();          // رقم العقد (تلقائي)
            $table->enum('contract_type', [
                'full_time',       // دوام كامل
                'part_time',       // دوام جزئي
                'temporary',       // مؤقت
                'tamheer',         // تمهير
                'percentage',      // نسبة
                'locum',           // لوكم
                'probation'        // تجربة
            ]);
            $table->enum('status', [
                'draft',           // مسودة
                'pending_approval',// بانتظار الموافقة
                'active',          // ساري المفعول
                'expired',         // منتهي
                'terminated',      // مُنهى
                'renewed',         // مُجدد (أصبح سابق)
                'suspended'        // معلق
            ])->default('draft');

            // التواريخ
            $table->date('start_date');                            // تاريخ بداية العقد
            $table->date('end_date')->nullable();                  // تاريخ نهاية العقد
            $table->integer('duration_months')->nullable();        // مدة العقد بالأشهر
            $table->integer('probation_days')->default(90);        // فترة التجربة (يوم)
            $table->date('probation_end_date')->nullable();

            // البيانات المالية
            $table->decimal('basic_salary', 12, 2);               // الراتب الأساسي
            $table->decimal('housing_allowance', 12, 2)->default(0);   // بدل سكن
            $table->decimal('transport_allowance', 12, 2)->default(0); // بدل نقل
            $table->decimal('food_allowance', 12, 2)->default(0);      // بدل طعام
            $table->decimal('phone_allowance', 12, 2)->default(0);     // بدل اتصالات
            $table->decimal('other_allowances', 12, 2)->default(0);    // بدلات أخرى
            $table->decimal('total_salary', 12, 2)->nullable();        // إجمالي الراتب (محسوب)
            $table->decimal('percentage_rate', 5, 2)->nullable();      // نسبة (لعقود النسبة)

            // الإجازات
            $table->integer('annual_leave_days')->default(30);         // أيام الإجازة السنوية
            $table->integer('sick_leave_days')->default(30);           // أيام الإجازة المرضية
            $table->integer('notice_period_days')->default(60);        // فترة الإشعار

            // التفاصيل
            $table->text('terms_and_conditions')->nullable();          // شروط وأحكام
            $table->json('benefits')->nullable();                       // مزايا إضافية (JSON)
            $table->text('special_clauses')->nullable();               // بنود خاصة
            $table->string('contract_file')->nullable();               // ملف العقد المرفوع

            // الموافقات
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // العقد السابق (في حالة التجديد)
            $table->uuid('previous_contract_id')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
            $table->index('end_date');
            $table->index('status');
        });

        // المفتاح الخارجي الذاتي
        Schema::table('contracts', function (Blueprint $table) {
            $table->foreign('previous_contract_id')->references('id')->on('contracts')->nullOnDelete();
        });

        // ─────────────────────────────────────────────
        // 2. تنبيهات العقود الآلية (Contract Alerts)
        // نظام تذكير قبل انتهاء العقد بفترة محددة
        // ─────────────────────────────────────────────
        Schema::create('contract_alerts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contract_id');
            $table->uuid('employee_id');
            $table->enum('alert_type', [
                'expiry_reminder',      // تذكير انتهاء العقد
                'probation_end',        // انتهاء فترة التجربة
                'renewal_due',          // مطلوب تجديد
                'document_expiry',      // انتهاء مستند مرتبط
                'id_expiry'             // انتهاء الهوية/الإقامة
            ]);
            $table->integer('days_before_expiry');            // عدد الأيام قبل الانتهاء
            $table->date('alert_date');                        // تاريخ التنبيه
            $table->date('expiry_date');                       // تاريخ الانتهاء
            $table->enum('status', [
                'pending',         // بانتظار الإرسال
                'sent',            // تم الإرسال
                'acknowledged',    // تم الاطلاع
                'action_taken',    // تم اتخاذ إجراء
                'dismissed'        // تم التجاهل
            ])->default('pending');
            $table->boolean('sent_to_employee')->default(false);    // تم إبلاغ الموظف
            $table->boolean('sent_to_manager')->default(false);     // تم إبلاغ المدير
            $table->boolean('sent_to_hr')->default(false);          // تم إبلاغ HR
            $table->text('message')->nullable();
            $table->text('message_ar')->nullable();
            $table->uuid('actioned_by')->nullable();
            $table->timestamp('actioned_at')->nullable();
            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('actioned_by')->references('id')->on('users');

            $table->index(['status', 'alert_date']);
            $table->index(['contract_id', 'alert_type']);
        });

        // ─────────────────────────────────────────────
        // 3. تجديد العقود (Contract Renewals)
        // سجل استجابة الموظف والإدارة لتجديد أو إنهاء العقد
        // ─────────────────────────────────────────────
        Schema::create('contract_renewals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('contract_id');                       // العقد الحالي
            $table->uuid('employee_id');

            // رغبة الموظف
            $table->enum('employee_response', [
                'wants_renewal',       // يرغب بالتجديد
                'wants_termination',   // يرغب بالإنهاء
                'no_response'          // لم يستجب بعد
            ])->default('no_response');
            $table->timestamp('employee_response_at')->nullable();
            $table->text('employee_remarks')->nullable();

            // قرار الإدارة
            $table->enum('management_decision', [
                'approve_renewal',     // موافقة على التجديد
                'reject_renewal',      // رفض التجديد
                'modify_terms',        // تعديل الشروط
                'pending'              // بانتظار القرار
            ])->default('pending');
            $table->uuid('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('management_remarks')->nullable();
            $table->json('new_terms')->nullable();             // الشروط الجديدة (إن وجدت)

            // العقد الجديد (إن تم التجديد)
            $table->uuid('new_contract_id')->nullable();

            $table->enum('status', [
                'initiated',       // بدأ الإجراء
                'awaiting_employee', // بانتظار رد الموظف
                'awaiting_management', // بانتظار الإدارة
                'approved',        // معتمد
                'rejected',        // مرفوض
                'completed',       // مكتمل (تم إنشاء العقد الجديد)
                'cancelled'        // ملغي
            ])->default('initiated');

            $table->timestamps();

            $table->foreign('contract_id')->references('id')->on('contracts');
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('decided_by')->references('id')->on('users');
            $table->foreign('new_contract_id')->references('id')->on('contracts')->nullOnDelete();

            $table->index(['contract_id', 'status']);
        });

        // ─────────────────────────────────────────────
        // 4. قوالب الخطابات الرسمية (Letter Templates)
        // ─────────────────────────────────────────────
        Schema::create('letter_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();                  // رمز القالب
            $table->string('name');                             // اسم القالب (EN)
            $table->string('name_ar');                          // اسم القالب (AR)
            $table->enum('letter_type', [
                'experience_certificate',       // شهادة خبرة
                'salary_certificate',           // تعريف بالراتب
                'employment_certificate',       // تعريف بالعمل
                'insurance_exclusion',          // استبعاد من التأمينات
                'acceptance_letter',            // خطاب قبول
                'termination_letter',           // خطاب إنهاء
                'warning_letter',               // خطاب إنذار
                'promotion_letter',             // خطاب ترقية
                'salary_transfer_letter',       // خطاب تحويل راتب
                'to_whom_it_may_concern',       // لمن يهمه الأمر
                'custom'                        // مخصص
            ]);
            $table->text('body_template');                      // محتوى القالب (EN) مع متغيرات {{variable}}
            $table->text('body_template_ar');                   // محتوى القالب (AR)
            $table->text('header_template')->nullable();        // ترويسة
            $table->text('footer_template')->nullable();        // تذييل
            $table->json('available_variables');                 // المتغيرات المتاحة
            $table->json('default_settings')->nullable();       // إعدادات افتراضية (حجم خط، هوامش...)
            $table->boolean('requires_approval')->default(false); // يتطلب موافقة
            $table->boolean('is_active')->default(true);
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->index('letter_type');
        });

        // ─────────────────────────────────────────────
        // 5. الخطابات المُصدَرة (Generated Letters)
        // سجل الخطابات التي تم توليدها
        // ─────────────────────────────────────────────
        Schema::create('generated_letters', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id');
            $table->uuid('employee_id');
            $table->string('letter_number')->unique();         // رقم الخطاب (تلقائي)
            $table->string('letter_type');                      // نوع الخطاب
            $table->text('content');                             // المحتوى المولّد (EN)
            $table->text('content_ar')->nullable();             // المحتوى المولّد (AR)
            $table->json('variables_used');                      // المتغيرات المستخدمة وقيمها
            $table->string('generated_file_path')->nullable();  // مسار ملف PDF المولّد
            $table->enum('status', [
                'draft',           // مسودة
                'pending_approval',// بانتظار الموافقة
                'approved',        // معتمد
                'printed',         // مطبوع
                'delivered',       // تم التسليم
                'cancelled'        // ملغي
            ])->default('draft');
            $table->uuid('generated_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('printed_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('template_id')->references('id')->on('letter_templates');
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('generated_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->index(['employee_id', 'letter_type']);
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_letters');
        Schema::dropIfExists('letter_templates');
        Schema::dropIfExists('contract_renewals');
        Schema::dropIfExists('contract_alerts');
        Schema::dropIfExists('contracts');
    }
};
