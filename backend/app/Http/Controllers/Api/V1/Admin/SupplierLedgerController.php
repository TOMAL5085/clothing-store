<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\SupplierLedgerResource;
use App\Models\Order;
use App\Models\SupplierLedger;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SupplierLedgerController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'supplier_id' => ['sometimes', 'string', 'max:20'],
            'reference_type' => ['sometimes', 'string', 'max:50'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $ledger = SupplierLedger::query()->with('supplier')
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
            ->when($validated['reference_type'] ?? null, fn ($query) => $query->where('reference_type', $validated['reference_type']))
            ->orderBy('created_at', 'desc')
            ->paginate($validated['per_page'] ?? 50);

        return SupplierLedgerResource::collection($ledger);
    }

    public function show(SupplierLedger $ledger): SupplierLedgerResource
    {
        Gate::authorize('viewAny', Order::class);

        return SupplierLedgerResource::make($ledger->load('supplier'));
    }

    public function store(Request $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('create', SupplierLedger::class);

        $validated = $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'reference_type' => ['required', 'string', 'max:50'],
            'reference_id' => ['required', 'integer'],
            'debit' => ['sometimes', 'numeric', 'min:0'],
            'credit' => ['sometimes', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:1000'],
        ]);

        $lastBalance = SupplierLedger::query()->where('supplier_id', $validated['supplier_id'])
            ->orderBy('created_at', 'desc')
            ->value('balance') ?? 0;

        $entry = SupplierLedger::query()->create([
            ...$validated,
            'debit' => $validated['debit'] ?? 0,
            'credit' => $validated['credit'] ?? 0,
            'balance' => round($lastBalance + ($validated['debit'] ?? 0) - ($validated['credit'] ?? 0), 2),
        ]);

        $audit->log('supplier_ledger.created', $request->user(), $entry, [
            'supplier_id' => $entry->supplier_id,
            'debit' => $entry->debit,
            'credit' => $entry->credit,
            'balance' => $entry->balance,
        ]);

        return response()->json(['data' => SupplierLedgerResource::make($entry->load('supplier'))], 201);
    }

    public function destroy(SupplierLedger $ledger, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('delete', $ledger);

        $id = $ledger->id;
        $ledger->delete();

        $audit->log('supplier_ledger.deleted', request()->user(), null, ['id' => $id]);

        return response()->json(['message' => 'Ledger entry deleted.']);
    }
}
