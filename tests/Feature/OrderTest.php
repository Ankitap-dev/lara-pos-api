<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\OrderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_order_creation_deducts_stock_correctly(): void
    {
        $branch = Branch::factory()->create();
        $category = Category::factory()->create(['branch_id' => $branch->id]);
        $product = Product::factory()->create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'price' => 10.00,
            'stock' => 5,
        ]);
        $cashier = User::factory()->create(['branch_id' => $branch->id, 'role' => 'cashier']);

        $order = app(OrderService::class)->createOrder(
            cashier: $cashier,
            items: [['product_id' => $product->id, 'quantity' => 3]],
        );

        $this->assertEquals(30.00, $order->subtotal);
        $this->assertEquals(2, $product->fresh()->stock);
        $this->assertCount(1, $order->items);
    }

    public function test_order_creation_fails_when_stock_is_insufficient(): void
    {
        $branch = Branch::factory()->create();
        $category = Category::factory()->create(['branch_id' => $branch->id]);
        $product = Product::factory()->create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'stock' => 2,
        ]);
        $cashier = User::factory()->create(['branch_id' => $branch->id, 'role' => 'cashier']);

        $this->expectException(ValidationException::class);

        app(OrderService::class)->createOrder(
            cashier: $cashier,
            items: [['product_id' => $product->id, 'quantity' => 5]],
        );

        // Stock must remain untouched since the transaction should roll back.
        $this->assertEquals(2, $product->fresh()->stock);
    }

    public function test_order_totals_apply_tax_and_discount(): void
    {
        $branch = Branch::factory()->create();
        $category = Category::factory()->create(['branch_id' => $branch->id]);
        $product = Product::factory()->create([
            'branch_id' => $branch->id,
            'category_id' => $category->id,
            'price' => 100.00,
            'stock' => 10,
        ]);
        $cashier = User::factory()->create(['branch_id' => $branch->id, 'role' => 'cashier']);

        $order = app(OrderService::class)->createOrder(
            cashier: $cashier,
            items: [['product_id' => $product->id, 'quantity' => 1]],
            taxRate: 0.10,
            discount: 5.00,
        );

        // 100 subtotal + 10 tax - 5 discount = 105 total
        $this->assertEquals(10.00, $order->tax);
        $this->assertEquals(105.00, $order->total);
    }
}
