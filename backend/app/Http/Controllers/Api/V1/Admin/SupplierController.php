<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreSupplierRequest;
use App\Http\Requests\Admin\UpdateSupplierRequest;
use App\Http\Resources\SupplierResource;
use App\Models\Order;
use App\Models\Supplier;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'city_id' => ['sometimes', 'string', 'max:20'],
            'zone_id' => ['sometimes', 'string', 'max:20'],
            'status' => ['sometimes', 'string', Rule::in(array_merge(['all'], Supplier::STATUSES))],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $suppliers = Supplier::query()->with(['city', 'zone'])
            ->when($validated['status'] ?? 'all' !== 'all', fn ($query) => $query->where('status', $validated['status']))
            ->when(array_key_exists('city_id', $validated), function ($query) use ($validated) {
                $value = $validated['city_id'];
                if ($value === 'null') {
                    $query->whereNull('city_id');
                } elseif ($value === 'not_null') {
                    $query->whereNotNull('city_id');
                } else {
                    $query->where('city_id', $value);
                }
            })
            ->when(array_key_exists('zone_id', $validated), function ($query) use ($validated) {
                $value = $validated['zone_id'];
                if ($value === 'null') {
                    $query->whereNull('zone_id');
                } elseif ($value === 'not_null') {
                    $query->whereNotNull('zone_id');
                } else {
                    $query->where('zone_id', $value);
                }
            })
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('slug', 'ilike', "%{$search}%")
                    ->orWhere('contact_person', 'ilike', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return SupplierResource::collection($suppliers);
    }

    public function store(StoreSupplierRequest $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $supplier = Supplier::query()->create([
            ...$request->validated(),
        ]);

        $audit->log('supplier.created', $request->user(), $supplier, [
            'slug' => $supplier->slug,
            'name' => $supplier->name,
        ]);

        return response()->json(['data' => SupplierResource::make($supplier->load(['city', 'zone']))], 201);
    }

    public function show(Supplier $supplier): SupplierResource
    {
        Gate::authorize('viewAny', Order::class);

        return SupplierResource::make($supplier->load(['city', 'zone', 'purchases' => function ($query) {
            $query->orderBy('created_at', 'desc')->limit(10);
        }]));
    }

    public function update(UpdateSupplierRequest $request, Supplier $supplier, AuditLogger $audit): SupplierResource
    {
        Gate::authorize('viewAny', Order::class);

        $supplier->update([
            ...$request->validated(),
        ]);

        $audit->log('supplier.updated', $request->user(), $supplier->refresh(), [
            'slug' => $supplier->slug,
            'name' => $supplier->name,
            'status' => $supplier->status,
        ]);

        return SupplierResource::make($supplier->refresh()->load(['city', 'zone']));
    }

    public function destroy(Supplier $supplier, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $slug = $supplier->slug;
        $supplier->delete();

        $audit->log('supplier.deleted', request()->user(), null, ['slug' => $slug]);

        return response()->json(['message' => 'Supplier deleted.']);
    }
}
