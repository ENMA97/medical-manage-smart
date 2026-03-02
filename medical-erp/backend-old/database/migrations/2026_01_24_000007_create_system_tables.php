<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // إعدادات الربط مع الأنظمة الخارجية
        Schema::create('integration_configs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('system_name')->unique();
            $table->string('display_name');
            $table->enum('type', [
                'biometric',      // أجهزة البصمة
                'accounting',     // نظام محاسبي
                'insurance',      // شركات التأمين
                'ai',             // خدمات الذكاء الاصطناعي
                'sms',            // خدمات الرسائل
                'payment',        // بوابات الدفع
                'other'
            ]);
            $table->text('api_key')->nullable();
            $table->text('api_secret')->nullable();
            $table->string('base_url')->nullable();
            $table->json('settings')->nullable();
            $table->boolean('is_active')->default(false);
            $table->timestamp('last_tested_at')->nullable();
            $table->enum('last_test_status', ['success', 'failed'])->nullable();
            $table->timestamps();
        });

        // سجل المراجعة (Audit Trail) - غير قابل للتعديل
        Schema::create('audit_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id')->nullable();
            $table->string('user_name')->nullable();
            $table->string('ip_address')->nullable();
            $table->string('user_agent')->nullable();
            $table->string('auditable_type'); // Model class
            $table->uuid('auditable_id');
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
                'rejected'
            ]);
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');
            // لا يوجد updated_at - السجل غير قابل للتعديل

            $table->index(['auditable_type', 'auditable_id']);
            $table->index(['user_id', 'created_at']);
            $table->index('event');
            $table->index('created_at');
        });

        // الصلاحيات والأدوار
        Schema::create('roles', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('display_name_ar');
            $table->text('description')->nullable();
            $table->boolean('is_system')->default(false);
            $table->timestamps();
        });

        Schema::create('permissions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->string('display_name');
            $table->string('display_name_ar');
            $table->string('module');
            $table->text('description')->nullable();
            $table->timestamps();
        });

        Schema::create('role_permissions', function (Blueprint $table) {
            $table->uuid('role_id');
            $table->uuid('permission_id');
            $table->timestamps();

            $table->primary(['role_id', 'permission_id']);
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
            $table->foreign('permission_id')->references('id')->on('permissions')->cascadeOnDelete();
        });

        Schema::create('user_roles', function (Blueprint $table) {
            $table->uuid('user_id');
            $table->uuid('role_id');
            $table->timestamps();

            $table->primary(['user_id', 'role_id']);
            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            $table->foreign('role_id')->references('id')->on('roles')->cascadeOnDelete();
        });

        // إعدادات النظام العامة
        Schema::create('system_settings', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('key')->unique();
            $table->text('value')->nullable();
            $table->string('type')->default('string'); // string, number, boolean, json
            $table->string('group')->default('general');
            $table->text('description')->nullable();
            $table->boolean('is_public')->default(false);
            $table->timestamps();
        });

        // الإشعارات
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('user_id');
            $table->string('type');
            $table->string('title');
            $table->string('title_ar')->nullable();
            $table->text('body');
            $table->text('body_ar')->nullable();
            $table->string('action_url')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->cascadeOnDelete();
            
            $table->index(['user_id', 'read_at']);
        });

        // طلبات الشراء
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_number')->unique();
            $table->uuid('department_id');
            $table->uuid('requested_by');
            $table->text('justification')->nullable();
            $table->decimal('estimated_total', 14, 2)->default(0);
            $table->enum('status', [
                'draft',
                'pending_coordinator',    // منسق
                'pending_admin_manager',  // مدير إداري
                'pending_finance_manager',// مدير مالي
                'pending_purchase',       // الشراء
                'purchased',
                'pending_archive',        // الأرشفة
                'archived',
                'rejected',
                'cancelled'
            ])->default('draft');
            $table->json('approval_chain')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('requested_by')->references('id')->on('users');
        });

        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_request_id');
            $table->uuid('item_id')->nullable();
            $table->string('item_description');
            $table->integer('quantity');
            $table->string('unit');
            $table->decimal('estimated_price', 12, 2)->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('inventory_items');
        });

        // سجل تدفق العمل (Workflow)
        Schema::create('workflow_logs', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('workflowable_type');
            $table->uuid('workflowable_id');
            $table->string('from_status');
            $table->string('to_status');
            $table->uuid('performed_by');
            $table->text('comment')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at');

            $table->index(['workflowable_type', 'workflowable_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_logs');
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('notifications');
        Schema::dropIfExists('system_settings');
        Schema::dropIfExists('user_roles');
        Schema::dropIfExists('role_permissions');
        Schema::dropIfExists('permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('audit_logs');
        Schema::dropIfExists('integration_configs');
    }
};
