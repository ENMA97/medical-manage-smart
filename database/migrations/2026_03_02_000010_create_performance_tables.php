<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الموديول العاشر: تقييم الأداء (Performance Appraisal)
 *
 * يغطي هذا الملف:
 * 1. دورات التقييم (appraisal_cycles)
 * 2. نماذج التقييم (appraisal_templates)
 * 3. معايير التقييم (appraisal_criteria)
 * 4. تقييمات الموظفين (employee_appraisals)
 * 5. درجات المعايير (appraisal_scores)
 * 6. أهداف الموظفين (employee_goals)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. دورات التقييم (Appraisal Cycles)
        // فترات التقييم السنوية أو النصف سنوية
        // ─────────────────────────────────────────────
        Schema::create('appraisal_cycles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                                    // اسم الدورة (EN)
            $table->string('name_ar');                                 // اسم الدورة (AR)
            $table->enum('type', [
                'annual',         // سنوي
                'semi_annual',    // نصف سنوي
                'quarterly',      // ربع سنوي
                'probation',      // فترة تجربة
                'project_based'   // حسب المشروع
            ])->default('annual');
            $table->date('start_date');                                // بداية فترة التقييم
            $table->date('end_date');                                  // نهاية فترة التقييم
            $table->date('review_deadline');                           // آخر موعد لإتمام التقييم
            $table->enum('status', [
                'draft',          // مسودة
                'active',         // نشط
                'in_review',      // قيد المراجعة
                'completed',      // مكتمل
                'cancelled'       // ملغي
            ])->default('draft');
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->boolean('self_evaluation_enabled')->default(true); // تقييم ذاتي
            $table->boolean('peer_evaluation_enabled')->default(false);// تقييم زملاء
            $table->uuid('created_by')->nullable();
            $table->timestamps();

            $table->foreign('created_by')->references('id')->on('users')->nullOnDelete();
        });

        // ─────────────────────────────────────────────
        // 2. نماذج التقييم (Appraisal Templates)
        // قوالب جاهزة للتقييم حسب القسم أو المنصب
        // ─────────────────────────────────────────────
        Schema::create('appraisal_templates', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');                                    // اسم النموذج (EN)
            $table->string('name_ar');                                 // اسم النموذج (AR)
            $table->text('description')->nullable();
            $table->uuid('department_id')->nullable();                 // خاص بقسم معين
            $table->uuid('position_id')->nullable();                   // خاص بمنصب معين
            $table->decimal('total_weight', 5, 2)->default(100);       // مجموع الأوزان
            $table->enum('rating_scale', [
                'scale_5',        // من 1 إلى 5
                'scale_10',       // من 1 إلى 10
                'scale_100',      // من 1 إلى 100
                'descriptive'     // وصفي (ممتاز، جيد جداً، ...)
            ])->default('scale_5');
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('position_id')->references('id')->on('positions')->nullOnDelete();
        });

        // ─────────────────────────────────────────────
        // 3. معايير التقييم (Appraisal Criteria)
        // العناصر التي يتم تقييم الموظف عليها
        // ─────────────────────────────────────────────
        Schema::create('appraisal_criteria', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('template_id');
            $table->string('name');                                    // اسم المعيار (EN)
            $table->string('name_ar');                                 // اسم المعيار (AR)
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->enum('category', [
                'competency',     // كفاءات
                'behavior',       // سلوك
                'achievement',    // إنجازات
                'skills',         // مهارات
                'leadership',     // قيادة
                'teamwork',       // عمل جماعي
                'communication',  // تواصل
                'innovation'      // ابتكار
            ])->default('competency');
            $table->decimal('weight', 5, 2)->default(0);               // الوزن النسبي %
            $table->decimal('max_score', 5, 2);                        // أقصى درجة
            $table->integer('sort_order')->default(0);
            $table->boolean('is_required')->default(true);
            $table->timestamps();

            $table->foreign('template_id')->references('id')->on('appraisal_templates')->cascadeOnDelete();
        });

        // ─────────────────────────────────────────────
        // 4. تقييمات الموظفين (Employee Appraisals)
        // التقييم الفعلي لكل موظف
        // ─────────────────────────────────────────────
        Schema::create('employee_appraisals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('cycle_id');
            $table->uuid('employee_id');
            $table->uuid('template_id');
            $table->uuid('reviewer_id');                               // المقيِّم (المدير المباشر)
            $table->enum('status', [
                'pending',            // بانتظار التقييم
                'self_review',        // تقييم ذاتي جاري
                'manager_review',     // تقييم المدير جاري
                'hr_review',          // مراجعة الموارد البشرية
                'completed',          // مكتمل
                'acknowledged'        // اطلع عليه الموظف
            ])->default('pending');

            // الدرجات
            $table->decimal('self_score', 5, 2)->nullable();           // درجة التقييم الذاتي
            $table->decimal('manager_score', 5, 2)->nullable();        // درجة تقييم المدير
            $table->decimal('final_score', 5, 2)->nullable();          // الدرجة النهائية
            $table->enum('final_rating', [
                'outstanding',        // ممتاز
                'exceeds',            // يفوق التوقعات
                'meets',              // يلبي التوقعات
                'needs_improvement',  // يحتاج تحسين
                'unsatisfactory'      // غير مرضي
            ])->nullable();

            // ملاحظات
            $table->text('self_comments')->nullable();                 // ملاحظات الموظف
            $table->text('manager_comments')->nullable();              // ملاحظات المدير
            $table->text('hr_comments')->nullable();                   // ملاحظات HR
            $table->text('improvement_plan')->nullable();              // خطة التحسين
            $table->text('strengths')->nullable();                     // نقاط القوة
            $table->text('weaknesses')->nullable();                    // نقاط الضعف

            // تواريخ
            $table->timestamp('self_review_date')->nullable();
            $table->timestamp('manager_review_date')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();

            $table->foreign('cycle_id')->references('id')->on('appraisal_cycles')->cascadeOnDelete();
            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('template_id')->references('id')->on('appraisal_templates')->cascadeOnDelete();
            $table->foreign('reviewer_id')->references('id')->on('users')->cascadeOnDelete();
            $table->unique(['cycle_id', 'employee_id']);
        });

        // ─────────────────────────────────────────────
        // 5. درجات المعايير (Appraisal Scores)
        // درجة كل معيار لكل تقييم
        // ─────────────────────────────────────────────
        Schema::create('appraisal_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('appraisal_id');
            $table->uuid('criteria_id');
            $table->decimal('self_score', 5, 2)->nullable();           // درجة التقييم الذاتي
            $table->decimal('manager_score', 5, 2)->nullable();        // درجة تقييم المدير
            $table->decimal('final_score', 5, 2)->nullable();          // الدرجة النهائية
            $table->text('self_comment')->nullable();
            $table->text('manager_comment')->nullable();
            $table->timestamps();

            $table->foreign('appraisal_id')->references('id')->on('employee_appraisals')->cascadeOnDelete();
            $table->foreign('criteria_id')->references('id')->on('appraisal_criteria')->cascadeOnDelete();
            $table->unique(['appraisal_id', 'criteria_id']);
        });

        // ─────────────────────────────────────────────
        // 6. أهداف الموظفين (Employee Goals)
        // الأهداف الفردية المرتبطة بالأداء
        // ─────────────────────────────────────────────
        Schema::create('employee_goals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('cycle_id')->nullable();                      // مرتبط بدورة تقييم
            $table->string('title');                                   // عنوان الهدف (EN)
            $table->string('title_ar')->nullable();                    // عنوان الهدف (AR)
            $table->text('description')->nullable();
            $table->text('description_ar')->nullable();
            $table->enum('type', [
                'performance',    // أداء
                'development',    // تطوير
                'project',        // مشروع
                'behavioral'      // سلوكي
            ])->default('performance');
            $table->enum('priority', [
                'critical',       // حرج
                'high',           // عالي
                'medium',         // متوسط
                'low'             // منخفض
            ])->default('medium');
            $table->date('start_date');
            $table->date('target_date');                               // الموعد المستهدف
            $table->date('completed_date')->nullable();
            $table->integer('progress_percentage')->default(0);        // نسبة الإنجاز
            $table->decimal('weight', 5, 2)->default(0);               // الوزن في التقييم
            $table->enum('status', [
                'draft',          // مسودة
                'active',         // نشط
                'on_track',       // في المسار الصحيح
                'at_risk',        // في خطر
                'behind',         // متأخر
                'completed',      // مكتمل
                'cancelled'       // ملغي
            ])->default('draft');
            $table->json('milestones')->nullable();                    // مراحل إنجاز
            $table->json('key_results')->nullable();                   // نتائج مفتاحية (OKR)
            $table->text('manager_feedback')->nullable();
            $table->uuid('approved_by')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
            $table->foreign('cycle_id')->references('id')->on('appraisal_cycles')->nullOnDelete();
            $table->foreign('approved_by')->references('id')->on('users')->nullOnDelete();
            $table->index(['employee_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_goals');
        Schema::dropIfExists('appraisal_scores');
        Schema::dropIfExists('employee_appraisals');
        Schema::dropIfExists('appraisal_criteria');
        Schema::dropIfExists('appraisal_templates');
        Schema::dropIfExists('appraisal_cycles');
    }
};
