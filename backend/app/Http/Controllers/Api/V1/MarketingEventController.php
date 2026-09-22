<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\StoreMarketingEventRequest;
use App\Http\Resources\MarketingEventResource;
use App\Services\Marketing\MarketingEventService;
use Illuminate\Http\JsonResponse;

class MarketingEventController extends Controller
{
    /**
     * First-party event ingestion. Authenticated identity comes from
     * Sanctum; anonymous callers are keyed by their anonymous identifier.
     * Server-authoritative events (purchase, refunds, …) are rejected here
     * — the browser can never manufacture a conversion.
     */
    public function store(StoreMarketingEventRequest $request, MarketingEventService $events): JsonResponse
    {
        if (! config('marketing.events_enabled', true)) {
            return response()->json(['message' => 'Marketing measurement is disabled.'], 503);
        }

        $result = $events->ingest($request->user(), $request->validated());

        if ($result['status'] === 'duplicate') {
            return response()->json(['data' => MarketingEventResource::make($result['event'])], 200);
        }

        if ($result['status'] === 'declined') {
            return response()->json(['data' => ['accepted' => false]], 202);
        }

        return response()->json(['data' => MarketingEventResource::make($result['event'])], 201);
    }
}
