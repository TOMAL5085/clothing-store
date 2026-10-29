<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreManualPaymentRequest;
use App\Http\Requests\Admin\UpdateManualPaymentRequest;
use App\Http\Resources\ManualPaymentResource;
use App\Models\ManualPayment;
use App\Models\Order;
use App\Services\AuditLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class ManualPaymentController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate([
            'account_id' => ['sometimes', 'string', 'max:20'],
            'status' => ['sometimes', 'string', Rule::in(array_merge(['all'], ManualPayment::STATUSES))],
            'payment_method' => ['sometimes', 'string', Rule::in(array_merge(['all'], ManualPayment::PAYMENT_METHODS))],
            'q' => ['sometimes', 'nullable', 'string', 'max:120'],
            'per_page' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ]);

        $payments = ManualPayment::query()->with(['order', 'account'])
            ->when(array_key_exists('account_id', $validated), function ($query) use ($validated) {
                $value = $validated['account_id'];
                if ($value === 'null') {
                    $query->whereNull('account_id');
                } elseif ($value === 'not_null') {
                    $query->whereNotNull('account_id');
                } else {
                    $query->where('account_id', $value);
                }
            })
            ->when($validated['status'] ?? 'all' !== 'all', fn ($query) => $query->where('status', $validated['status']))
            ->when($validated['payment_method'] ?? 'all' !== 'all', fn ($query) => $query->where('payment_method', $validated['payment_method']))
            ->when($validated['q'] ?? null, fn ($query, string $search) => $query->where(function ($query) use ($search) {
                $query->where('reference_number', 'ilike', "%{$search}%")
                    ->orWhere('customer_name', 'ilike', "%{$search}%");
            }))
            ->orderBy('payment_date', 'desc')
            ->orderBy('id')
            ->paginate($validated['per_page'] ?? 50);

        return ManualPaymentResource::collection($payments);
    }

    public function store(StoreManualPaymentRequest $request, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $payment = ManualPayment::query()->create([
            ...$request->validated(),
            'created_by' => $request->user()->id,
            'updated_by' => $request->user()->id,
        ]);

        $audit->log('manual_payment.created', $request->user(), $payment, [
            'reference_number' => $payment->reference_number,
            'order_id' => $payment->order_id,
            'account_id' => $payment->account_id,
            'amount' => $payment->amount,
        ]);

        return response()->json(['data' => ManualPaymentResource::make($payment->load(['order', 'account']))], 201);
    }

    public function show(ManualPayment $payment): ManualPaymentResource
    {
        Gate::authorize('viewAny', Order::class);

        return ManualPaymentResource::make($payment->load(['order', 'account']));
    }

    public function update(UpdateManualPaymentRequest $request, ManualPayment $payment, AuditLogger $audit): ManualPaymentResource
    {
        Gate::authorize('viewAny', Order::class);

        $payment->update([
            ...$request->validated(),
            'updated_by' => $request->user()->id,
        ]);

        $audit->log('manual_payment.updated', $request->user(), $payment->refresh(), [
            'reference_number' => $payment->reference_number,
            'status' => $payment->status,
            'amount' => $payment->amount,
        ]);

        return ManualPaymentResource::make($payment->refresh()->load(['order', 'account']));
    }

    public function destroy(ManualPayment $payment, AuditLogger $audit): JsonResponse
    {
        Gate::authorize('viewAny', Order::class);

        $reference = $payment->reference_number;
        $payment->delete();

        $audit->log('manual_payment.deleted', request()->user(), null, ['reference_number' => $reference]);

        return response()->json(['message' => 'Manual payment deleted.']);
    }
}
