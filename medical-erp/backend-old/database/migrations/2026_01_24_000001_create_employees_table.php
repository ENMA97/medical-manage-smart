<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('employee_number')->unique();
            $table->string('name');
            $table->string('name_ar')->nullable();
            $table->string('email')->unique();
            $table->string('phone')->nullable();
            $table->string('national_id')->unique();
            $table->date('birth_date')->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->string('nationality')->nullable();
            $table->uuid('department_id');
            $table->uuid('position_id');
            $table->date('hire_date');
            $table->date('termination_date')->nullable();
            $table->enum('status', ['active', 'inactive', 'suspended', 'terminated'])->default('active');
            $table->string('bank_name')->nullable();
            $table->string('iban')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact')->nullable();
            $table->string('emergency_phone')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('position_id')->references('id')->on('positions');
            
            $table->index(['department_id', 'status']);
            $table->index('hire_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employees');
    }
};
