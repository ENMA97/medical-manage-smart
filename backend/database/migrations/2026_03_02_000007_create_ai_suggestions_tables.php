<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الموديول السادس: الذكاء الاصطناعي والاقتراحات (AI Suggestions)
 *
 * يغطي هذا الملف:
 * 1. سجلات تحليل الذكاء الاصطناعي (ai_analysis_logs)
 * 2. التنبؤات (ai_predictions)
 * 3. التوصيات (ai_recommendations)
 * 4. أنماط الإجازات المكتشفة (leave_patterns)
 * 5. تقارير تحليل التسرب الوظيفي (turnover_risk_scores)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. سجلات تحليل الذكاء الاصطناعي (AI Analysis Logs)
        // يسجل كل عملية تحليل يقوم بها النظام
        // ─────────────────────────────────────────────
        Schema::create('ai_analysis_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('analysis_type', [
                'leave_pattern',           // تحليل أنماط إجازات
                'staffing_prediction',     // التنبؤ بالتوظيف
                'turnover_prediction',     // التنبؤ بالتسرب الوظيفي
                'scheduling_optimization', // تحسين الجدولة
                'absence_analysis',        // تحليل الغياب
                'workload_analysis',       // تحليل عبء العمل
                'seasonal_trend',          // اتجاهات موسمية
                'anomaly_detection'        // كشف الشذوذ
            ]);
            $table->string('model_version')->nullable();           // إصدار النموذج المستخدم
            $table->json('input_parameters');                       // معاملات الإدخال
            $table->json('results');                                 // النتائج
            $table->decimal('confidence_score', 5, 4)->nullable(); // مستوى الثقة (0.0000 - 1.0000)
            $table->integer('data_points_analyzed')->default(0);   // عدد نقاط البيانات
            $table->string('time_range_start')->nullable();
            $table->string('time_range_end')->nullable();
            $table->integer('processing_time_ms')->nullable();     // مدة المعالجة (مللي ثانية)
            $table->enum('status', [
                'pending',         // بانتظار
                'processing',      // جاري المعالجة
                'completed',       // مكتمل
                'failed',          // فشل
                'expired'          // منتهي الصلاحية
            ])->default('pending');
            $table->text('error_message')->nullable();
            $table->uuid('triggered_by')->nullable();              // المستخدم الذي بدأ التحليل
            $table->timestamp('created_at');
            $table->timestamp('completed_at')->nullable();

            $table->foreign('triggered_by')->references('id')->on('users');
            $table->index(['analysis_type', 'status']);
            $table->index('created_at');
        });

        // ─────────────────────────────────────────────
        // 2. التنبؤات (AI Predictions)
        // تنبؤات محددة يولدها النظام
        // ─────────────────────────────────────────────
        Schema::create('ai_predictions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('analysis_log_id');                        // ربط بالتحليل المصدر
            $table->enum('prediction_type', [
                'staff_shortage',           // نقص موظفين
                'leave_peak',               // ذروة إجازات
                'turnover_risk',            // مخاطر تسرب
                'overtime_need',            // حاجة لأوفرتايم
                'hiring_need',              // حاجة للتوظيف
                'budget_overrun'            // تجاوز ميزانية
            ]);
            $table->uuid('department_id')->nullable();              // القسم المتأثر
            $table->date('prediction_date');                         // التاريخ المتوقع
            $table->date('prediction_end_date')->nullable();        // نهاية الفترة المتوقعة
            $table->text('description');
            $table->text('description_ar')->nullable();
            $table->decimal('probability', 5, 4)->nullable();      // الاحتمالية (0-1)
            $table->enum('impact_level', [
                'low', 'medium', 'high', 'critical'
            ])->default('medium');
            $table->json('affected_positions')->nullable();         // المسميات المتأثرة
            $table->integer('affected_count')->nullable();          // العدد المتأثر
            $table->json('suggested_actions')->nullable();          // الإجراءات المقترحة
            $table->boolean('is_acknowledged')->default(false);
            $table->uuid('acknowledged_by')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->boolean('was_accurate')->nullable();            // هل تحقق التنبؤ (للتعلم)
            $table->timestamps();

            $table->foreign('analysis_log_id')->references('id')->on('ai_analysis_logs');
            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('acknowledged_by')->references('id')->on('users');

            $table->index(['prediction_type', 'prediction_date']);
            $table->index(['department_id', 'prediction_date']);
        });

        // ─────────────────────────────────────────────
        // 3. التوصيات (AI Recommendations)
        // توصيات استباقية للموارد البشرية
        // ─────────────────────────────────────────────
        Schema::create('ai_recommendations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('analysis_log_id')->nullable();
            $table->enum('recommendation_type', [
                'scheduling',              // تحسين الجدولة
                'leave_redistribution',    // إعادة توزيع الإجازات
                'hiring',                  // توظيف
                'retention',               // استبقاء
                'training',                // تدريب
                'workload_balance',        // توازن عبء العمل
                'policy_change',           // تغيير سياسة
                'cost_saving'              // توفير تكاليف
            ]);
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->text('description');
            $table->text('description_ar')->nullable();
            $table->enum('priority', [
                'low', 'medium', 'high', 'urgent'
            ])->default('medium');
            $table->json('supporting_data')->nullable();            // البيانات الداعمة
            $table->json('action_steps')->nullable();               // خطوات التنفيذ المقترحة
            $table->decimal('estimated_impact', 12, 2)->nullable(); // الأثر المتوقع (مالي)
            $table->string('impact_unit')->nullable();              // وحدة الأثر (ريال، %)
            $table->enum('status', [
                'new',             // جديد
                'under_review',    // قيد المراجعة
                'accepted',        // مقبول
                'rejected',        // مرفوض
                'implemented',     // تم التنفيذ
                'expired'          // منتهي
            ])->default('new');
            $table->uuid('reviewed_by')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->text('review_notes')->nullable();
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->foreign('analysis_log_id')->references('id')->on('ai_analysis_logs');
            $table->foreign('reviewed_by')->references('id')->on('users');

            $table->index(['recommendation_type', 'status']);
            $table->index('priority');
        });

        // ─────────────────────────────────────────────
        // 4. أنماط الإجازات المكتشفة (Leave Patterns)
        // الأنماط المتكررة في سلوكيات الإجازات
        // ─────────────────────────────────────────────
        Schema::create('leave_patterns', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->enum('pattern_type', [
                'seasonal_peak',           // ذروة موسمية
                'day_of_week',             // أنماط أيام الأسبوع
                'before_after_holiday',    // قبل/بعد الإجازات الرسمية
                'department_cluster',      // تجمع قسمي
                'individual_recurring',    // نمط فردي متكرر
                'cascading',               // تتابعي (موظفون يأخذون إجازات متتالية)
                'conflict_prone'           // معرض للتعارض
            ]);
            $table->uuid('department_id')->nullable();
            $table->uuid('employee_id')->nullable();                // null = نمط عام
            $table->string('pattern_description');
            $table->string('pattern_description_ar')->nullable();
            $table->json('pattern_data');                            // تفاصيل النمط
            $table->decimal('confidence', 5, 4);                    // مستوى الثقة
            $table->integer('occurrences');                          // عدد مرات التكرار
            $table->json('affected_periods');                        // الفترات المتأثرة
            $table->decimal('impact_score', 5, 2)->nullable();     // درجة التأثير (0-100)
            $table->boolean('is_active')->default(true);
            $table->timestamp('first_detected_at');
            $table->timestamp('last_detected_at');
            $table->timestamps();

            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('employee_id')->references('id')->on('employees');

            $table->index(['pattern_type', 'is_active']);
            $table->index('department_id');
        });

        // ─────────────────────────────────────────────
        // 5. درجات مخاطر التسرب الوظيفي (Turnover Risk Scores)
        // تقييم مخاطر مغادرة كل موظف
        // ─────────────────────────────────────────────
        Schema::create('turnover_risk_scores', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('employee_id');
            $table->uuid('analysis_log_id')->nullable();
            $table->decimal('risk_score', 5, 4);                   // درجة المخاطرة (0-1)
            $table->enum('risk_level', [
                'low', 'moderate', 'high', 'very_high'
            ]);
            $table->json('risk_factors');                            // عوامل المخاطرة
            // مثال: {"tenure_short": 0.3, "high_absence": 0.2, "no_promotion": 0.15}
            $table->json('recommended_actions')->nullable();        // الإجراءات المقترحة
            $table->date('assessment_date');
            $table->date('valid_until');                             // صالح حتى
            $table->boolean('is_latest')->default(true);            // أحدث تقييم
            $table->boolean('action_taken')->default(false);
            $table->uuid('action_by')->nullable();
            $table->text('action_notes')->nullable();
            $table->timestamps();

            $table->foreign('employee_id')->references('id')->on('employees');
            $table->foreign('analysis_log_id')->references('id')->on('ai_analysis_logs');
            $table->foreign('action_by')->references('id')->on('users');

            $table->index(['employee_id', 'is_latest']);
            $table->index(['risk_level', 'is_latest']);
            $table->index('assessment_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnover_risk_scores');
        Schema::dropIfExists('leave_patterns');
        Schema::dropIfExists('ai_recommendations');
        Schema::dropIfExists('ai_predictions');
        Schema::dropIfExists('ai_analysis_logs');
    }
};
