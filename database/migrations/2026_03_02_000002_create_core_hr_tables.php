<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الموديول الأول: إدارة ملفات الموظفين المركزية (Core HR & Employee Profiles)
 *
 * يغطي هذا الملف:
 * 1. الموظفون (employees) - السجل الرقمي الشامل
 * 2. وثائق الموظفين (employee_documents) - رفع وأرشفة المستندات
 * 3. جهات الاتصال الطارئة (employee_emergency_contacts)
 * 4. المؤهلات والشهادات (employee_qualifications)
 * 5. الخبرات السابقة (employee_experiences)
 * 6. نموذج مباشرة العمل (employee_onboardings)
 * 7. ملاحظات الموظف (employee_notes)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. جدول الموظفين الرئيسي (Employees)
        // السجل الرقمي الشامل لكل موظف
        // ─────────────────────────────────────────────
        Schema::create('employees', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // البيانات الوظيفية
            $table->string('employee_number')->unique();              // الرقم الوظيفي (تلقائي)
            $table->uuid('department_id');                             // القسم
            $table->uuid('position_id');                               // المسمى الوظيفي
            $table->uuid('direct_manager_id')->nullable();            // المشرف المباشر
            $table->date('hire_date');                                  // تاريخ الالتحاق
            $table->date('actual_start_date')->nullable();            // تاريخ المباشرة الفعلي
            $table->date('termination_date')->nullable();             // تاريخ انتهاء الخدمة
            $table->enum('employment_type', [
                'full_time',       // دوام كامل
                'part_time',       // دوام جزئي
                'contract',        // عقد مؤقت
                'tamheer',         // تمهير
                'locum'            // لوكم (بديل)
            ])->default('full_time');
            $table->enum('status', [
                'active',          // نشط
                'inactive',        // غير نشط
                'on_leave',        // في إجازة
                'suspended',       // موقوف
                'terminated',      // منتهي الخدمة
                'pending_onboard'  // بانتظار المباشرة
            ])->default('pending_onboard');

            // البيانات الشخصية
            $table->string('first_name');                              // الاسم الأول (EN)
            $table->string('second_name')->nullable();                // الاسم الثاني (EN)
            $table->string('third_name')->nullable();                 // الاسم الثالث (EN)
            $table->string('last_name');                               // اسم العائلة (EN)
            $table->string('first_name_ar')->nullable();              // الاسم الأول (AR)
            $table->string('second_name_ar')->nullable();
            $table->string('third_name_ar')->nullable();
            $table->string('last_name_ar')->nullable();               // اسم العائلة (AR)
            $table->string('full_name')->virtualAs(
                "CONCAT(first_name, ' ', COALESCE(second_name, ''), ' ', COALESCE(third_name, ''), ' ', last_name)"
            )->nullable();
            $table->enum('gender', ['male', 'female']);
            $table->date('date_of_birth')->nullable();
            $table->string('place_of_birth')->nullable();
            $table->enum('marital_status', [
                'single', 'married', 'divorced', 'widowed'
            ])->nullable();
            $table->integer('dependents_count')->default(0);          // عدد المعالين

            // بيانات الهوية
            $table->string('national_id')->unique();                  // رقم الهوية الوطنية / الإقامة
            $table->enum('id_type', [
                'national_id',      // هوية وطنية
                'iqama',            // إقامة
                'passport',         // جواز سفر
                'border_number'     // رقم حدود
            ])->default('national_id');
            $table->date('id_expiry_date')->nullable();               // تاريخ انتهاء الهوية
            $table->string('passport_number')->nullable();
            $table->date('passport_expiry_date')->nullable();
            $table->string('nationality');                             // الجنسية
            $table->string('nationality_ar')->nullable();

            // بيانات الاتصال
            $table->string('email')->unique();
            $table->string('personal_email')->nullable();
            $table->string('phone');                                   // رقم الجوال
            $table->string('phone_secondary')->nullable();            // رقم بديل
            $table->text('address')->nullable();                       // العنوان
            $table->string('city')->nullable();
            $table->string('postal_code')->nullable();

            // البيانات المالية
            $table->string('bank_name')->nullable();
            $table->string('bank_account_number')->nullable();
            $table->string('iban')->nullable();                        // رقم الآيبان
            $table->string('gosi_number')->nullable();                // رقم التأمينات الاجتماعية

            // بيانات إضافية
            $table->string('photo')->nullable();                       // صورة الموظف
            $table->enum('blood_type', [
                'A+', 'A-', 'B+', 'B-', 'AB+', 'AB-', 'O+', 'O-'
            ])->nullable();
            $table->text('medical_conditions')->nullable();           // حالات طبية خاصة
            $table->json('metadata')->nullable();                      // بيانات إضافية مرنة

            $table->timestamps();
            $table->softDeletes();

            // المفاتيح الخارجية
            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('position_id')->references('id')->on('positions');
            $table->foreign('direct_manager_id')->references('id')->on('employees')->nullOnDelete();

            // الفهارس
            $table->index(['department_id', 'status']);
            $table->index('hire_date');
            $table->index('nationality');
            $table->index('employment_type');
            $table->index('status');
        });

        // ربط users.employee_id بجدول employees
        Schema::table('users', function (Blueprint $table) {
            $table->foreign('employee_id')->references('id')->on('employees')->nullOnDelete();
        });

        // ربط departments.manager_id بجدول employees
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('employees')->nullOnDelete();
        });

        // ─────────────────────────────────────────────
        // 2. وثائق الموظفين (Employee Documents)
        // رفع، حفظ، أرشفة، وعرض جميع مستندات الموظف
        // ─────────────────────────────────────────────
        Schema::create('employee_documents', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->enum('document_type', [
                'contract',             // عقد عمل
                'national_id',          // صورة الهوية
                'iqama',                // صورة الإقامة
                'passport',             // جواز السفر
                'medical_certificate',  // شهادة طبية
                'qualification',        // شهادة مؤهل
                'experience_letter',    // شهادة خبرة
                'driving_license',      // رخصة قيادة
                'professional_license', // ترخيص مهني
                'photo',                // صورة شخصية
                'form',                 // نموذج ورقي
                'other'                 // أخرى
            ]);
            $table->string('document_name');                      // اسم المستند
            $table->string('document_name_ar')->nullable();       // اسم المستند (AR)
            $table->string('file_path');                           // مسار الملف
            $table->string('file_name');                           // اسم الملف الأصلي
            $table->string('mime_type');                            // نوع الملف
            $table->bigInteger('file_size');                        // حجم الملف (بالبايت)
            $table->string('document_number')->nullable();        // رقم المستند (إن وجد)
            $table->date('issue_date')->nullable();                // تاريخ الإصدار
            $table->date('expiry_date')->nullable();               // تاريخ الانتهاء
            $table->boolean('is_verified')->default(false);        // تم التحقق
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->boolean('is_archived')->default(false);        // مؤرشف
            $table->text('notes')->nullable();
            $table->uuid('uploaded_by');
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('uploaded_by')->references('id')->on('users');
            $table->foreign('verified_by')->references('id')->on('users');

            $table->index(['employee_id', 'document_type']);
            $table->index('expiry_date');
        });

        // ─────────────────────────────────────────────
        // 3. جهات الاتصال الطارئة (Emergency Contacts)
        // ─────────────────────────────────────────────
        Schema::create('employee_emergency_contacts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->string('contact_name');
            $table->string('relationship');                    // صلة القرابة
            $table->string('phone');
            $table->string('phone_secondary')->nullable();
            $table->string('email')->nullable();
            $table->text('address')->nullable();
            $table->boolean('is_primary')->default(false);     // جهة الاتصال الرئيسية
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->index('employee_id');
        });

        // ─────────────────────────────────────────────
        // 4. المؤهلات والشهادات (Qualifications)
        // ─────────────────────────────────────────────
        Schema::create('employee_qualifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->enum('qualification_type', [
                'diploma',          // دبلوم
                'bachelors',        // بكالوريوس
                'masters',          // ماجستير
                'doctorate',        // دكتوراه
                'fellowship',       // زمالة
                'board',            // بورد
                'certificate',      // شهادة مهنية
                'other'
            ]);
            $table->string('institution');                     // الجهة المانحة
            $table->string('field_of_study');                  // التخصص
            $table->date('start_date')->nullable();
            $table->date('completion_date')->nullable();
            $table->string('grade')->nullable();               // التقدير
            $table->string('certificate_number')->nullable();
            $table->uuid('document_id')->nullable();           // ربط بالوثيقة المرفوعة
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('document_id')->references('id')->on('employee_documents')->nullOnDelete();
        });

        // ─────────────────────────────────────────────
        // 5. الخبرات السابقة (Previous Experience)
        // ─────────────────────────────────────────────
        Schema::create('employee_experiences', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->string('company_name');
            $table->string('job_title');
            $table->date('start_date');
            $table->date('end_date')->nullable();
            $table->text('responsibilities')->nullable();
            $table->string('leaving_reason')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
        });

        // ─────────────────────────────────────────────
        // 6. نموذج مباشرة العمل (Onboarding)
        // أتمتة إجراءات المباشرة
        // ─────────────────────────────────────────────
        Schema::create('employee_onboardings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->enum('status', [
                'pending',          // بانتظار البدء
                'in_progress',      // جاري التنفيذ
                'completed',        // مكتمل
                'cancelled'         // ملغي
            ])->default('pending');

            // قائمة مهام المباشرة (Checklist)
            $table->boolean('profile_completed')->default(false);       // اكتمال الملف الشخصي
            $table->boolean('documents_submitted')->default(false);     // تسليم الوثائق
            $table->boolean('contract_signed')->default(false);         // توقيع العقد
            $table->boolean('bank_info_provided')->default(false);      // تقديم بيانات البنك
            $table->boolean('it_setup_done')->default(false);           // إعداد الحساب التقني
            $table->boolean('workspace_assigned')->default(false);      // تجهيز مكان العمل
            $table->boolean('orientation_completed')->default(false);   // اجتياز التعريف
            $table->boolean('policies_acknowledged')->default(false);   // الاطلاع على السياسات

            $table->date('expected_start_date')->nullable();
            $table->date('completed_date')->nullable();
            $table->uuid('assigned_to')->nullable();           // المسؤول عن المباشرة
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('assigned_to')->references('id')->on('users');

            $table->unique('employee_id');
        });

        // ─────────────────────────────────────────────
        // 7. ملاحظات وسجلات الموظف (Employee Notes)
        // ─────────────────────────────────────────────
        Schema::create('employee_notes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->enum('note_type', [
                'general',          // ملاحظة عامة
                'performance',      // أداء
                'disciplinary',     // تأديبي
                'commendation',     // تقدير
                'warning',          // إنذار
                'other'
            ])->default('general');
            $table->string('title');
            $table->text('content');
            $table->boolean('is_confidential')->default(false); // سري (يظهر فقط لـ HR)
            $table->uuid('created_by');
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('created_by')->references('id')->on('users');

            $table->index(['employee_id', 'note_type']);
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
        });
        Schema::dropIfExists('employee_notes');
        Schema::dropIfExists('employee_onboardings');
        Schema::dropIfExists('employee_experiences');
        Schema::dropIfExists('employee_qualifications');
        Schema::dropIfExists('employee_emergency_contacts');
        Schema::dropIfExists('employee_documents');
        Schema::dropIfExists('employees');
    }
};
