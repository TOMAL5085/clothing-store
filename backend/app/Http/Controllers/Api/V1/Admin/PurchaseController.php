<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StorePurchaseRequest;
use App\Http\Requests\Admin\UpdatePurchaseRequest;
use App\Http\Resources\PurchaseResource;
use App\Models\Order;
use App\Models\Purchase;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'supplier_id' => ['sometimes', 'string', 'max:20'],
            'status' => ['sometimes', 'string', Rule::in(array_merge(['all'], Purchase::STATUSES))],
            'payment_status' => ['sometimes', 'string', Rule::in(array_merge(['all'], Purchase::PAYMENT_STATUSES))],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $purchases = Purchase::query()->with('supplier')
            ->when(array_key_exists('supplier_id', $validated), function ($query) use ($validated) {
                $value = $validated['supplier_id'];
                if ($value === 'null') {
                    $query->whereNull('supplier_id');
                } elseif ($value === 'not_null') {
                    $query->whereNotNull('supplier_id');
                } else {
                    $query->where('supplier_id', $value);
                }
            })
            ->when($validated['status'] ?? 'all' !== 'all', fn ($query) => $query->where('status', $validated['status']))
            ->when($validated['payment_status'] ?? 'all' !== 'all', fn ($query) => $query->where('payment_status', $validated['payment_status']))
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search) {
                $query->where('reference_number', 'ilike', "%{$search}%");
            }))
            ->orderBy('created_at', 'desc')
            ->paginate($validated['per_page'] ?? 50);

        return PurchaseResource::collection($purchases);
    }

    public function store(StorePurchaseRequest $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $purchase = DB::transaction(function () use ($request, $audit) {
            $purchase = Purchase::query()->create([
                ...$request->validated(),
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            $audit->log('purchase.created', $request->user(), $purchase, [
                'reference_number' => $purchase->reference_number,
                'supplier_id' => $purchase->supplier_id,
                'total' => $purchase->total,
            ]);

            return $purchase;
        });

        return response()->json(['data' => PurchaseResource::make($purchase->load('supplier'))], 201);
    }

    public function show(Purchase $purchase): PurchaseResource
    {
        Gate::authorize('viewAny', Order::class);

        return PurchaseResource::make($purchase->load(['supplier', 'items.product', 'items.variant']));
    }

    public function update(UpdatePurchaseRequest $request, Purchase $purchase, AuditLogger $audit): PurchaseResource
    {
        Gate::authorize('viewAny', Order::class);

        $purchase = DB::transaction(function () use ($request, $audit, $purchase) {
            $purchase->update([
                ...$request->validated(),
                'updated_by' => $request->user()->id,
            ]);

            $audit->log('purchase.updated', $request->user(), $purchase->refresh(), [
                'reference_number' => $purchase->reference_number,
                'status' => $purchase->status,
                'payment_status' => $purchase->payment_status,
            ]);

            return $purchase;
        });

        return PurchaseResource::make($purchase->refresh()->load(['supplier', 'items.product', 'items.variant']));
    }

    public function destroy(Purchase $purchase, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $reference = $purchase->reference_number;
        $purchase->delete();

        $audit->log('purchase.deleted', request()->user(), null, ['reference_number' => $reference]);

        return response()->json(['message' => 'Purchase deleted.']);
    }
}
