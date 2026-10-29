<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CityResource;
use App\Models\City;
use Illuminate\Http\Request;

class CityController extends Controller
{
    public function index(Request $request)
    {
        $validated = $request->validate([
            'zone_id' => ['sometimes', 'nullable', 'integer', 'exists:zones,id'],
        ]);

        $query = City::query()->where('is_active', true);

        if (array_key_exists('zone_id', $validated) && $validated['zone_id'] !== null) {
            $query->where('zone_id', $validated['zone_id']);
        }

        return CityResource::collection(
            $query->with('zone')->orderBy('sort_order')->orderBy('id')->get()
        );
    }

    public function show(City $city): CityResource
    {
        if (! $city->is_active) {
            abort(404);
        }

        return CityResource::make($city->load('zone'));
    }
}
