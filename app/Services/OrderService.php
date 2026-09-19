<?php

namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class OrderService
{
    /**
     * Create an order and decrement stock atomically.
     *
     * $items = [['product_id' => 1, 'quantity' => 2], ...]
     */
    public function createOrder(User $cashier, array $items, float $taxRate = 0.0, float $discount = 0.0): Order
    {
        return DB::transaction(function () use ($cashier, $items, $taxRate, $discount) {
            $subtotal = 0;
            $lineData = [];

            foreach ($items as $item) {
                // lockForUpdate() prevents two concurrent requests from both
                // reading stale stock and overselling the last unit.
                $product = Product::where('id', $item['product_id'])
                    ->where('branch_id', $cashier->branch_id)
                    ->lockForUpdate()
                    ->firstOrFail();

                if (! $product->hasSufficientStock($item['quantity'])) {
                    throw ValidationException::withMessages([
                        'items' => "Insufficient stock for {$product->name}. Available: {$product->stock}.",
                    ]);
                }

                $lineTotal = $product->price * $item['quantity'];
                $subtotal += $lineTotal;

                $lineData[] = [
                    'product' => $product,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'line_total' => $lineTotal,
                ];
            }

            $tax = round($subtotal * $taxRate, 2);
            $total = round($subtotal + $tax - $discount, 2);

            $order = Order::create([
                'branch_id' => $cashier->branch_id,
                'user_id' => $cashier->id,
                'invoice_number' => $this->generateInvoiceNumber($cashier->branch_id),
                'subtotal' => $subtotal,
                'tax' => $tax,
                'discount' => $discount,
                'total' => $total,
                'status' => 'completed',
            ]);

            foreach ($lineData as $line) {
                $order->items()->create([
                    'product_id' => $line['product']->id,
                    'product_name' => $line['product']->name,
                    'quantity' => $line['quantity'],
                    'unit_price' => $line['unit_price'],
                    'line_total' => $line['line_total'],
                ]);

                // Decrement stock in the same locked transaction.
                $line['product']->decrement('stock', $line['quantity']);
            }

            return $order->load('items');
        });
    }

    private function generateInvoiceNumber(int $branchId): string
    {
        return sprintf('INV-%d-%s', $branchId, now()->format('YmdHis') . random_int(100, 999));
    }
}
