<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ManualPaymentResource;
use App\Models\ManualPayment;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class ManualPaymentController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'status' => ['sometimes', 'string', Rule::in(ManualPayment::STATUSES)],
            'payment_method' => ['sometimes', 'string', Rule::in(ManualPayment::PAYMENT_METHODS)],
        ]);

        return ManualPaymentResource::collection(
            ManualPayment::query()->with(['order', 'account'])
                ->when($validated['status'] ?? null, fn ($query) => $query->where('status', $validated['status']))
                ->when($validated['payment_method'] ?? null, fn ($query) => $query->where('payment_method', $validated['payment_method']))
                ->orderBy('payment_date', 'desc')
                ->orderBy('id')
                ->get()
        );
    }

    public function show(ManualPayment $payment): ManualPaymentResource
    {
        return ManualPaymentResource::make($payment->load(['order', 'account']));
    }
}
