<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إعدادات الرواتب
        Schema::create('payroll_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, number, percentage, amount, multiplier, boolean
            $table->string('description_ar')->nullable();
            $table->string('description_en')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // مسيرات الرواتب
        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payroll_number')->unique();
            $table->uuid('employee_id');
            $table->integer('period_year');
            $table->integer('period_month');

            // الراتب الأساسي والبدلات
            $table->decimal('basic_salary', 10, 2)->default(0);
            $table->decimal('housing_allowance', 10, 2)->default(0);
            $table->decimal('transportation_allowance', 10, 2)->default(0);
            $table->decimal('food_allowance', 10, 2)->default(0);
            $table->decimal('phone_allowance', 10, 2)->default(0);
            $table->decimal('other_allowances', 10, 2)->default(0);

            // الوقت الإضافي
            $table->decimal('overtime_hours', 6, 2)->default(0);
            $table->decimal('overtime_rate', 10, 2)->default(0);
            $table->decimal('overtime_amount', 10, 2)->default(0);

            // المكافآت والحوافز
            $table->decimal('bonus_amount', 10, 2)->default(0);
            $table->decimal('incentive_amount', 10, 2)->default(0);
            $table->decimal('commission_amount', 10, 2)->default(0);

            // الخصومات
            $table->integer('absence_days')->default(0);
            $table->decimal('absence_deduction', 10, 2)->default(0);
            $table->integer('late_minutes')->default(0);
            $table->decimal('late_deduction', 10, 2)->default(0);
            $table->decimal('loan_deduction', 10, 2)->default(0);
            $table->decimal('advance_deduction', 10, 2)->default(0);
            $table->decimal('other_deductions', 10, 2)->default(0);

            // التأمينات الاجتماعية (GOSI)
            $table->decimal('gosi_employee', 10, 2)->default(0);
            $table->decimal('gosi_employer', 10, 2)->default(0);

            // الإجماليات (يتم حسابها)
            $table->decimal('total_allowances', 10, 2)->default(0);
            $table->decimal('total_earnings', 10, 2)->default(0);
            $table->decimal('total_deductions', 10, 2)->default(0);
            $table->decimal('gross_salary', 10, 2)->default(0);
            $table->decimal('net_salary', 10, 2)->default(0);

            // العملة والبنك
            $table->string('currency', 3)->default('SAR');
            $table->string('bank_name')->nullable();
            $table->string('bank_code', 4)->nullable();
            $table->string('iban', 34)->nullable();

            // WPS
            $table->boolean('wps_generated')->default(false);
            $table->string('wps_file_path')->nullable();
            $table->timestamp('wps_generated_at')->nullable();

            // الحالة
            $table->enum('status', [
                'draft',        // مسودة
                'calculated',   // محسوب
                'reviewed',     // مراجع
                'approved',     // معتمد
                'paid',         // مدفوع
                'cancelled'     // ملغي
            ])->default('draft');

            // التتبع
            $table->uuid('calculated_by')->nullable();
            $table->timestamp('calculated_at')->nullable();
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->uuid('paid_by')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->date('pay_date')->nullable();

            $table->text('notes')->nullable();
            $table->integer('version')->default(1);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees');

            $table->unique(['employee_id', 'period_year', 'period_month', 'version'], 'unique_employee_period');
            $table->index(['period_year', 'period_month', 'status']);
            $table->index(['employee_id', 'status']);
        });

        // بنود مسير الراتب (التفاصيل)
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_id');

            $table->enum('type', ['earning', 'deduction']);
            $table->string('code'); // BASIC, HOUSING, TRANSPORT, OVERTIME, GOSI, ABSENCE, LOAN, etc.
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();

            $table->decimal('quantity', 8, 2)->nullable(); // للوقت الإضافي مثلاً
            $table->decimal('rate', 10, 2)->nullable();
            $table->decimal('amount', 10, 2);

            $table->boolean('is_taxable')->default(false);
            $table->boolean('is_recurring')->default(true);

            // للربط مع السلف والقروض
            $table->string('reference_type')->nullable(); // loan, advance, etc.
            $table->uuid('reference_id')->nullable();

            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('payroll_id')->references('id')->on('payrolls')->cascadeOnDelete();

            $table->index(['payroll_id', 'type']);
        });

        // السلف والقروض
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('loan_number')->unique();
            $table->uuid('employee_id');

            $table->enum('type', [
                'loan',     // سلفة (تقسط على أشهر)
                'advance'   // سلفة راتب (تخصم مرة واحدة)
            ]);

            $table->enum('status', [
                'pending',      // قيد الانتظار
                'approved',     // معتمد
                'rejected',     // مرفوض
                'active',       // نشط (جاري السداد)
                'completed',    // مكتمل
                'cancelled'     // ملغي
            ])->default('pending');

            $table->decimal('loan_amount', 10, 2);
            $table->decimal('installment_amount', 10, 2);
            $table->integer('total_installments');
            $table->integer('paid_installments')->default(0);
            $table->decimal('remaining_amount', 10, 2);

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();

            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            // الطلب
            $table->timestamp('requested_at')->nullable();

            // الموافقة
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();

            // الرفض
            $table->uuid('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('approved_by')->references('id')->on('users');
            $table->foreign('rejected_by')->references('id')->on('users');

            $table->index(['employee_id', 'status']);
        });

        // مدفوعات/أقساط السلف
        Schema::create('loan_payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('loan_id');
            $table->uuid('payroll_id')->nullable();

            $table->decimal('amount', 10, 2);
            $table->date('payment_date');
            $table->decimal('remaining_after', 10, 2);

            $table->text('notes')->nullable();

            $table->timestamp('created_at')->useCurrent();
            // لا يوجد updated_at - السجل غير قابل للتعديل

            $table->foreign('loan_id')->references('id')->on('employee_loans')->cascadeOnDelete();
            $table->foreign('payroll_id')->references('id')->on('payrolls')->nullOnDelete();

            $table->index(['loan_id', 'payment_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('loan_payments');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
        Schema::dropIfExists('payroll_settings');
    }
};
