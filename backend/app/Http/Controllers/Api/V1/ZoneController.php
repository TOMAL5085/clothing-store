<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\ZoneResource;
use App\Models\Zone;

class ZoneController extends Controller
{
    public function index()
    {
        return ZoneResource::collection(
            Zone::where('is_active', true)->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function show(Zone $zone): ZoneResource
    {
        if (! $zone->is_active) {
            abort(404);
        }

        return ZoneResource::make($zone->load('cities'));
    }
}
