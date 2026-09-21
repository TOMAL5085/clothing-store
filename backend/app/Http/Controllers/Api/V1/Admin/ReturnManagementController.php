<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ReviewReturnRequest;
use App\Http\Resources\ReturnRequestResource;
use App\Models\Order;
use App\Models\ReturnRequest;
use App\Services\OrderResolutionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ReturnManagementController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Order::class);

        $status = $request->query('status');
        $returns = ReturnRequest::query()
            ->with(['order.user', 'items.orderItem', 'refund'])
            ->when(is_string($status) && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->latest()
            ->paginate((int) $request->query('per_page', 50));

        return ReturnRequestResource::collection($returns);
    }

    public function update(ReviewReturnRequest $request, ReturnRequest $returnRequest, OrderResolutionService $service): ReturnRequestResource
    {
        Gate::authorize('viewAny', Order::class);

        $reviewed = $service->reviewReturn(
            $returnRequest,
            $request->user(),
            $request->validated('decision'),
            $request->validated('admin_reason') ?? null,
        );

        return ReturnRequestResource::make($reviewed);
    }

    public function received(Request $request, ReturnRequest $returnRequest, OrderResolutionService $service): ReturnRequestResource
    {
        Gate::authorize('viewAny', Order::class);

        $validated = $request->validate(['admin_reason' => ['sometimes', 'nullable', 'string', 'max:1000']]);

        return ReturnRequestResource::make($service->markReturnReceived($returnRequest, $request->user(), $validated['admin_reason'] ?? null));
    }
}
