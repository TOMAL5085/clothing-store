<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateNotificationPreferencesRequest;
use App\Http\Resources\NotificationPreferenceResource;
use App\Services\NotificationPreferenceService;
use Illuminate\Http\Request;

class NotificationPreferenceController extends Controller
{
    /**
     * The authenticated user's effective preferences across every known
     * category. Only ever the caller's own rows — there is no user
     * parameter to tamper with.
     */
    public function index(Request $request, NotificationPreferenceService $service)
    {
        return NotificationPreferenceResource::collection(
            $service->forUser($request->user())
        );
    }

    /**
     * Replace channel flags for the given categories of the caller's own
     * preferences. Omitted categories keep their current state.
     */
    public function update(UpdateNotificationPreferencesRequest $request, NotificationPreferenceService $service)
    {
        return NotificationPreferenceResource::collection(
            $service->updateForUser($request->user(), $request->validated()['preferences'])
        );
    }
}
