<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\AuditLogResource;
use App\Models\AuditLog;
use App\Models\Order;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'action' => ['sometimes', 'nullable', 'string', 'max:60'],
            'actor_id' => ['sometimes', 'nullable', 'integer', 'min:1'],
            'from' => ['sometimes', 'nullable', 'date_format:Y-m-d'],
            'to' => ['sometimes', 'nullable', 'date_format:Y-m-d', 'after_or_equal:from'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $logs = AuditLog::query()
            ->when($validated['action'] ?? null, fn ($query, string $action) => $query->where('action', $action))
            ->when($validated['actor_id'] ?? null, fn ($query, int $actor) => $query->where('actor_id', $actor))
            ->when($validated['from'] ?? null, fn ($query, string $from) => $query->where('created_at', '>=', $from))
            ->when($validated['to'] ?? null, fn ($query, string $to) => $query->where('created_at', '<=', $to.' 23:59:59'))
            ->latest('id')
            ->paginate($validated['per_page'] ?? 50);

        return AuditLogResource::collection($logs);
    }

    public function actions()
    {
        Gate::authorize('viewAny', Order::class);

        return response()->json([
            'data' => AuditLog::query()->distinct()->orderBy('action')->pluck('action'),
        ]);
    }
}
