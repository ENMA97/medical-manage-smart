<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // المستودعات
        Schema::create('warehouses', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();

            $table->enum('type', [
                'main',         // رئيسي
                'pharmacy',     // صيدلية
                'department',   // قسم
                'crash_cart',   // عربة الطوارئ
                'consignment',  // أمانة
                'quarantine',   // حجر صحي
                'expired'       // منتهي الصلاحية
            ]);

            $table->string('location')->nullable();
            $table->uuid('department_id')->nullable();
            $table->uuid('manager_id')->nullable();

            $table->boolean('is_active')->default(true);
            $table->boolean('requires_approval')->default(false);
            $table->boolean('track_batch')->default(true);
            $table->boolean('track_expiry')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('department_id')->references('id')->on('departments')->nullOnDelete();
            $table->foreign('manager_id')->references('id')->on('employees')->nullOnDelete();
        });

        // فئات الأصناف
        Schema::create('item_categories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->uuid('parent_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->foreign('parent_id')->references('id')->on('item_categories')->nullOnDelete();
        });

        // الأصناف
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('code')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->string('name_ar');
            $table->string('name_en')->nullable();
            $table->text('description')->nullable();

            $table->uuid('category_id')->nullable();

            $table->enum('type', [
                'medicine',         // دواء
                'consumable',       // مستهلك
                'equipment',        // معدات
                'surgical',         // جراحي
                'laboratory',       // مختبري
                'radiology',        // أشعة
                'other'             // أخرى
            ])->default('consumable');

            // الوحدات
            $table->string('unit')->default('piece'); // piece, box, bottle, etc.
            $table->string('secondary_unit')->nullable();
            $table->decimal('conversion_rate', 8, 2)->default(1);

            // للأدوية
            $table->string('generic_name')->nullable();
            $table->string('manufacturer')->nullable();
            $table->string('strength')->nullable();
            $table->string('dosage_form')->nullable();

            // المخزون
            $table->decimal('reorder_level', 10, 2)->default(0);
            $table->decimal('max_stock', 10, 2)->nullable();
            $table->decimal('min_stock', 10, 2)->default(0);

            // السعر
            $table->decimal('cost_price', 10, 2)->default(0);
            $table->decimal('selling_price', 10, 2)->default(0);

            // التتبع
            $table->boolean('track_batch')->default(false);
            $table->boolean('track_expiry')->default(false);
            $table->boolean('is_controlled')->default(false); // مواد خاضعة للرقابة
            $table->boolean('requires_prescription')->default(false);
            $table->boolean('is_active')->default(true);

            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('item_categories')->nullOnDelete();
        });

        // مخزون المستودعات
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->uuid('item_id');
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();

            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('reserved_quantity', 10, 2)->default(0);
            $table->decimal('available_quantity', 10, 2)->default(0);

            $table->decimal('cost_price', 10, 2)->default(0);
            $table->string('location_in_warehouse')->nullable(); // الموقع داخل المستودع

            $table->integer('version')->default(1); // للـ optimistic locking
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('inventory_items')->cascadeOnDelete();

            $table->unique(['warehouse_id', 'item_id', 'batch_number'], 'unique_stock');
            $table->index(['item_id', 'expiry_date']); // للـ FEFO
        });

        // حركات المخزون
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('movement_number')->unique();

            $table->uuid('item_id');
            $table->uuid('from_warehouse_id')->nullable();
            $table->uuid('to_warehouse_id')->nullable();
            $table->string('batch_number')->nullable();

            $table->enum('type', [
                'receive',      // استلام
                'issue',        // صرف
                'transfer',     // تحويل
                'adjust_in',    // تعديل (زيادة)
                'adjust_out',   // تعديل (نقص)
                'return',       // إرجاع
                'expired',      // انتهاء صلاحية
                'damaged'       // تالف
            ]);

            $table->decimal('quantity', 10, 2);
            $table->decimal('unit_cost', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2)->default(0);

            $table->date('expiry_date')->nullable();
            $table->string('reference_type')->nullable(); // purchase_order, patient, department, etc.
            $table->uuid('reference_id')->nullable();
            $table->text('reason')->nullable();
            $table->text('notes')->nullable();

            $table->uuid('performed_by');
            $table->uuid('approved_by')->nullable();

            $table->timestamp('created_at')->useCurrent();
            // لا يوجد updated_at - السجل غير قابل للتعديل

            $table->foreign('item_id')->references('id')->on('inventory_items');
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses');
            $table->foreign('performed_by')->references('id')->on('users');
            $table->foreign('approved_by')->references('id')->on('users');

            $table->index(['item_id', 'created_at']);
            $table->index(['from_warehouse_id', 'created_at']);
            $table->index(['to_warehouse_id', 'created_at']);
        });

        // حصص الاستهلاك (للأقسام)
        Schema::create('item_quotas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('item_id');
            $table->uuid('department_id');

            $table->enum('period', ['daily', 'weekly', 'monthly'])->default('monthly');
            $table->decimal('quota_amount', 10, 2);
            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
            $table->foreign('department_id')->references('id')->on('departments')->cascadeOnDelete();

            $table->unique(['item_id', 'department_id', 'period']);
        });

        // استهلاك الحصص
        Schema::create('quota_consumptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('quota_id');
            $table->uuid('movement_id');

            $table->date('period_start');
            $table->date('period_end');
            $table->decimal('consumed_amount', 10, 2);

            $table->timestamp('created_at')->useCurrent();

            $table->foreign('quota_id')->references('id')->on('item_quotas')->cascadeOnDelete();
            $table->foreign('movement_id')->references('id')->on('inventory_movements')->cascadeOnDelete();
        });

        // طلبات الشراء
        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('request_number')->unique();
            $table->uuid('warehouse_id');
            $table->uuid('department_id');

            $table->enum('status', [
                'draft',            // مسودة
                'pending',          // قيد الانتظار
                'manager_approved', // موافقة المدير
                'finance_approved', // موافقة المالية
                'ceo_approved',     // موافقة المدير العام
                'ordered',          // تم الطلب
                'received',         // تم الاستلام
                'completed',        // مكتمل
                'rejected',         // مرفوض
                'cancelled'         // ملغي
            ])->default('draft');

            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->date('needed_by')->nullable();
            $table->text('justification')->nullable();

            $table->decimal('estimated_total', 12, 2)->default(0);
            $table->decimal('approved_total', 12, 2)->default(0);

            // الموافقات
            $table->uuid('requested_by');
            $table->timestamp('requested_at')->nullable();

            $table->uuid('manager_approved_by')->nullable();
            $table->timestamp('manager_approved_at')->nullable();

            $table->uuid('finance_approved_by')->nullable();
            $table->timestamp('finance_approved_at')->nullable();

            $table->uuid('ceo_approved_by')->nullable();
            $table->timestamp('ceo_approved_at')->nullable();

            $table->uuid('rejected_by')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->text('rejection_reason')->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('department_id')->references('id')->on('departments');
            $table->foreign('requested_by')->references('id')->on('users');

            $table->index(['status', 'created_at']);
        });

        // بنود طلبات الشراء
        Schema::create('purchase_request_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('purchase_request_id');
            $table->uuid('item_id');

            $table->decimal('requested_quantity', 10, 2);
            $table->decimal('approved_quantity', 10, 2)->nullable();
            $table->decimal('received_quantity', 10, 2)->default(0);

            $table->decimal('estimated_unit_price', 10, 2)->default(0);
            $table->decimal('actual_unit_price', 10, 2)->nullable();

            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('purchase_request_id')->references('id')->on('purchase_requests')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('inventory_items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_items');
        Schema::dropIfExists('purchase_requests');
        Schema::dropIfExists('quota_consumptions');
        Schema::dropIfExists('item_quotas');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('warehouse_stocks');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('item_categories');
        Schema::dropIfExists('warehouses');
    }
};
