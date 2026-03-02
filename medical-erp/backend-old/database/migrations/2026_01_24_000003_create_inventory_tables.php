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
            $table->string('name');
            $table->string('name_ar');
            $table->string('code')->unique();
            $table->uuid('cost_center_id');
            $table->enum('type', [
                'main',           // المستودع الرئيسي
                'sub',            // مستودع فرعي
                'dressing_male',  // ضماد رجال
                'dressing_female',// ضماد نساء
                'emergency',      // طوارئ
                'pharmacy',       // صيدلية
                'crash_cart'      // عربة الطوارئ
            ]);
            $table->uuid('manager_id')->nullable();
            $table->boolean('is_active')->default(true);
            $table->text('location')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('cost_center_id')->references('id')->on('cost_centers');
            $table->foreign('manager_id')->references('id')->on('employees');
        });

        // المواد والأصناف
        Schema::create('inventory_items', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('name_ar');
            $table->string('sku')->unique();
            $table->string('barcode')->nullable()->unique();
            $table->uuid('category_id');
            $table->uuid('unit_id');
            $table->enum('type', ['consumable', 'asset', 'medication', 'equipment']);
            $table->integer('stock')->default(0);
            $table->integer('safety_margin')->default(10);
            $table->integer('reorder_level')->default(20);
            $table->decimal('unit_cost', 12, 2)->default(0);
            $table->decimal('selling_price', 12, 2)->default(0);
            $table->boolean('requires_prescription')->default(false);
            $table->boolean('is_controlled')->default(false);
            $table->integer('shelf_life_days')->nullable();
            $table->json('storage_conditions')->nullable();
            $table->integer('version')->default(1); // للـ Optimistic Locking
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('item_categories');
            $table->foreign('unit_id')->references('id')->on('units');
            
            $table->index('sku');
            $table->index(['stock', 'safety_margin']);
        });

        // مخزون المستودعات (كمية كل صنف في كل مستودع)
        Schema::create('warehouse_stocks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('warehouse_id');
            $table->uuid('item_id');
            $table->integer('quantity')->default(0);
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->string('location_in_warehouse')->nullable(); // رقم الرف
            $table->integer('version')->default(1);
            $table->timestamps();

            $table->foreign('warehouse_id')->references('id')->on('warehouses')->cascadeOnDelete();
            $table->foreign('item_id')->references('id')->on('inventory_items')->cascadeOnDelete();
            
            $table->unique(['warehouse_id', 'item_id', 'batch_number']);
            $table->index('expiry_date'); // للـ FEFO
        });

        // حركات المخزون (سجل غير قابل للتعديل - Audit Trail)
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('item_id');
            $table->uuid('warehouse_id');
            $table->uuid('warehouse_stock_id')->nullable();
            $table->enum('type', [
                'purchase',       // شراء
                'sale',           // بيع
                'transfer_in',    // تحويل وارد
                'transfer_out',   // تحويل صادر
                'adjustment',     // تسوية
                'return',         // مرتجع
                'disposal',       // إتلاف
                'crash_cart'      // صرف طوارئ
            ]);
            $table->integer('quantity');
            $table->integer('quantity_before');
            $table->integer('quantity_after');
            $table->decimal('unit_cost', 12, 2);
            $table->decimal('total_cost', 12, 2);
            $table->string('batch_number')->nullable();
            $table->date('expiry_date')->nullable();
            $table->string('reference_type')->nullable(); // نوع المرجع
            $table->uuid('reference_id')->nullable();     // معرف المرجع
            $table->uuid('user_id');
            $table->text('notes')->nullable();
            $table->string('blue_code_number')->nullable(); // لحالات الطوارئ
            $table->timestamp('created_at');
            // لا يوجد updated_at - السجل غير قابل للتعديل

            $table->foreign('item_id')->references('id')->on('inventory_items');
            $table->foreign('warehouse_id')->references('id')->on('warehouses');
            $table->foreign('user_id')->references('id')->on('users');
            
            $table->index(['item_id', 'created_at']);
            $table->index(['warehouse_id', 'type']);
            $table->index('reference_id');
        });

        // نظام الحصص (Quotas)
        Schema::create('item_quotas', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('item_id');
            $table->uuid('department_id');
            $table->integer('daily_limit');
            $table->integer('weekly_limit')->nullable();
            $table->integer('monthly_limit')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->foreign('item_id')->references('id')->on('inventory_items');
            $table->foreign('department_id')->references('id')->on('departments');
            
            $table->unique(['item_id', 'department_id']);
        });

        // استهلاك الحصص
        Schema::create('quota_consumptions', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('quota_id');
            $table->date('consumption_date');
            $table->integer('quantity_consumed');
            $table->uuid('movement_id');
            $table->timestamps();

            $table->foreign('quota_id')->references('id')->on('item_quotas');
            $table->foreign('movement_id')->references('id')->on('inventory_movements');
            
            $table->index(['quota_id', 'consumption_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quota_consumptions');
        Schema::dropIfExists('item_quotas');
        Schema::dropIfExists('inventory_movements');
        Schema::dropIfExists('warehouse_stocks');
        Schema::dropIfExists('inventory_items');
        Schema::dropIfExists('warehouses');
    }
};
