<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierResource;
use App\Models\Supplier;
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'city_id' => ['sometimes', 'nullable', 'integer', 'exists:cities,id'],
            'zone_id' => ['sometimes', 'nullable', 'integer', 'exists:zones,id'],
        ]);

        $query = Supplier::query()->where('status', 'active');

        if (array_key_exists('city_id', $validated) && $validated['city_id'] !== null) {
            $query->where('city_id', $validated['city_id']);
        }

        if (array_key_exists('zone_id', $validated) && $validated['zone_id'] !== null) {
            $query->where('zone_id', $validated['zone_id']);
        }

        return SupplierResource::collection(
            $query->with(['city', 'zone'])->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function show(Supplier $supplier): SupplierResource
    {
        if ($supplier->status !== 'active') {
            abort(404);
        }

        return SupplierResource::make($supplier->load(['city', 'zone', 'purchases' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(5);
        }]));
    }
}
