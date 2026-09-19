<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $categories = Category::where('branch_id', $request->user()->branch_id)
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['id', 'name']);

        return response()->json($categories);
    }

    public function store(Request $request)
    {
        // Role restricted at the route level via the 'role:admin,manager' middleware.
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
        ]);

        $category = Category::create([
            'branch_id' => $request->user()->branch_id,
            'name' => $validated['name'],
        ]);

        return response()->json($category, 201);
    }
}
