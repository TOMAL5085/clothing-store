<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewCancellationRequest;
use App\Http\Resources\CancellationRequestResource;
use App\Models\CancellationRequest;
use App\Models\Order;
use App\Services\AuditLogger;
use App\Services\OrderResolutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CancellationManagementController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $status = $request->query('status');
        $requests = CancellationRequest::query()
            ->with(['order.user', 'refund'])
            ->when(is_string($status) && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate((int) $request->query('per_page', 50));

        return CancellationRequestResource::collection($requests);
    }

    public function update(ReviewCancellationRequest $request, CancellationRequest $cancellationRequest, OrderResolutionService $service, AuditLogger $audit): CancellationRequestResource
    {
        Gate::authorize('viewAny', Order::class);

        $reviewed = $service->reviewCancellation(
            $cancellationRequest,
            $request->user(),
            $request->validated('decision'),
            $request->validated('admin_reason') ?? null,
        );

        $audit->log('cancellation.reviewed', $request->user(), $reviewed, [
            'order_number' => $reviewed->order?->number,
            'decision' => $request->validated('decision'),
            'status' => $reviewed->status,
        ]);

        return CancellationRequestResource::make($reviewed);
    }
}
