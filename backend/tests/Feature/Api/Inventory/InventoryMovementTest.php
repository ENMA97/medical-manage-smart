<?php

namespace Tests\Feature\Api\Inventory;

use App\Models\Inventory\InventoryItem;
use App\Models\Inventory\InventoryMovement;
use App\Models\Inventory\Warehouse;
use App\Models\Inventory\WarehouseStock;
use App\Models\System\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Gate;
use Tests\TestCase;

class InventoryMovementTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Warehouse $warehouse;
    protected InventoryItem $item;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create();
        $this->warehouse = Warehouse::factory()->create([
            'allows_negative_stock' => false,
        ]);
        $this->item = InventoryItem::factory()->create([
            'unit_cost' => 100,
        ]);

        Gate::define('inventory.view', fn() => true);
        Gate::define('inventory.manage', fn() => true);
    }

    /** @test */
    public function can_receive_inventory()
    {
        // Arrange
        $data = [
            'item_id' => $this->item->id,
            'warehouse_id' => $this->warehouse->id,
            'quantity' => 50,
            'batch_number' => 'BATCH001',
            'expiry_date' => now()->addYear()->toDateString(),
            'unit_cost' => 100,
        ];

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/inventory/movements/receive', $data);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseHas('warehouse_stocks', [
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 50,
        ]);

        $this->assertDatabaseHas('inventory_movements', [
            'type' => 'receive',
            'item_id' => $this->item->id,
            'quantity' => 50,
        ]);
    }

    /** @test */
    public function can_issue_inventory_with_fefo()
    {
        // Arrange - Create stocks with different expiry dates (FEFO)
        $oldStock = WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 30,
            'expiry_date' => now()->addMonths(1),
            'batch_number' => 'OLD_BATCH',
        ]);

        $newStock = WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 50,
            'expiry_date' => now()->addMonths(6),
            'batch_number' => 'NEW_BATCH',
        ]);

        // Act - Issue 40 units (should take from old stock first)
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/inventory/movements/issue', [
                'item_id' => $this->item->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 40,
                'reason' => 'صرف للقسم',
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        // Old stock should be depleted first (FEFO)
        $oldStock->refresh();
        $newStock->refresh();
        $this->assertEquals(0, $oldStock->quantity);
        $this->assertEquals(40, $newStock->quantity); // 50 - 10 = 40
    }

    /** @test */
    public function cannot_issue_more_than_available_stock()
    {
        // Arrange
        WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 20,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/inventory/movements/issue', [
                'item_id' => $this->item->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity' => 50, // More than available
                'reason' => 'صرف',
            ]);

        // Assert
        $response->assertStatus(400)
            ->assertJson([
                'success' => false,
            ]);
    }

    /** @test */
    public function can_transfer_between_warehouses()
    {
        // Arrange
        $sourceWarehouse = $this->warehouse;
        $targetWarehouse = Warehouse::factory()->create();

        WarehouseStock::factory()->create([
            'warehouse_id' => $sourceWarehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 100,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/inventory/movements/transfer', [
                'item_id' => $this->item->id,
                'from_warehouse_id' => $sourceWarehouse->id,
                'to_warehouse_id' => $targetWarehouse->id,
                'quantity' => 30,
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        // Check source warehouse reduced
        $sourceStock = WarehouseStock::where([
            'warehouse_id' => $sourceWarehouse->id,
            'item_id' => $this->item->id,
        ])->first();
        $this->assertEquals(70, $sourceStock->quantity);

        // Check target warehouse received
        $targetStock = WarehouseStock::where([
            'warehouse_id' => $targetWarehouse->id,
            'item_id' => $this->item->id,
        ])->first();
        $this->assertEquals(30, $targetStock->quantity);
    }

    /** @test */
    public function can_adjust_inventory()
    {
        // Arrange
        $stock = WarehouseStock::factory()->create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'quantity' => 100,
        ]);

        // Act - Adjust to 80 (physical count difference)
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/inventory/movements/adjust', [
                'item_id' => $this->item->id,
                'warehouse_id' => $this->warehouse->id,
                'new_quantity' => 80,
                'reason' => 'جرد فعلي',
            ]);

        // Assert
        $response->assertStatus(201)
            ->assertJson([
                'success' => true,
            ]);

        $stock->refresh();
        $this->assertEquals(80, $stock->quantity);

        $this->assertDatabaseHas('inventory_movements', [
            'type' => 'adjustment',
            'item_id' => $this->item->id,
            'quantity' => 20, // Difference
        ]);
    }

    /** @test */
    public function can_list_movements()
    {
        // Arrange
        InventoryMovement::factory()->count(5)->create([
            'item_id' => $this->item->id,
            'to_warehouse_id' => $this->warehouse->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson('/api/inventory/movements');

        // Assert
        $response->assertStatus(200)
            ->assertJsonStructure([
                'success',
                'data' => [
                    'data' => [
                        '*' => [
                            'id',
                            'type',
                            'quantity',
                        ],
                    ],
                ],
            ]);
    }

    /** @test */
    public function can_filter_movements_by_item()
    {
        // Arrange
        $otherItem = InventoryItem::factory()->create();

        InventoryMovement::factory()->count(3)->create([
            'item_id' => $this->item->id,
            'to_warehouse_id' => $this->warehouse->id,
        ]);

        InventoryMovement::factory()->count(2)->create([
            'item_id' => $otherItem->id,
            'to_warehouse_id' => $this->warehouse->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/inventory/movements?item_id={$this->item->id}");

        // Assert
        $response->assertStatus(200);
        $this->assertCount(3, $response->json('data.data'));
    }

    /** @test */
    public function can_view_single_movement()
    {
        // Arrange
        $movement = InventoryMovement::factory()->create([
            'item_id' => $this->item->id,
            'to_warehouse_id' => $this->warehouse->id,
        ]);

        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->getJson("/api/inventory/movements/{$movement->id}");

        // Assert
        $response->assertStatus(200)
            ->assertJson([
                'success' => true,
                'data' => [
                    'id' => $movement->id,
                ],
            ]);
    }

    /** @test */
    public function receive_validates_required_fields()
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/inventory/movements/receive', []);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['item_id', 'warehouse_id', 'quantity']);
    }

    /** @test */
    public function receive_validates_quantity_is_positive()
    {
        // Act
        $response = $this->actingAs($this->user, 'sanctum')
            ->postJson('/api/inventory/movements/receive', [
                'item_id' => $this->item->id,
                'warehouse_id' => $this->warehouse->id,
                'quantity' => -10, // Negative
            ]);

        // Assert
        $response->assertStatus(422)
            ->assertJsonValidationErrors(['quantity']);
    }
}
