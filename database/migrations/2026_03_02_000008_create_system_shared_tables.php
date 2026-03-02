<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * الجداول المشتركة والنظامية (System & Shared Tables)
 *
 * يغطي هذا الملف:
 * 1. الأدوار (roles)
 * 2. الصلاحيات (permissions)
 * 3. ربط الأدوار بالصلاحيات (role_permissions)
 * 4. ربط المستخدمين بالأدوار (user_roles)
 * 5. الإشعارات (notifications)
 * 6. سجل المراجعة (audit_logs)
 * 7. إعدادات النظام (system_settings)
 * 8. سجل تدفق العمل (workflow_logs)
 * 9. الإجازات الرسمية (public_holidays)
 * 10. تفويضات الصلاحيات (delegations)
 */
return new class extends Migration
{
    public function up(): void
    {
        // ─────────────────────────────────────────────
        // 1. الأدوار (Roles)
        // ─────────────────────────────────────────────
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();                      // اسم الدور (slug)
            $table->string('display_name');                         // الاسم المعروض (EN)
            $table->string('display_name_ar');                      // الاسم المعروض (AR)
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);           // دور نظامي (لا يمكن حذفه)
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        // ─────────────────────────────────────────────
        // 2. الصلاحيات (Permissions)
        // ─────────────────────────────────────────────
        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();                      // اسم الصلاحية (مثل employees.create)
            $table->string('display_name');
            $table->string('display_name_ar');
            $table->string('module');                               // الموديول (hr, payroll, leaves, etc.)
            $table->string('action');                               // الإجراء (create, read, update, delete, export)
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('module');
        });

        // ─────────────────────────────────────────────
        // 3. ربط الأدوار بالصلاحيات (Role-Permission)
        // ─────────────────────────────────────────────
        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('permission_id');
            $table->timestamps();

            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        // ─────────────────────────────────────────────
        // 4. ربط المستخدمين بالأدوار (User-Role)
        // ─────────────────────────────────────────────
        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('role_id');
            $table->timestamps();

            $table->primary(['user_id', 'role_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });

        // ─────────────────────────────────────────────
        // 5. الإشعارات (Notifications)
        // ─────────────────────────────────────────────
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');                                // المستخدم المستهدف
            $table->enum('channel', [
                'in_app',           // داخل التطبيق
                'email',            // بريد إلكتروني
                'sms',              // رسالة نصية
                'push'              // إشعار دفعي (موبايل)
            ])->default('in_app');
            $table->string('type');                                 // نوع الإشعار (leave_request, contract_alert, etc.)
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->text('body');
            $table->text('body_ar')->nullable();
            $table->string('action_url')->nullable();               // رابط الإجراء
            $table->string('action_type')->nullable();              // نوع الإجراء (approve, view, etc.)

            // الكيان المرتبط
            $table->string('notifiable_type')->nullable();          // نوع الكيان (LeaveRequest, Contract, etc.)
            $table->uuid('notifiable_id')->nullable();              // معرف الكيان

            $table->json('data')->nullable();                       // بيانات إضافية
            $table->timestamp('read_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->boolean('is_sent')->default(false);
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();

            $table->index(['user_id', 'read_at']);
            $table->index(['notifiable_type', 'notifiable_id']);
            $table->index('type');
        });

        // ─────────────────────────────────────────────
        // 6. سجل المراجعة (Audit Logs)
        // سجل غير قابل للتعديل لجميع العمليات الحساسة
        // ─────────────────────────────────────────────
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('auditable_type');                       // نوع الكيان (Employee, Contract, LeaveRequest, etc.)
            $table->uuid('auditable_id');                            // معرف الكيان
            $table->enum('event', [
                'created',
                'updated',
                'deleted',
                'restored',
                'login',
                'logout',
                'failed_login',
                'exported',
                'imported',
                'approved',
                'rejected',
                'printed',
                'viewed'
            ]);
            $table->json('old_values')->nullable();                 // القيم القديمة
            $table->json('new_values')->nullable();                 // القيم الجديدة
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
            // لا يوجد updated_at - السجل غير قابل للتعديل

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('event');
            $table->index('created_at');
        });

        // ─────────────────────────────────────────────
        // 7. إعدادات النظام العامة (System Settings)
        // ─────────────────────────────────────────────
        Schema::create('system_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->enum('type', [
                'string', 'number', 'boolean', 'json', 'date', 'file'
            ])->default('string');
            $table->string('group')->default('general');            // المجموعة (general, hr, payroll, leaves, etc.)
            $table->string('label')->nullable();                    // تسمية الإعداد (EN)
            $table->string('label_ar')->nullable();                 // تسمية الإعداد (AR)
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);           // يمكن عرضه للجميع
            $table->boolean('is_editable')->default(true);          // يمكن تعديله
            $table->timestamps();

            $table->index('group');
        });

        // ─────────────────────────────────────────────
        // 8. سجل تدفق العمل (Workflow Logs)
        // ─────────────────────────────────────────────
        Schema::create('workflow_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('workflowable_type');                    // نوع الكيان
            $table->uuid('workflowable_id');                        // معرف الكيان
            $table->string('from_status');
            $table->string('to_status');
            $table->uuid('performed_by');
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->foreign('performed_by')->references('id')->on('users');
            $table->index(['workflowable_type', 'workflowable_id']);
        });

        // ─────────────────────────────────────────────
        // 9. الإجازات الرسمية (Public Holidays)
        // ─────────────────────────────────────────────
        Schema::create('public_holidays', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('name_ar');
            $table->date('start_date');
            $table->date('end_date');
            $table->integer('year');
            $table->boolean('is_recurring')->default(false);        // يتكرر سنوياً
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['start_date', 'end_date']);
            $table->index('year');
        });

        // ─────────────────────────────────────────────
        // 10. تفويضات الصلاحيات (Delegations)
        // تفويض الموافقات من مستخدم لآخر
        // ─────────────────────────────────────────────
        Schema::create('delegations', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('delegator_id');                           // المفوِّض
            $table->uuid('delegate_id');                             // المفوَّض إليه
            $table->enum('delegation_type', [
                'leave_approval',       // موافقة إجازات
                'contract_approval',    // موافقة عقود
                'payroll_approval',     // موافقة رواتب
                'letter_approval',      // موافقة خطابات
                'full'                  // تفويض كامل
            ]);
            $table->date('start_date');
            $table->date('end_date');
            $table->text('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->uuid('approved_by')->nullable();
            $table->timestamps();

            $table->foreign('delegator_id')->references('id')->on('users');
            $table->foreign('delegate_id')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->index(['delegator_id', 'is_active']);
            $table->index(['delegate_id', 'is_active']);
            $table->index(['start_date', 'end_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('delegations');
        Schema::dropIfExists('public_holidays');
        Schema::dropIfExists('workflow_logs');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
    }
};
