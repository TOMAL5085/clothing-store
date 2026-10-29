<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreZoneRequest;
use App\Http\Requests\Admin\UpdateZoneRequest;
use App\Http\Resources\ZoneResource;
use App\Models\Order;
use App\Models\Zone;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ZoneController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'is_active' => ['sometimes', 'boolean'],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $zones = Zone::query()
            ->when($validated['is_active'] ?? null !== null, fn ($query) => $query->where('is_active', $validated['is_active']))
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search) {
                $query->where('name', 'ilike', "%{$search}%")
                    ->orWhere('code', 'ilike', "%{$search}%");
            }))
            ->orderBy('sort_order')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return ZoneResource::collection($zones);
    }

    public function store(StoreZoneRequest $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $zone = Zone::query()->create([
            ...$request->validated(),
        ]);

        $audit->log('zone.created', $request->user(), $zone, [
            'code' => $zone->code,
            'name' => $zone->name,
        ]);

        return response()->json(['data' => ZoneResource::make($zone)], 201);
    }

    public function show(Zone $zone): ZoneResource
    {
        Gate::authorize('viewAny', Order::class);

        return ZoneResource::make($zone->load('cities'));
    }

    public function update(UpdateZoneRequest $request, Zone $zone, AuditLogger $audit): ZoneResource
    {
        Gate::authorize('viewAny', Order::class);

        $zone->update([
            ...$request->validated(),
        ]);

        $audit->log('zone.updated', $request->user(), $zone->refresh(), [
            'code' => $zone->code,
            'name' => $zone->name,
            'is_active' => $zone->is_active,
        ]);

        return ZoneResource::make($zone->refresh());
    }

    public function destroy(Zone $zone, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $code = $zone->code;
        $zone->delete();

        $audit->log('zone.deleted', request()->user(), null, ['code' => $code]);

        return response()->json(['message' => 'Zone deleted.']);
    }
}
