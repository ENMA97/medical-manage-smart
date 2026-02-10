<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // الأقسام
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->uuid('parent_id')->nullable(); // للأقسام الفرعية
            $table->uuid('manager_id')->nullable(); // مدير القسم
            $table->string('cost_center_code')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // المناصب الوظيفية
        Schema::create('positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description_ar')->nullable();
            $table->uuid('department_id')->nullable();
            $table->enum('level', ['executive', 'manager', 'supervisor', 'senior', 'junior', 'entry'])->default('entry');
            $table->decimal('min_salary', 10, 2)->nullable();
            $table->decimal('max_salary', 10, 2)->nullable();
            $table->boolean('is_medical')->default(false);
            $table->boolean('requires_license')->default(false);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });

        // الموظفون
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('employee_number')->unique();

            // الاسم
            $table->string('first_name_ar');
            $table->string('first_name_en')->nullable();
            $table->string('middle_name_ar')->nullable();
            $table->string('middle_name_en')->nullable();
            $table->string('last_name_ar');
            $table->string('last_name_en')->nullable();

            // المعرفات
            $table->string('national_id')->nullable()->unique();
            $table->string('iqama_number')->nullable()->unique();
            $table->string('passport_number')->nullable();
            $table->string('nationality', 3)->default('SA'); // ISO 3166-1 alpha-2

            // المعلومات الشخصية
            $table->enum('gender', ['male', 'female']);
            $table->date('date_of_birth')->nullable();
            $table->enum('marital_status', ['single', 'married', 'divorced', 'widowed'])->nullable();

            // معلومات التواصل
            $table->string('email')->nullable()->unique();
            $table->string('phone')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_phone')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();

            // معلومات العمل
            $table->uuid('department_id')->nullable();
            $table->uuid('position_id')->nullable();
            $table->uuid('supervisor_id')->nullable();
            $table->date('hire_date');
            $table->date('probation_end_date')->nullable();
            $table->enum('employment_status', ['active', 'on_leave', 'suspended', 'terminated', 'resigned'])->default('active');
            $table->enum('contract_type', ['full_time', 'part_time', 'contract', 'tamheer', 'locum'])->default('full_time');
            $table->string('work_location')->nullable();

            // للكادر الطبي
            $table->boolean('is_medical_staff')->default(false);
            $table->string('medical_license_number')->nullable();
            $table->string('specialization')->nullable();

            // المعلومات البنكية
            $table->string('bank_name')->nullable();
            $table->string('bank_code', 4)->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('iban', 34)->nullable();

            // GOSI
            $table->string('gosi_number')->nullable();

            // أخرى
            $table->string('profile_photo')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
            $table->foreign('supervisor_id')->references('id')->on('employees')->nullOnDelete();

            $table->index(['department_id', 'is_active']);
            $table->index(['employment_status', 'is_active']);
            $table->index('nationality');
        });

        // ربط المستخدمين بالموظفين
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });

        // ربط مدير القسم
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('manager_id')->references('id')->on('employees')->nullOnDelete();
        });

        // العقود
        Schema::create('contracts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('contract_number')->unique();
            $table->uuid('employee_id');

            $table->enum('type', [
                'full_time',   // دوام كامل
                'part_time',   // دوام جزئي
                'contract',    // عقد مؤقت
                'tamheer',     // تمهير
                'locum'        // بديل
            ]);

            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->boolean('is_indefinite')->default(false); // عقد غير محدد المدة

            // الراتب والبدلات
            $table->decimal('basic_salary', 10, 2);
            $table->decimal('housing_allowance', 10, 2)->default(0);
            $table->decimal('transportation_allowance', 10, 2)->default(0);
            $table->decimal('food_allowance', 10, 2)->default(0);
            $table->decimal('phone_allowance', 10, 2)->default(0);
            $table->decimal('other_allowances', 10, 2)->default(0);
            $table->json('allowance_details')->nullable(); // تفاصيل البدلات الإضافية

            // ساعات العمل
            $table->integer('working_hours_per_week')->default(48);
            $table->integer('working_days_per_week')->default(6);

            // الإجازات
            $table->integer('annual_leave_days')->default(21);
            $table->integer('sick_leave_days')->default(30);

            // للتمهير
            $table->decimal('tamheer_stipend', 10, 2)->nullable();

            // للدوام الجزئي والنسبة المئوية
            $table->decimal('percentage_rate', 5, 2)->nullable(); // نسبة من الإيرادات

            // الحالة
            $table->boolean('is_active')->default(true);
            $table->date('termination_date')->nullable();
            $table->string('termination_reason')->nullable();
            $table->uuid('terminated_by')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->index(['employee_id', 'is_active']);
        });

        // العهد
        Schema::create('custodies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('custody_number')->unique();
            $table->uuid('employee_id');

            $table->enum('type', [
                'equipment',    // معدات
                'vehicle',      // مركبة
                'laptop',       // لابتوب
                'phone',        // هاتف
                'keys',         // مفاتيح
                'card',         // بطاقة
                'uniform',      // زي موحد
                'tools',        // أدوات
                'other'         // أخرى
            ]);

            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();
            $table->string('serial_number')->nullable();
            $table->string('asset_tag')->nullable(); // رقم الأصل

            $table->decimal('value', 10, 2)->default(0);
            $table->string('condition')->default('good'); // good, fair, poor

            $table->date('assigned_date');
            $table->uuid('assigned_by');
            $table->date('expected_return_date')->nullable();

            $table->date('returned_at')->nullable();
            $table->uuid('received_by')->nullable();
            $table->string('return_condition')->nullable();
            $table->text('return_notes')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('assigned_by')->references('id')->on('users');
            $table->foreign('received_by')->references('id')->on('users');

            $table->index(['employee_id', 'returned_at']);
        });

        // طلبات إخلاء الطرف
        Schema::create('clearance_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_number')->unique();
            $table->uuid('employee_id');

            $table->enum('reason', [
                'resignation',      // استقالة
                'termination',      // إنهاء خدمات
                'end_of_contract',  // انتهاء العقد
                'transfer',         // نقل
                'retirement'        // تقاعد
            ]);

            $table->date('last_working_day');
            $table->text('notes')->nullable();

            // حالة الموافقات
            $table->enum('status', [
                'pending',              // قيد الانتظار
                'finance_approved',     // موافقة المالية
                'hr_approved',          // موافقة الموارد البشرية
                'it_approved',          // موافقة تقنية المعلومات
                'custody_cleared',      // تسليم العهد
                'completed',            // مكتمل
                'cancelled'             // ملغي
            ])->default('pending');

            // الموافقات
            $table->uuid('finance_approved_by')->nullable();
            $table->timestamp('finance_approved_at')->nullable();
            $table->text('finance_notes')->nullable();

            $table->uuid('hr_approved_by')->nullable();
            $table->timestamp('hr_approved_at')->nullable();
            $table->text('hr_notes')->nullable();

            $table->uuid('it_approved_by')->nullable();
            $table->timestamp('it_approved_at')->nullable();
            $table->text('it_notes')->nullable();

            $table->uuid('custody_cleared_by')->nullable();
            $table->timestamp('custody_cleared_at')->nullable();
            $table->text('custody_notes')->nullable();

            // المستحقات النهائية
            $table->decimal('final_settlement', 10, 2)->nullable();
            $table->decimal('end_of_service', 10, 2)->nullable();
            $table->decimal('leave_balance_amount', 10, 2)->nullable();
            $table->decimal('deductions', 10, 2)->nullable();

            $table->uuid('completed_by')->nullable();
            $table->timestamp('completed_at')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('clearance_requests');
        Schema::dropIfExists('custodies');
        Schema::dropIfExists('contracts');

        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['parent_id']);
            $table->dropForeign(['manager_id']);
        });

        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });

        Schema::dropIfExists('employees');
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
    }
};
