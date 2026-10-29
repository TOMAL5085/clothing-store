<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BrandResource;
use App\Models\Brand;

class BrandController extends Controller
{
    public function index()
    {
        return BrandResource::collection(
            Brand::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function show(Brand $brand): BrandResource
    {
        if (! $brand->is_active) {
            abort(404);
        }

        return BrandResource::make($brand->load(['products' => function ($query) {
            $query->where('is_active', true)->orderBy('id');
        }]));
    }
}
