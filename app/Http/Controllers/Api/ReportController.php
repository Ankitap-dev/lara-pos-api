<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function sales(Request $request)
    {
        $validated = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date'],
        ]);

        $query = Order::where('branch_id', $request->user()->branch_id)
            ->where('status', 'completed');

        if (! empty($validated['from'])) {
            $query->whereDate('created_at', '>=', $validated['from']);
        }

        if (! empty($validated['to'])) {
            $query->whereDate('created_at', '<=', $validated['to']);
        }

        return response()->json([
            'branch_id' => $request->user()->branch_id,
            'order_count' => (clone $query)->count(),
            'total_revenue' => (float) (clone $query)->sum('total'),
            'average_order_value' => (float) round((clone $query)->avg('total') ?? 0, 2),
        ]);
    }
}
