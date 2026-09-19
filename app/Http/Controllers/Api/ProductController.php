<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    public function index(Request $request)
    {
        $products = Product::where('branch_id', $request->user()->branch_id)
            ->when($request->query('category_id'), fn ($q, $categoryId) => $q->where('category_id', $categoryId))
            ->where('is_active', true)
            ->with('category')
            ->orderBy('name')
            ->paginate(20);

        return ProductResource::collection($products);
    }

    public function store(StoreProductRequest $request)
    {
        $product = Product::create([
            ...$request->validated(),
            'branch_id' => $request->user()->branch_id,
        ]);

        return new ProductResource($product->load('category'));
    }

    public function show(Request $request, Product $product)
    {
        abort_if($product->branch_id !== $request->user()->branch_id, 403);

        return new ProductResource($product->load('category'));
    }
}
