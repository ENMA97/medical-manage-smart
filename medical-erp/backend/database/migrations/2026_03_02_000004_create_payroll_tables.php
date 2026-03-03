<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الموديول الثالث: الرواتب والمستحقات (Payroll & End of Service)
 *
 * يغطي هذا الملف:
 * 1. مسيرات الرواتب الشهرية (payrolls)
 * 2. تفاصيل رواتب الموظفين (payroll_items)
 * 3. عناصر الرواتب المخصصة (payroll_additions_deductions)
 * 4. سلف الموظفين (employee_loans)
 * 5. أقساط السلف (loan_installments)
 * 6. حسابات نهاية الخدمة (end_of_service_calculations)
 * 7. سجل تصدير الرواتب للبنوك (payroll_exports)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. مسيرات الرواتب الشهرية (Payrolls)
        // ─────────────────────────────────────────────
        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payroll_number')->unique();           // رقم المسيّر
            $table->integer('month');                              // الشهر
            $table->integer('year');                               // السنة
            $table->enum('status', [
                'draft',              // مسودة
                'calculating',        // جاري الحساب
                'pending_review',     // بانتظار المراجعة
                'reviewed',           // تمت المراجعة
                'pending_approval',   // بانتظار الاعتماد
                'approved',           // معتمد
                'processing',         // جاري المعالجة البنكية
                'paid',               // مدفوع
                'cancelled'           // ملغي
            ])->default('draft');

            // الإجماليات
            $table->decimal('total_basic_salary', 14, 2)->default(0);
            $table->decimal('total_allowances', 14, 2)->default(0);
            $table->decimal('total_additions', 14, 2)->default(0);    // إضافات
            $table->decimal('total_deductions', 14, 2)->default(0);   // خصومات
            $table->decimal('total_overtime', 14, 2)->default(0);     // أوفرتايم
            $table->decimal('total_gosi_employee', 14, 2)->default(0);// تأمينات (حصة موظف)
            $table->decimal('total_gosi_employer', 14, 2)->default(0);// تأمينات (حصة صاحب عمل)
            $table->decimal('total_gross_salary', 14, 2)->default(0); // إجمالي قبل الخصم
            $table->decimal('total_net_salary', 14, 2)->default(0);   // صافي الرواتب
            $table->integer('employees_count')->default(0);            // عدد الموظفين

            // البيانات الإضافية
            $table->date('payment_date')->nullable();                  // تاريخ الصرف
            $table->text('notes')->nullable();
            $table->uuid('created_by');
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('reviewed_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->unique(['month', 'year']);
        });

        // ─────────────────────────────────────────────
        // 2. تفاصيل رواتب الموظفين (Payroll Items)
        // بند لكل موظف في كل مسيّر
        // ─────────────────────────────────────────────
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_id');
            $table->uuid('employee_id');
            $table->uuid('contract_id');

            // المستحقات (الراتب + البدلات)
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('food_allowance', 12, 2)->default(0);
            $table->decimal('phone_allowance', 12, 2)->default(0);
            $table->decimal('other_allowances', 12, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            $table->decimal('commission', 12, 2)->default(0);
            $table->decimal('custom_additions', 12, 2)->default(0);

            // الاستقطاعات
            $table->decimal('gosi_employee', 12, 2)->default(0);       // تأمينات (حصة الموظف)
            $table->decimal('gosi_employer', 12, 2)->default(0);       // تأمينات (حصة صاحب العمل)
            $table->decimal('absence_deduction', 12, 2)->default(0);   // خصم غياب
            $table->decimal('late_deduction', 12, 2)->default(0);      // خصم تأخير
            $table->decimal('loan_deduction', 12, 2)->default(0);      // خصم سلف
            $table->decimal('other_deductions', 12, 2)->default(0);    // خصومات أخرى
            $table->decimal('custom_deductions', 12, 2)->default(0);

            // الإجماليات
            $table->decimal('gross_salary', 12, 2)->default(0);        // الإجمالي قبل الخصم
            $table->decimal('total_deductions', 12, 2)->default(0);    // إجمالي الخصومات
            $table->decimal('net_salary', 12, 2)->default(0);          // صافي الراتب

            // معلومات أيام العمل
            $table->integer('total_working_days')->default(30);
            $table->integer('actual_working_days')->default(30);
            $table->integer('absent_days')->default(0);
            $table->integer('late_days')->default(0);
            $table->integer('overtime_hours')->default(0);

            // معلومات البنك
            $table->string('bank_name')->nullable();
            $table->string('iban')->nullable();

            $table->json('calculation_details')->nullable();           // تفاصيل الحساب (للمراجعة)
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('payroll_id')->references('id')->on('payrolls')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('contract_id')->references('id')->on('contracts');

            $table->unique(['payroll_id', 'employee_id']);
            $table->index('employee_id');
        });

        // ─────────────────────────────────────────────
        // 3. إضافات وخصومات مخصصة (Payroll Additions/Deductions)
        // عناصر إضافية يتم إدراجها يدوياً
        // ─────────────────────────────────────────────
        Schema::create('payroll_additions_deductions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('payroll_id')->nullable();                // يربط بمسيّر محدد
            $table->enum('type', ['addition', 'deduction']);
            $table->string('description');
            $table->string('description_ar')->nullable();
            $table->decimal('amount', 12, 2);
            $table->enum('frequency', [
                'one_time',        // لمرة واحدة
                'monthly',         // شهري
                'recurring'        // متكرر (لعدد محدد)
            ])->default('one_time');
            $table->integer('remaining_occurrences')->nullable(); // العدد المتبقي (للمتكرر)
            $table->date('effective_from');
            $table->date('effective_to')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('reason')->nullable();
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('payroll_id')->references('id')->on('payrolls')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->index(['employee_id', 'type', 'is_active']);
        });

        // ─────────────────────────────────────────────
        // 4. سلف الموظفين (Employee Loans)
        // ─────────────────────────────────────────────
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->string('loan_number')->unique();
            $table->decimal('loan_amount', 12, 2);                 // مبلغ السلفة
            $table->decimal('monthly_deduction', 12, 2);           // القسط الشهري
            $table->decimal('remaining_amount', 12, 2);            // المبلغ المتبقي
            $table->integer('total_installments');                  // عدد الأقساط الكلي
            $table->integer('paid_installments')->default(0);      // الأقساط المدفوعة
            $table->integer('remaining_installments');              // الأقساط المتبقية
            $table->date('start_date');                              // تاريخ بدء السلفة
            $table->date('expected_end_date')->nullable();          // تاريخ الانتهاء المتوقع
            $table->text('reason');                                  // سبب السلفة
            $table->enum('status', [
                'pending',         // بانتظار الموافقة
                'approved',        // معتمد
                'active',          // جاري السداد
                'completed',       // مكتمل السداد
                'cancelled',       // ملغي
                'defaulted'        // متعثر
            ])->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->index(['employee_id', 'status']);
        });

        // ─────────────────────────────────────────────
        // 5. أقساط السلف (Loan Installments)
        // ─────────────────────────────────────────────
        Schema::create('loan_installments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->uuid('payroll_item_id')->nullable();           // ربط ببند الراتب
            $table->integer('installment_number');                  // رقم القسط
            $table->decimal('amount', 12, 2);                       // مبلغ القسط
            $table->decimal('remaining_after', 12, 2);              // المتبقي بعد القسط
            $table->date('due_date');                                // تاريخ الاستحقاق
            $table->date('paid_date')->nullable();                  // تاريخ الدفع الفعلي
            $table->enum('status', [
                'pending',         // بانتظار
                'paid',            // مدفوع
                'skipped',         // مؤجل
                'cancelled'        // ملغي
            ])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('loan_id')->references('id')->on('employee_loans')->cascadeOnDelete();
            $table->foreign('payroll_item_id')->references('id')->on('payroll_items')->nullOnDelete();

            $table->unique(['loan_id', 'installment_number']);
            $table->index(['status', 'due_date']);
        });

        // ─────────────────────────────────────────────
        // 6. حسابات نهاية الخدمة (End of Service Calculations)
        // حاسبة المستحقات بناءً على نظام العمل السعودي
        // ─────────────────────────────────────────────
        Schema::create('end_of_service_calculations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('contract_id');

            // بيانات الحساب
            $table->date('service_start_date');                    // تاريخ بداية الخدمة
            $table->date('service_end_date');                      // تاريخ نهاية الخدمة
            $table->integer('total_service_years');                 // سنوات الخدمة
            $table->integer('total_service_months');                // أشهر الخدمة
            $table->integer('total_service_days');                  // أيام الخدمة

            // تفاصيل الراتب المعتمد
            $table->decimal('last_basic_salary', 12, 2);
            $table->decimal('last_housing_allowance', 12, 2)->default(0);
            $table->decimal('last_transport_allowance', 12, 2)->default(0);
            $table->decimal('last_total_salary', 12, 2);           // الراتب الأخير الشامل

            // حساب مكافأة نهاية الخدمة
            $table->enum('termination_reason', [
                'resignation',              // استقالة
                'employer_termination',     // فصل من صاحب العمل
                'contract_end',             // انتهاء عقد
                'mutual_agreement',         // اتفاق مشترك
                'retirement',               // تقاعد
                'death',                    // وفاة
                'force_majeure'             // قوة قاهرة
            ]);
            $table->decimal('first_5_years_amount', 12, 2)->default(0);  // مستحقات أول 5 سنوات
            $table->decimal('after_5_years_amount', 12, 2)->default(0);  // مستحقات ما بعد 5 سنوات
            $table->decimal('total_eos_amount', 12, 2);                   // إجمالي مكافأة نهاية الخدمة
            $table->decimal('eos_multiplier', 5, 2)->default(1.00);      // معامل الاستحقاق (حسب السبب)

            // مستحقات أخرى
            $table->decimal('remaining_leave_days', 8, 2)->default(0);    // أيام إجازة متبقية
            $table->decimal('leave_compensation', 12, 2)->default(0);     // تعويض الإجازات
            $table->decimal('other_compensation', 12, 2)->default(0);     // تعويضات أخرى
            $table->decimal('pending_loans', 12, 2)->default(0);          // سلف معلقة (خصم)
            $table->decimal('other_deductions', 12, 2)->default(0);       // خصومات أخرى

            // الإجمالي
            $table->decimal('total_entitlements', 12, 2);                  // إجمالي المستحقات
            $table->decimal('total_deductions_amount', 12, 2)->default(0); // إجمالي الخصومات
            $table->decimal('net_settlement', 12, 2);                      // صافي التسوية

            // الحالة
            $table->enum('status', [
                'draft',            // مسودة (حساب أولي)
                'calculated',       // محسوب
                'pending_review',   // بانتظار المراجعة
                'approved',         // معتمد
                'paid',             // مصروف
                'cancelled'         // ملغي
            ])->default('draft');

            $table->json('calculation_breakdown')->nullable();  // تفصيل الحساب خطوة بخطوة
            $table->text('notes')->nullable();
            $table->uuid('calculated_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('contract_id')->references('id')->on('contracts');
            $table->foreign('calculated_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->index(['employee_id', 'status']);
        });

        // ─────────────────────────────────────────────
        // 7. سجل تصدير الرواتب للبنوك (Payroll Exports)
        // ─────────────────────────────────────────────
        Schema::create('payroll_exports', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_id');
            $table->enum('export_format', [
                'wps',             // نظام حماية الأجور
                'csv',             // ملف CSV
                'excel',           // ملف Excel
                'bank_transfer',   // تحويل بنكي
                'pdf'              // ملف PDF
            ]);
            $table->string('file_path')->nullable();                // مسار الملف
            $table->string('file_name');
            $table->string('bank_name')->nullable();
            $table->integer('records_count')->default(0);
            $table->decimal('total_amount', 14, 2)->default(0);
            $table->uuid('exported_by');
            $table->timestamp('exported_at');
            $table->timestamps();

            $table->foreign('payroll_id')->references('id')->on('payrolls');
            $table->foreign('exported_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_exports');
        Schema::dropIfExists('end_of_service_calculations');
        Schema::dropIfExists('loan_installments');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('payroll_additions_deductions');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
    }
};
