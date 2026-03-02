<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // مراكز التكلفة
        Schema::create('cost_centers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_ar');
            $table->uuid('parent_id')->nullable();
            $table->enum('type', ['clinic', 'department', 'support', 'admin']);
            $table->decimal('area_sqm', 10, 2)->nullable(); // المساحة بالمتر المربع
            $table->decimal('overhead_percentage', 5, 2)->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('parent_id')->references('id')->on('cost_centers');
        });

        // الأطباء
        Schema::create('doctors', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->string('license_number')->unique();
            $table->string('specialty');
            $table->string('specialty_ar');
            $table->decimal('consultation_fee', 10, 2)->default(0);
            $table->decimal('commission_rate', 5, 2)->default(0); // نسبة العمولة
            $table->json('working_hours')->nullable();
            $table->boolean('accepts_insurance')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees');
        });

        // الخدمات الطبية
        Schema::create('medical_services', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_ar');
            $table->uuid('category_id');
            $table->uuid('cost_center_id');
            $table->decimal('base_price', 12, 2);
            $table->decimal('cost', 12, 2)->default(0);
            $table->decimal('doctor_commission_rate', 5, 2)->default(0);
            $table->integer('duration_minutes')->default(30);
            $table->boolean('requires_appointment')->default(true);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('service_categories');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
        });

        // جدول الربحية (Fact Table للتحليلات)
        Schema::create('fact_service_profitability', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('doctor_id');
            $table->uuid('service_id');
            $table->uuid('cost_center_id');
            $table->uuid('patient_id');
            $table->uuid('invoice_id');
            $table->date('service_date');
            $table->decimal('gross_revenue', 12, 2);
            $table->decimal('discount', 12, 2)->default(0);
            $table->decimal('net_revenue', 12, 2);
            $table->decimal('doctor_commission', 12, 2)->default(0);
            $table->decimal('material_cost', 12, 2)->default(0);
            $table->decimal('equipment_depreciation', 12, 2)->default(0);
            $table->decimal('overhead_cost', 12, 2)->default(0);
            $table->decimal('net_profit', 12, 2);
            $table->decimal('profit_margin', 5, 2)->default(0);
            $table->string('payment_method')->nullable();
            $table->uuid('insurance_company_id')->nullable();
            $table->timestamp('created_at');

            $table->foreign('doctor_id')->references('id')->on('doctors');
            $table->foreign('service_id')->references('id')->on('medical_services');
            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
            
            $table->index(['service_date', 'cost_center_id']);
            $table->index(['doctor_id', 'service_date']);
        });

        // شركات التأمين
        Schema::create('insurance_companies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name');
            $table->string('name_ar');
            $table->string('contact_person')->nullable();
            $table->string('phone')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->decimal('default_discount', 5, 2)->default(0);
            $table->integer('payment_terms_days')->default(30);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });

        // مطالبات التأمين
        Schema::create('insurance_claims', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('claim_number')->unique();
            $table->uuid('insurance_company_id');
            $table->uuid('patient_id');
            $table->uuid('invoice_id');
            $table->uuid('batch_id')->nullable();
            $table->decimal('claimed_amount', 12, 2);
            $table->decimal('approved_amount', 12, 2)->nullable();
            $table->decimal('rejected_amount', 12, 2)->default(0);
            $table->enum('status', [
                'draft',
                'pending_scrub',   // في انتظار التدقيق
                'scrub_failed',    // فشل التدقيق
                'submitted',       // تم الإرسال
                'acknowledged',    // تم الاستلام
                'partially_approved',
                'approved',
                'rejected',
                'paid',
                'appealed'
            ])->default('draft');
            $table->text('rejection_reason')->nullable();
            $table->string('diagnosis_code')->nullable();
            $table->string('procedure_codes')->nullable();
            $table->date('submission_date')->nullable();
            $table->date('response_date')->nullable();
            $table->date('payment_date')->nullable();
            $table->json('scrubber_results')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
            
            $table->index(['status', 'submission_date']);
            $table->index('batch_id');
        });

        // تسويات عمولات الأطباء (Clawback)
        Schema::create('commission_adjustments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('doctor_id');
            $table->uuid('claim_id');
            $table->uuid('original_service_id');
            $table->decimal('original_commission', 12, 2);
            $table->decimal('deduction_amount', 12, 2);
            $table->decimal('final_commission', 12, 2);
            $table->text('reason');
            $table->enum('status', ['pending', 'applied', 'cancelled'])->default('pending');
            $table->uuid('applied_to_payroll_id')->nullable();
            $table->uuid('created_by');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->foreign('doctor_id')->references('id')->on('doctors');
            $table->foreign('claim_id')->references('id')->on('insurance_claims');
            $table->foreign('created_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');
            
            $table->index(['doctor_id', 'status']);
        });

        // تقرير أعمار الديون (Aging Report)
        Schema::create('aging_snapshots', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('insurance_company_id');
            $table->date('snapshot_date');
            $table->decimal('current_0_30', 12, 2)->default(0);
            $table->decimal('aging_31_60', 12, 2)->default(0);
            $table->decimal('aging_61_90', 12, 2)->default(0);
            $table->decimal('aging_91_120', 12, 2)->default(0);
            $table->decimal('aging_over_120', 12, 2)->default(0);
            $table->decimal('total_outstanding', 12, 2)->default(0);
            $table->integer('claims_count')->default(0);
            $table->timestamp('created_at');

            $table->foreign('insurance_company_id')->references('id')->on('insurance_companies');
            
            $table->unique(['insurance_company_id', 'snapshot_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('aging_snapshots');
        Schema::dropIfExists('commission_adjustments');
        Schema::dropIfExists('insurance_claims');
        Schema::dropIfExists('insurance_companies');
        Schema::dropIfExists('fact_service_profitability');
        Schema::dropIfExists('medical_services');
        Schema::dropIfExists('doctors');
        Schema::dropIfExists('cost_centers');
    }
};
