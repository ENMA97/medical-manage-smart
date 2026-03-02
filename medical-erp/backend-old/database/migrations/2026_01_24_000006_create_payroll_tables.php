<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // مسيرات الرواتب
        Schema::create('payrolls', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->integer('month');
            $table->integer('year');
            $table->enum('status', [
                'draft',
                'calculating',
                'pending_review',
                'approved',
                'processing',
                'paid',
                'cancelled'
            ])->default('draft');
            $table->decimal('total_basic_salary', 14, 2)->default(0);
            $table->decimal('total_allowances', 14, 2)->default(0);
            $table->decimal('total_deductions', 14, 2)->default(0);
            $table->decimal('total_overtime', 14, 2)->default(0);
            $table->decimal('total_commissions', 14, 2)->default(0);
            $table->decimal('total_net_salary', 14, 2)->default(0);
            $table->integer('employees_count')->default(0);
            $table->string('wps_file_path')->nullable();
            $table->timestamp('wps_generated_at')->nullable();
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            
            $table->unique(['month', 'year']);
        });

        // تفاصيل رواتب الموظفين
        Schema::create('payroll_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('payroll_id');
            $table->uuid('employee_id');
            $table->uuid('contract_id');
            
            // المستحقات
            $table->decimal('basic_salary', 12, 2)->default(0);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('other_allowances', 12, 2)->default(0);
            $table->decimal('overtime_amount', 12, 2)->default(0);
            $table->decimal('commission_amount', 12, 2)->default(0);
            $table->decimal('bonus', 12, 2)->default(0);
            
            // الاستقطاعات
            $table->decimal('gosi_employee', 12, 2)->default(0);
            $table->decimal('gosi_employer', 12, 2)->default(0);
            $table->decimal('absence_deduction', 12, 2)->default(0);
            $table->decimal('late_deduction', 12, 2)->default(0);
            $table->decimal('loan_deduction', 12, 2)->default(0);
            $table->decimal('clawback_deduction', 12, 2)->default(0);
            $table->decimal('other_deductions', 12, 2)->default(0);
            
            // الإجماليات
            $table->decimal('gross_salary', 12, 2)->default(0);
            $table->decimal('total_deductions', 12, 2)->default(0);
            $table->decimal('net_salary', 12, 2)->default(0);
            
            // معلومات إضافية
            $table->integer('working_days')->default(0);
            $table->integer('absent_days')->default(0);
            $table->integer('overtime_hours')->default(0);
            $table->text('notes')->nullable();
            $table->json('calculation_details')->nullable();
            $table->timestamps();

            $table->foreign('payroll_id')->references('id')->on('payrolls')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('contract_id')->references('id')->on('contracts');
            
            $table->unique(['payroll_id', 'employee_id']);
        });

        // سلف الموظفين
        Schema::create('employee_loans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->decimal('loan_amount', 12, 2);
            $table->decimal('monthly_deduction', 12, 2);
            $table->decimal('remaining_amount', 12, 2);
            $table->integer('total_installments');
            $table->integer('remaining_installments');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('reason')->nullable();
            $table->enum('status', ['pending', 'approved', 'active', 'completed', 'cancelled'])->default('pending');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('approved_by')->references('id')->on('users');
        });

        // العهد (Custody)
        Schema::create('custodies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('item_id')->nullable(); // ربط بالأصول
            $table->string('asset_type'); // laptop, phone, key, etc.
            $table->string('asset_name');
            $table->string('asset_serial')->nullable();
            $table->decimal('asset_value', 12, 2)->default(0);
            $table->date('assigned_date');
            $table->date('expected_return_date')->nullable();
            $table->date('actual_return_date')->nullable();
            $table->enum('status', [
                'assigned',
                'returned',
                'damaged',
                'lost'
            ])->default('assigned');
            $table->enum('condition_on_assign', ['new', 'good', 'fair', 'poor'])->default('good');
            $table->enum('condition_on_return', ['new', 'good', 'fair', 'poor', 'damaged'])->nullable();
            $table->text('notes')->nullable();
            $table->uuid('assigned_by');
            $table->uuid('received_by')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('item_id')->references('id')->on('inventory_items');
            $table->foreign('assigned_by')->references('id')->on('users');
            $table->foreign('received_by')->references('id')->on('users');
            
            $table->index(['employee_id', 'status']);
        });

        // إخلاء الطرف
        Schema::create('clearance_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->date('last_working_day');
            $table->enum('reason', [
                'resignation',
                'termination',
                'end_of_contract',
                'retirement',
                'other'
            ]);
            $table->text('reason_details')->nullable();
            $table->enum('status', [
                'initiated',
                'pending_custody',   // في انتظار استلام العهد
                'pending_finance',   // في انتظار المالية
                'pending_hr',        // في انتظار الموارد البشرية
                'pending_it',        // في انتظار IT
                'completed',
                'cancelled'
            ])->default('initiated');
            $table->boolean('custody_cleared')->default(false);
            $table->boolean('finance_cleared')->default(false);
            $table->boolean('hr_cleared')->default(false);
            $table->boolean('it_cleared')->default(false);
            $table->decimal('final_settlement', 12, 2)->nullable();
            $table->json('clearance_notes')->nullable();
            $table->uuid('initiated_by');
            $table->uuid('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('initiated_by')->references('id')->on('users');
            $table->foreign('completed_by')->references('id')->on('users');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_requests');
        Schema::dropIfExists('custodies');
        Schema::dropIfExists('employee_loans');
        Schema::dropIfExists('payroll_items');
        Schema::dropIfExists('payrolls');
    }
};
