<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الموديول الحادي عشر: التدريب والتطوير (Training & Development)
 *
 * يغطي هذا الملف:
 * 1. البرامج التدريبية (training_programs)
 * 2. الدورات التدريبية (training_courses)
 * 3. تسجيل الموظفين في الدورات (training_enrollments)
 * 4. شهادات الموظفين (employee_certificates)
 * 5. مصفوفة المهارات (skill_categories + employee_skills)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. البرامج التدريبية (Training Programs)
        // البرامج العامة التي تحتوي على دورات متعددة
        // ─────────────────────────────────────────────
        Schema::create('training_programs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                                    // اسم البرنامج (EN)
            $table->string('name_ar');                                 // اسم البرنامج (AR)
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->enum('type', [
                'onboarding',     // تهيئة موظف جديد
                'mandatory',      // إلزامي
                'professional',   // تطوير مهني
                'leadership',     // قيادة
                'technical',      // تقني
                'safety',         // سلامة مهنية
                'compliance'      // امتثال
            ])->default('professional');
            $table->uuid('department_id')->nullable();                 // خاص بقسم (أو null لجميع الأقسام)
            $table->integer('year');                                    // سنة الخطة التدريبية
            $table->decimal('budget', 14, 2)->default(0);              // الميزانية المرصودة
            $table->decimal('actual_cost', 14, 2)->default(0);         // التكلفة الفعلية
            $table->enum('status', [
                'planned',        // مخطط
                'active',         // نشط
                'completed',      // مكتمل
                'cancelled'       // ملغي
            ])->default('planned');
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // ─────────────────────────────────────────────
        // 2. الدورات التدريبية (Training Courses)
        // الدورات الفعلية ضمن البرنامج التدريبي
        // ─────────────────────────────────────────────
        Schema::create('training_courses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('program_id')->nullable();                    // البرنامج التدريبي
            $table->string('title');                                   // عنوان الدورة (EN)
            $table->string('title_ar');                                // عنوان الدورة (AR)
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->text('objectives')->nullable();                    // أهداف الدورة
            $table->text('objectives_ar')->nullable();
            $table->enum('delivery_method', [
                'classroom',      // حضوري
                'online',         // عن بُعد
                'blended',        // مدمج
                'on_the_job',     // أثناء العمل
                'self_paced',     // ذاتي
                'workshop',       // ورشة عمل
                'conference'      // مؤتمر
            ])->default('classroom');
            $table->string('provider')->nullable();                    // جهة التدريب
            $table->string('provider_ar')->nullable();
            $table->string('trainer_name')->nullable();                // اسم المدرب
            $table->string('location')->nullable();                    // مكان التدريب
            $table->string('location_ar')->nullable();
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('duration_hours');                          // مدة التدريب بالساعات
            $table->integer('max_participants')->nullable();            // أقصى عدد مشاركين
            $table->decimal('cost_per_person', 14, 2)->default(0);     // التكلفة للفرد
            $table->decimal('total_cost', 14, 2)->default(0);          // التكلفة الإجمالية
            $table->enum('status', [
                'scheduled',      // مجدول
                'open',           // مفتوح للتسجيل
                'in_progress',    // جاري
                'completed',      // مكتمل
                'cancelled'       // ملغي
            ])->default('scheduled');
            $table->boolean('certificate_issued')->default(false);     // يتم إصدار شهادة
            $table->boolean('is_mandatory')->default(false);           // إلزامي
            $table->json('prerequisites')->nullable();                 // متطلبات سابقة
            $table->text('notes')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('program_id')->references('id')->on('training_programs')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['start_date', 'status']);
        });

        // ─────────────────────────────────────────────
        // 3. تسجيل الموظفين في الدورات (Training Enrollments)
        // ─────────────────────────────────────────────
        Schema::create('training_enrollments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('course_id');
            $table->uuid('employee_id');
            $table->enum('status', [
                'nominated',      // مرشّح
                'approved',       // معتمد
                'enrolled',       // مسجّل
                'attending',      // يحضر
                'completed',      // أكمل الدورة
                'failed',         // لم يجتز
                'withdrawn',      // انسحب
                'no_show'         // لم يحضر
            ])->default('nominated');
            $table->uuid('nominated_by')->nullable();                  // من رشّحه
            $table->uuid('approved_by')->nullable();                   // من اعتمد المشاركة
            $table->timestamp('approved_at')->nullable();
            $table->decimal('score', 5, 2)->nullable();                // درجة التقييم بعد الدورة
            $table->decimal('attendance_percentage', 5, 2)->nullable();// نسبة الحضور
            $table->text('feedback')->nullable();                      // رأي الموظف في الدورة
            $table->integer('rating')->nullable();                     // تقييم الدورة (1-5)
            $table->text('manager_feedback')->nullable();              // رأي المدير بعد الدورة
            $table->date('certificate_date')->nullable();              // تاريخ الشهادة
            $table->string('certificate_number')->nullable();          // رقم الشهادة
            $table->timestamps();

            $table->foreign('course_id')->references('id')->on('training_courses')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('nominated_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['course_id', 'employee_id']);
        });

        // ─────────────────────────────────────────────
        // 4. شهادات الموظفين (Employee Certificates)
        // الشهادات المهنية والتراخيص
        // ─────────────────────────────────────────────
        Schema::create('employee_certificates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->string('name');                                    // اسم الشهادة (EN)
            $table->string('name_ar')->nullable();                     // اسم الشهادة (AR)
            $table->enum('type', [
                'professional',   // مهنية (PMP, SHRM, etc.)
                'technical',      // تقنية
                'medical',        // طبية (تصنيف الهيئات)
                'safety',         // سلامة
                'language',       // لغة
                'academic',       // أكاديمية
                'license'         // ترخيص مهني
            ])->default('professional');
            $table->string('issuing_body');                             // الجهة المانحة
            $table->string('issuing_body_ar')->nullable();
            $table->string('certificate_number')->nullable();          // رقم الشهادة
            $table->date('issue_date');                                 // تاريخ الإصدار
            $table->date('expiry_date')->nullable();                   // تاريخ الانتهاء
            $table->boolean('is_verified')->default(false);            // تم التحقق
            $table->uuid('verified_by')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->string('document_path')->nullable();               // مسار ملف الشهادة
            $table->boolean('renewal_required')->default(false);       // يتطلب تجديد
            $table->integer('renewal_reminder_days')->nullable();      // تنبيه قبل الانتهاء
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('verified_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['employee_id', 'expiry_date']);
        });

        // ─────────────────────────────────────────────
        // 5. فئات المهارات (Skill Categories)
        // ─────────────────────────────────────────────
        Schema::create('skill_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                                    // اسم الفئة (EN)
            $table->string('name_ar');                                 // اسم الفئة (AR)
            $table->enum('type', [
                'technical',      // تقنية
                'soft',           // مهارات ناعمة
                'clinical',       // سريرية / إكلينيكية
                'management',     // إدارية
                'language'        // لغوية
            ])->default('technical');
            $table->text('description')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 6. مهارات الموظفين (Employee Skills)
        // مصفوفة المهارات لكل موظف
        // ─────────────────────────────────────────────
        Schema::create('employee_skills', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('skill_category_id');
            $table->string('skill_name');                              // اسم المهارة (EN)
            $table->string('skill_name_ar')->nullable();               // اسم المهارة (AR)
            $table->enum('proficiency_level', [
                'beginner',       // مبتدئ
                'intermediate',   // متوسط
                'advanced',       // متقدم
                'expert'          // خبير
            ])->default('beginner');
            $table->integer('years_of_experience')->default(0);        // سنوات الخبرة
            $table->date('last_assessed_date')->nullable();            // آخر تقييم
            $table->uuid('assessed_by')->nullable();                   // من قيّم
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('skill_category_id')->references('id')->on('skill_categories')->cascadeOnDelete();
            $table->foreign('assessed_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['employee_id', 'skill_category_id', 'skill_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_skills');
        Schema::dropIfExists('skill_categories');
        Schema::dropIfExists('employee_certificates');
        Schema::dropIfExists('training_enrollments');
        Schema::dropIfExists('training_courses');
        Schema::dropIfExists('training_programs');
    }
};
