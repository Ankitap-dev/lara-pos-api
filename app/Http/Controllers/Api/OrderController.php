<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreOrderRequest;
use App\Http\Resources\OrderResource;
use App\Models\Order;
use App\Services\OrderService;
use Illuminate\Http\Request;

class OrderController extends Controller
{
    public function __construct(private readonly OrderService $orderService)
    {
    }

    public function store(StoreOrderRequest $request)
    {
        $order = $this->orderService->createOrder(
            cashier: $request->user(),
            items: $request->validated()['items'],
            taxRate: $request->validated()['tax_rate'] ?? 0.0,
            discount: $request->validated()['discount'] ?? 0.0,
        );

        return new OrderResource($order->load(['items', 'cashier']));
    }

    public function show(Request $request, Order $order)
    {
        abort_if($order->branch_id !== $request->user()->branch_id, 403);

        return new OrderResource($order->load(['items', 'cashier']));
    }
}
