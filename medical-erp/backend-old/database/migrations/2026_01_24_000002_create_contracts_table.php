<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->string('contract_number')->unique();
            $table->enum('type', [
                'full_time',      // دوام كامل
                'part_time',      // دوام جزئي
                'tamheer',        // تمهير
                'percentage',     // نسبة
                'locum'           // لوكم
            ]);
            $table->decimal('base_salary', 12, 2);
            $table->decimal('housing_allowance', 12, 2)->default(0);
            $table->decimal('transport_allowance', 12, 2)->default(0);
            $table->decimal('other_allowances', 12, 2)->default(0);
            $table->decimal('percentage_rate', 5, 2)->nullable(); // للعقود بنسبة
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->integer('probation_days')->default(90);
            $table->integer('annual_leave_days')->default(21);
            $table->enum('status', ['draft', 'active', 'expired', 'terminated', 'renewed'])->default('draft');
            $table->text('terms')->nullable();
            $table->json('benefits')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users');
            
            $table->index(['employee_id', 'status']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contracts');
    }
};
