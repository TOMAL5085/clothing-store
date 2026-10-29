<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'parent_id' => ['sometimes', 'nullable', 'integer', 'exists:categories,id'],
        ]);

        $query = Category::query()->where('is_active', true);

        if (array_key_exists('parent_id', $validated)) {
            // null = top-level categories, otherwise children of that parent.
            $query->where('parent_id', $validated['parent_id']);
        } else {
            $query->whereNull('parent_id');
        }

        return CategoryResource::collection(
            $query->orderBy('sort_order')->orderBy('id')->get()
        );
    }
}
