<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreCityRequest;
use App\Http\Requests\Admin\UpdateCityRequest;
use App\Http\Resources\CityResource;
use App\Models\City;
use App\Models\Order;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CityController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'zone_id' => ['sometimes', 'string', 'max:20'],
            'is_active' => ['sometimes', 'boolean'],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $zoneFilter = $validated['zone_id'] ?? null;

        $cities = City::query()->with('zone')
            ->when($validated['is_active'] ?? null !== null, fn ($query) => $query->where('is_active', $validated['is_active']))
            ->when(array_key_exists('zone_id', $validated), function ($query) use ($zoneFilter) {
                if ($zoneFilter === 'null') {
                    $query->whereNull('zone_id');
                } elseif ($zoneFilter === 'not_null') {
                    $query->whereNotNull('zone_id');
                } else {
                    $query->where('zone_id', $zoneFilter);
                }
            })
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return CityResource::collection($cities);
    }

    public function store(StoreCityRequest $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $city = City::query()->create([
            ...$request->validated(),
        ]);

        $audit->log('city.created', $request->user(), $city, [
            'name' => $city->name,
            'zone_id' => $city->zone_id,
        ]);

        return response()->json(['data' => CityResource::make($city->load('zone'))], 201);
    }

    public function show(City $city): CityResource
    {
        Gate::authorize('viewAny', Order::class);

        return CityResource::make($city->load('zone'));
    }

    public function update(UpdateCityRequest $request, City $city, AuditLogger $audit): CityResource
    {
        Gate::authorize('viewAny', Order::class);

        $city->update([
            ...$request->validated(),
        ]);

        $audit->log('city.updated', $request->user(), $city->refresh(), [
            'name' => $city->name,
            'is_active' => $city->is_active,
        ]);

        return CityResource::make($city->refresh()->load('zone'));
    }

    public function destroy(City $city, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $name = $city->name;
        $city->delete();

        $audit->log('city.deleted', request()->user(), null, ['name' => $name]);

        return response()->json(['message' => 'City deleted.']);
    }
}
