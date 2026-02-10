<?php

namespace Tests\Unit\Services\Inventory;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\ItemQuota;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\WarehouseStock;
use App\Models\HR\Department;
use App\Services\Inventory\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryServiceTest extends TestCase
{
    use RefreshDatabase;

    protected InventoryService $service;
    protected Warehouse $warehouse;
    protected InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new InventoryService();
        $this->warehouse = Warehouse::factory()->create([
            'allows_negative_stock' => false,
        ]);
        $this->item = InventoryItem::factory()->create([
            'unit_cost' => 50.00,
            'reorder_level' => 10,
        ]);
    }

    /** @test */
    public function it_can_receive_inventory()
    {
        // Act
        $movement = $this->service->receive(
            itemId: $this->item->id,
            warehouseId: $this->warehouse->id,
            quantity: 100,
            createdBy: fake()->uuid(),
            batchNumber: 'BATCH001',
            expiryDate: new \DateTime('+1 year'),
            unitCost: 50.00
        );

        // Assert
        $this->assertInstanceOf(InventoryMovement::class, $movement);
        $this->assertEquals(InventoryMovement::TYPE_RECEIVE, $movement->type);
        $this->assertEquals(100, $movement->quantity);

        $stock = WarehouseStock::where([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'batch_number' => 'BATCH001',
        ])->first();

        $this->assertNotNull($stock);
        $this->assertEquals(100, $stock->quantity);
    }

    /** @test */
    public function it_adds_to_existing_stock_when_receiving()
    {
        // Arrange
        $existingStock = WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'batch_number' => 'BATCH001',
            'quantity' => 50,
        ]);

        // Act
        $this->service->receive(
            itemId: $this->item->id,
            warehouseId: $this->warehouse->id,
            quantity: 30,
            createdBy: fake()->uuid(),
            batchNumber: 'BATCH001'
        );

        // Assert
        $existingStock->refresh();
        $this->assertEquals(80, $existingStock->quantity);
    }

    /** @test */
    public function it_issues_inventory_using_fefo_policy()
    {
        // Arrange - Create stocks with different expiry dates
        $oldBatch = WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'batch_number' => 'OLD_BATCH',
            'quantity' => 30,
            'expiry_date' => now()->addMonth(),
        ]);

        $newBatch = WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'batch_number' => 'NEW_BATCH',
            'quantity' => 50,
            'expiry_date' => now()->addMonths(6),
        ]);

        // Act - Issue 40 units
        $movement = $this->service->issue(
            itemId: $this->item->id,
            warehouseId: $this->warehouse->id,
            quantity: 40,
            createdBy: fake()->uuid()
        );

        // Assert - Old batch should be depleted first (FEFO)
        $oldBatch->refresh();
        $newBatch->refresh();

        $this->assertEquals(0, $oldBatch->quantity); // Fully depleted
        $this->assertEquals(40, $newBatch->quantity); // 50 - 10 = 40
        $this->assertEquals(InventoryMovement::TYPE_ISSUE, $movement->type);
    }

    /** @test */
    public function it_throws_exception_when_issuing_more_than_available()
    {
        // Arrange
        WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 20,
        ]);

        // Act & Assert
        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('الكمية المتاحة');

        $this->service->issue(
            itemId: $this->item->id,
            warehouseId: $this->warehouse->id,
            quantity: 50,
            createdBy: fake()->uuid()
        );
    }

    /** @test */
    public function it_can_transfer_between_warehouses()
    {
        // Arrange
        $targetWarehouse = Warehouse::factory()->create();

        WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 100,
            'batch_number' => 'BATCH001',
            'expiry_date' => now()->addMonths(3),
        ]);

        // Act
        $movement = $this->service->transfer(
            itemId: $this->item->id,
            fromWarehouseId: $this->warehouse->id,
            toWarehouseId: $targetWarehouse->id,
            quantity: 40,
            createdBy: fake()->uuid()
        );

        // Assert
        $this->assertEquals(InventoryMovement::TYPE_TRANSFER, $movement->type);

        $sourceStock = WarehouseStock::where([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
        ])->first();

        $targetStock = WarehouseStock::where([
            'warehouse_id' => $targetWarehouse->id,
            'item_id' => $this->item->id,
        ])->first();

        $this->assertEquals(60, $sourceStock->quantity);
        $this->assertEquals(40, $targetStock->quantity);
    }

    /** @test */
    public function it_can_adjust_inventory()
    {
        // Arrange
        $stock = WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 100,
        ]);

        // Act - Adjust to 85 (physical count shows 85)
        $movement = $this->service->adjust(
            itemId: $this->item->id,
            warehouseId: $this->warehouse->id,
            newQuantity: 85,
            createdBy: fake()->uuid(),
            reason: 'جرد فعلي'
        );

        // Assert
        $stock->refresh();
        $this->assertEquals(85, $stock->quantity);
        $this->assertEquals(InventoryMovement::TYPE_ADJUSTMENT, $movement->type);
        $this->assertEquals(15, $movement->quantity); // Difference
    }

    /** @test */
    public function it_checks_quota_correctly()
    {
        // Arrange
        $department = Department::factory()->create();

        ItemQuota::factory()->create([
            'item_id' => $this->item->id,
            'department_id' => $department->id,
            'quota_amount' => 100,
            'period_type' => 'monthly',
            'is_active' => true,
        ]);

        // Act
        $result = $this->service->checkQuota(
            $this->item->id,
            $department->id,
            30
        );

        // Assert
        $this->assertTrue($result['allowed']);
        $this->assertEquals(70, $result['remaining']);
    }

    /** @test */
    public function it_denies_quota_when_exceeded()
    {
        // Arrange
        $department = Department::factory()->create();

        ItemQuota::factory()->create([
            'item_id' => $this->item->id,
            'department_id' => $department->id,
            'quota_amount' => 50,
            'period_type' => 'monthly',
            'is_active' => true,
        ]);

        // Act
        $result = $this->service->checkQuota(
            $this->item->id,
            $department->id,
            60 // Exceeds quota
        );

        // Assert
        $this->assertFalse($result['allowed']);
        $this->assertStringContains('تجاوز الحصة', $result['message']);
    }

    /** @test */
    public function it_returns_expired_items()
    {
        // Arrange
        WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 20,
            'expiry_date' => now()->subDays(5), // Expired
        ]);

        WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 30,
            'expiry_date' => now()->addMonths(6), // Not expired
        ]);

        // Act
        $expiredItems = $this->service->getExpiredItems();

        // Assert
        $this->assertCount(1, $expiredItems);
        $this->assertEquals(20, $expiredItems->first()->quantity);
    }

    /** @test */
    public function it_returns_expiring_soon_items()
    {
        // Arrange
        WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 25,
            'expiry_date' => now()->addDays(15), // Expiring soon
        ]);

        WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 30,
            'expiry_date' => now()->addMonths(6), // Not expiring soon
        ]);

        // Act
        $expiringItems = $this->service->getExpiringItems(30);

        // Assert
        $this->assertCount(1, $expiringItems);
    }

    /** @test */
    public function it_returns_low_stock_items()
    {
        // Arrange
        WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 5, // Below reorder level of 10
        ]);

        // Act
        $lowStockItems = $this->service->getLowStockItems();

        // Assert
        $this->assertCount(1, $lowStockItems);
    }

    /** @test */
    public function it_returns_correct_statistics()
    {
        // Arrange
        WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 100,
            'unit_cost' => 50,
        ]);

        // Act
        $stats = $this->service->getStatistics();

        // Assert
        $this->assertArrayHasKey('total_items', $stats);
        $this->assertArrayHasKey('total_stock_value', $stats);
        $this->assertArrayHasKey('expired_items_count', $stats);
        $this->assertArrayHasKey('low_stock_items_count', $stats);
        $this->assertEquals(5000, $stats['total_stock_value']); // 100 * 50
    }

    /**
     * Helper assertion for string contains
     */
    protected function assertStringContains(string $needle, string $haystack): void
    {
        $this->assertTrue(
            str_contains($haystack, $needle),
            "Failed asserting that '$haystack' contains '$needle'"
        );
    }
}
