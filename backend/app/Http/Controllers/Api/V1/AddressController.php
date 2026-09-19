<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Address\StoreAddressRequest;
use App\Http\Requests\Address\UpdateAddressRequest;
use App\Http\Resources\AddressResource;
use App\Models\Address;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Facades\DB;

class AddressController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        return AddressResource::collection(
            $request->user()->addresses()->latest('is_default')->latest()->get()
        );
    }

    public function store(StoreAddressRequest $request): JsonResponse
    {
        $address = DB::transaction(function () use ($request) {
            $data = $this->payload($request->validated());

            if ($data['is_default'] ?? false) {
                $request->user()->addresses()->update(['is_default' => false]);
            }

            if (! $request->user()->addresses()->exists()) {
                $data['is_default'] = true;
            }

            return $request->user()->addresses()->create($data);
        });

        return response()->json(['data' => AddressResource::make($address)], 201);
    }

    public function show(Request $request, Address $address): AddressResource
    {
        $this->authorizeAddress($request, $address);

        return AddressResource::make($address);
    }

    public function update(UpdateAddressRequest $request, Address $address): AddressResource
    {
        $this->authorizeAddress($request, $address);

        return AddressResource::make(DB::transaction(function () use ($request, $address) {
            $data = $this->payload($request->validated());

            if ($data['is_default'] ?? false) {
                $request->user()->addresses()->whereKeyNot($address->id)->update(['is_default' => false]);
            }

            $address->update($data);

            return $address->refresh();
        }));
    }

    public function destroy(Request $request, Address $address): JsonResponse
    {
        $this->authorizeAddress($request, $address);

        $wasDefault = $address->is_default;
        $address->delete();

        if ($wasDefault) {
            $request->user()->addresses()->latest()->first()?->update(['is_default' => true]);
        }

        return response()->json(['message' => 'Address deleted.']);
    }

    private function authorizeAddress(Request $request, Address $address): void
    {
        abort_unless($address->user_id === $request->user()->id, 403);
    }

    private function payload(array $data): array
    {
        $map = [
            'label' => 'label',
            'firstName' => 'first_name',
            'lastName' => 'last_name',
            'email' => 'email',
            'phone' => 'phone',
            'address' => 'address',
            'city' => 'city',
            'postalCode' => 'postal_code',
            'country' => 'country',
            'isDefault' => 'is_default',
        ];

        $payload = [];
        foreach ($map as $input => $column) {
            if (array_key_exists($input, $data)) {
                $payload[$column] = $data[$input];
            }
        }

        return $payload;
    }
}
