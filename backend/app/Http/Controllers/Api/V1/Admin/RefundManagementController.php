<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateRefundRequest;
use App\Http\Resources\RefundResource;
use App\Models\Order;
use App\Models\Refund;
use App\Services\AuditLogger;
use App\Services\OrderResolutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class RefundManagementController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $status = $request->query('status');
        $refunds = Refund::query()
            ->with(['order', 'returnRequest', 'cancellationRequest'])
            ->when(is_string($status) && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate(min(max((int) $request->query('per_page', 50), 1), 100));

        return RefundResource::collection($refunds);
    }

    public function update(UpdateRefundRequest $request, Refund $refund, OrderResolutionService $service, AuditLogger $audit): RefundResource
    {
        Gate::authorize('viewAny', Order::class);

        $previousStatus = $refund->status;

        $updated = $service->updateRefund(
            $refund,
            $request->user(),
            $request->validated('status'),
            $request->validated('provider_reference') ?? null,
            $request->validated('failure_reason') ?? null,
        );

        $audit->log('refund.updated', $request->user(), $updated, [
            'order_number' => $updated->order?->number,
            'previous_status' => $previousStatus,
            'new_status' => $updated->status,
            'amount' => (float) $updated->amount,
            'currency' => $updated->currency,
        ]);

        return RefundResource::make($updated);
    }
}
