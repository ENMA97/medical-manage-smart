<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الموديول الثاني عشر: التوظيف والاستقطاب (Recruitment & Hiring)
 *
 * يغطي هذا الملف:
 * 1. طلبات التوظيف (job_requisitions)
 * 2. الإعلانات الوظيفية (job_postings)
 * 3. المرشحين (candidates)
 * 4. طلبات التقديم (job_applications)
 * 5. المقابلات (interviews)
 * 6. عروض العمل (job_offers)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. طلبات التوظيف (Job Requisitions)
        // طلب فتح وظيفة جديدة من القسم
        // ─────────────────────────────────────────────
        Schema::create('job_requisitions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('requisition_number')->unique();            // رقم الطلب
            $table->uuid('department_id');
            $table->uuid('position_id');
            $table->uuid('requested_by');                              // مقدّم الطلب
            $table->integer('vacancies')->default(1);                  // عدد الشواغر
            $table->enum('employment_type', [
                'full_time',      // دوام كامل
                'part_time',      // دوام جزئي
                'contract',       // عقد مؤقت
                'internship'      // تدريب
            ])->default('full_time');
            $table->enum('priority', [
                'urgent',         // عاجل
                'high',           // عالي
                'medium',         // متوسط
                'low'             // منخفض
            ])->default('medium');
            $table->text('justification');                             // مبرر الطلب
            $table->text('justification_ar')->nullable();
            $table->text('job_description')->nullable();               // الوصف الوظيفي
            $table->text('job_description_ar')->nullable();
            $table->text('requirements')->nullable();                  // المتطلبات
            $table->text('requirements_ar')->nullable();
            $table->json('qualifications')->nullable();                // المؤهلات المطلوبة
            $table->integer('min_experience_years')->default(0);       // أقل سنوات خبرة
            $table->decimal('salary_range_min', 14, 2)->nullable();    // الحد الأدنى للراتب
            $table->decimal('salary_range_max', 14, 2)->nullable();    // الحد الأعلى للراتب
            $table->date('target_hire_date')->nullable();              // تاريخ التوظيف المستهدف
            $table->enum('status', [
                'draft',          // مسودة
                'pending',        // بانتظار الموافقة
                'approved',       // معتمد
                'open',           // مفتوح للتوظيف
                'on_hold',        // معلّق
                'filled',         // تم شغل الوظيفة
                'cancelled'       // ملغي
            ])->default('draft');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->cascadeOnDelete();
            $table->foreign('requested_by')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
        });

        // ─────────────────────────────────────────────
        // 2. الإعلانات الوظيفية (Job Postings)
        // نشر الوظائف للتقديم
        // ─────────────────────────────────────────────
        Schema::create('job_postings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('requisition_id');
            $table->string('title');                                   // عنوان الإعلان (EN)
            $table->string('title_ar');                                // عنوان الإعلان (AR)
            $table->text('description');                               // الوصف التفصيلي
            $table->text('description_ar')->nullable();
            $table->text('responsibilities')->nullable();              // المهام والمسؤوليات
            $table->text('responsibilities_ar')->nullable();
            $table->text('benefits')->nullable();                      // المزايا
            $table->text('benefits_ar')->nullable();
            $table->json('channels')->nullable();                      // قنوات النشر (website, linkedin, etc.)
            $table->date('publish_date');                               // تاريخ النشر
            $table->date('closing_date');                               // آخر موعد للتقديم
            $table->boolean('is_internal')->default(false);            // توظيف داخلي فقط
            $table->enum('status', [
                'draft',          // مسودة
                'published',      // منشور
                'closed',         // مغلق
                'on_hold',        // معلّق
                'filled'          // تم الشغل
            ])->default('draft');
            $table->integer('views_count')->default(0);                // عدد المشاهدات
            $table->integer('applications_count')->default(0);         // عدد الطلبات
            $table->uuid('published_by')->nullable();
            $table->timestamps();

            $table->foreign('requisition_id')->references('id')->on('job_requisitions')->cascadeOnDelete();
            $table->foreign('published_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['status', 'closing_date']);
        });

        // ─────────────────────────────────────────────
        // 3. المرشحين (Candidates)
        // بيانات المتقدمين للوظائف
        // ─────────────────────────────────────────────
        Schema::create('candidates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('first_name');
            $table->string('last_name');
            $table->string('first_name_ar')->nullable();
            $table->string('last_name_ar')->nullable();
            $table->string('email')->unique();
            $table->string('phone');
            $table->string('phone_secondary')->nullable();
            $table->enum('gender', ['male', 'female'])->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('nationality')->nullable();
            $table->string('nationality_ar')->nullable();
            $table->string('national_id')->nullable();
            $table->string('city')->nullable();
            $table->text('address')->nullable();
            $table->string('current_employer')->nullable();            // جهة العمل الحالية
            $table->string('current_position')->nullable();            // المنصب الحالي
            $table->integer('years_of_experience')->default(0);
            $table->decimal('expected_salary', 14, 2)->nullable();     // الراتب المتوقع
            $table->decimal('current_salary', 14, 2)->nullable();      // الراتب الحالي
            $table->string('highest_education')->nullable();           // أعلى مؤهل
            $table->string('university')->nullable();
            $table->string('major')->nullable();
            $table->string('resume_path')->nullable();                 // مسار السيرة الذاتية
            $table->json('skills')->nullable();                        // المهارات
            $table->json('languages')->nullable();                     // اللغات
            $table->json('certifications')->nullable();                // الشهادات
            $table->enum('source', [
                'website',        // الموقع
                'linkedin',       // لينكد إن
                'referral',       // ترشيح
                'agency',         // وكالة توظيف
                'career_fair',    // معرض توظيف
                'social_media',   // التواصل الاجتماعي
                'walk_in',        // حضور مباشر
                'internal',       // داخلي
                'other'           // أخرى
            ])->nullable();
            $table->uuid('referred_by_employee_id')->nullable();       // موظف مرشِّح
            $table->text('notes')->nullable();
            $table->boolean('is_blacklisted')->default(false);         // في القائمة السوداء
            $table->text('blacklist_reason')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('referred_by_employee_id')->references('id')->on('employees')->nullOnDelete();
        });

        // ─────────────────────────────────────────────
        // 4. طلبات التقديم (Job Applications)
        // تقديم المرشحين على الوظائف
        // ─────────────────────────────────────────────
        Schema::create('job_applications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('application_number')->unique();            // رقم الطلب
            $table->uuid('posting_id');
            $table->uuid('candidate_id');
            $table->enum('status', [
                'received',           // مستلم
                'screening',          // فرز أولي
                'shortlisted',        // مختار للمقابلة
                'interview_scheduled',// تم جدولة مقابلة
                'interviewed',        // تمت المقابلة
                'assessment',         // تقييم / اختبار
                'reference_check',    // فحص المراجع
                'offer_pending',      // بانتظار عرض العمل
                'offered',            // تم إرسال العرض
                'accepted',           // تم القبول
                'rejected',           // مرفوض
                'withdrawn',          // انسحب المرشح
                'on_hold'             // معلّق
            ])->default('received');
            $table->text('cover_letter')->nullable();                  // خطاب التقديم
            $table->decimal('screening_score', 5, 2)->nullable();      // درجة الفرز الأولي
            $table->uuid('screened_by')->nullable();
            $table->timestamp('screened_at')->nullable();
            $table->text('screening_notes')->nullable();
            $table->text('rejection_reason')->nullable();
            $table->uuid('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->json('evaluation_scores')->nullable();             // درجات التقييم
            $table->decimal('overall_score', 5, 2)->nullable();        // الدرجة الإجمالية
            $table->timestamps();

            $table->foreign('posting_id')->references('id')->on('job_postings')->cascadeOnDelete();
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();
            $table->foreign('screened_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('rejected_by')->references('id')->on('users')->nullOnDelete();
            $table->unique(['posting_id', 'candidate_id']);
            $table->index(['status']);
        });

        // ─────────────────────────────────────────────
        // 5. المقابلات (Interviews)
        // ─────────────────────────────────────────────
        Schema::create('interviews', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('application_id');
            $table->integer('round')->default(1);                      // رقم جولة المقابلة
            $table->enum('type', [
                'phone',          // هاتفية
                'video',          // عبر الفيديو
                'in_person',      // حضورية
                'panel',          // لجنة
                'technical',      // تقنية
                'hr'              // موارد بشرية
            ])->default('in_person');
            $table->timestamp('scheduled_at');                         // موعد المقابلة
            $table->integer('duration_minutes')->default(60);          // المدة المتوقعة
            $table->string('location')->nullable();                    // المكان / رابط الاجتماع
            $table->json('interviewers')->nullable();                  // قائمة المحاورين (user_ids)
            $table->uuid('primary_interviewer_id');                    // المحاور الرئيسي
            $table->enum('status', [
                'scheduled',      // مجدول
                'confirmed',      // مؤكد
                'in_progress',    // جاري
                'completed',      // مكتمل
                'cancelled',      // ملغي
                'no_show',        // لم يحضر المرشح
                'rescheduled'     // أُعيد جدولته
            ])->default('scheduled');
            $table->decimal('overall_score', 5, 2)->nullable();        // التقييم العام
            $table->enum('recommendation', [
                'strong_hire',    // توظيف بقوة
                'hire',           // توظيف
                'maybe',          // ربما
                'no_hire',        // عدم التوظيف
                'strong_no_hire'  // عدم التوظيف بقوة
            ])->nullable();
            $table->text('strengths')->nullable();                     // نقاط القوة
            $table->text('concerns')->nullable();                      // الملاحظات
            $table->text('feedback')->nullable();                      // التغذية الراجعة
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('job_applications')->cascadeOnDelete();
            $table->foreign('primary_interviewer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->index(['application_id', 'round']);
        });

        // ─────────────────────────────────────────────
        // 6. عروض العمل (Job Offers)
        // ─────────────────────────────────────────────
        Schema::create('job_offers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('offer_number')->unique();                  // رقم العرض
            $table->uuid('application_id');
            $table->uuid('candidate_id');
            $table->uuid('position_id');
            $table->uuid('department_id');
            $table->decimal('offered_salary', 14, 2);                  // الراتب المعروض
            $table->json('allowances')->nullable();                    // البدلات
            $table->json('benefits')->nullable();                      // المزايا
            $table->enum('contract_type', [
                'permanent',      // دائم
                'fixed_term',     // محدد المدة
                'probation'       // فترة تجربة
            ])->default('permanent');
            $table->integer('contract_duration_months')->nullable();   // مدة العقد بالأشهر
            $table->integer('probation_months')->default(3);           // فترة التجربة بالأشهر
            $table->date('proposed_start_date');                        // تاريخ المباشرة المقترح
            $table->date('offer_valid_until');                          // صلاحية العرض
            $table->text('additional_terms')->nullable();               // شروط إضافية
            $table->text('additional_terms_ar')->nullable();
            $table->enum('status', [
                'draft',          // مسودة
                'pending_approval',// بانتظار الاعتماد
                'approved',       // معتمد
                'sent',           // أُرسل للمرشح
                'accepted',       // قُبل
                'negotiating',    // تفاوض
                'declined',       // رُفض
                'withdrawn',      // سُحب
                'expired'         // انتهت الصلاحية
            ])->default('draft');
            $table->uuid('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->text('decline_reason')->nullable();
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('application_id')->references('id')->on('job_applications')->cascadeOnDelete();
            $table->foreign('candidate_id')->references('id')->on('candidates')->cascadeOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->cascadeOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('job_offers');
        Schema::dropIfExists('interviews');
        Schema::dropIfExists('job_applications');
        Schema::dropIfExists('candidates');
        Schema::dropIfExists('job_postings');
        Schema::dropIfExists('job_requisitions');
    }
};
