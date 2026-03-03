<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الجداول الأساسية (Foundation Tables)
 *
 * الجداول المرجعية التي تعتمد عليها جميع الموديولات الأخرى:
 * - المستخدمون (users)
 * - الأقسام (departments)
 * - المسميات الوظيفية (positions)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // جدول المستخدمين (Users)
        // المسؤول عن المصادقة وتسجيل الدخول
        // ─────────────────────────────────────────────
        Schema::create('users', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('username')->unique();                          // اسم المستخدم للدخول
            $table->string('email')->unique();                             // البريد الإلكتروني
            $table->timestamp('email_verified_at')->nullable();
            $table->string('password');                                    // كلمة المرور المشفّرة
            $table->string('phone')->nullable();                           // رقم الجوال
            $table->string('full_name');                                   // الاسم الكامل (EN)
            $table->string('full_name_ar')->nullable();                    // الاسم الكامل (AR)
            $table->string('avatar')->nullable();                          // صورة المستخدم
            $table->enum('user_type', [
                'employee',           // موظف
                'admin',              // مسؤول نظام
                'hr_manager',         // مدير موارد بشرية
                'department_manager', // مدير قسم
                'general_manager',    // مدير عام
                'super_admin'         // مسؤول أعلى
            ])->default('employee');
            $table->uuid('employee_id')->nullable();                       // ربط بجدول الموظفين
            $table->string('preferred_language', 5)->default('ar');         // اللغة المفضلة
            $table->boolean('is_active')->default(true);
            $table->boolean('receive_notifications')->default(true);       // استقبال الإشعارات
            $table->boolean('receive_email_notifications')->default(true); // إشعارات البريد
            $table->boolean('receive_sms_notifications')->default(false);  // إشعارات SMS
            $table->string('fcm_token')->nullable();                       // Firebase Cloud Messaging
            $table->timestamp('last_login_at')->nullable();
            $table->string('last_login_ip')->nullable();
            $table->rememberToken();
            $table->timestamps();
            $table->softDeletes();

            $table->index('user_type');
            $table->index('is_active');
        });

        // ─────────────────────────────────────────────
        // جدول الأقسام (Departments)
        // الهيكل التنظيمي للمنشأة - يدعم التسلسل الهرمي
        // ─────────────────────────────────────────────
        Schema::create('departments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();                    // رمز القسم
            $table->string('name');                              // اسم القسم (EN)
            $table->string('name_ar');                           // اسم القسم (AR)
            $table->uuid('parent_id')->nullable();               // القسم الأب (للهيكل الشجري)
            $table->uuid('manager_id')->nullable();              // مدير القسم
            $table->text('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();
        });

        // إضافة المفتاح الخارجي الذاتي بعد إنشاء الجدول
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('departments')->nullOnDelete();
        });

        // ─────────────────────────────────────────────
        // جدول المسميات الوظيفية (Positions / Job Titles)
        // ─────────────────────────────────────────────
        Schema::create('positions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();                    // رمز المسمى
            $table->string('title');                             // المسمى الوظيفي (EN)
            $table->string('title_ar');                          // المسمى الوظيفي (AR)
            $table->uuid('department_id')->nullable();           // القسم الافتراضي
            $table->enum('category', [
                'medical',          // طبي (أطباء، ممرضين)
                'administrative',   // إداري
                'technical',        // تقني
                'support'           // خدمات مساندة
            ])->default('administrative');
            $table->text('description')->nullable();
            $table->text('requirements')->nullable();            // متطلبات الوظيفة
            $table->decimal('min_salary', 12, 2)->nullable();    // الحد الأدنى للراتب
            $table->decimal('max_salary', 12, 2)->nullable();    // الحد الأعلى للراتب
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('positions');
        Schema::dropIfExists('departments');
        Schema::dropIfExists('users');
    }
};
