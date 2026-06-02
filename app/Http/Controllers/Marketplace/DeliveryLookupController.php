<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\DeliveryLookupRequest;
use App\Services\DeliveryDirectoryService;

class DeliveryLookupController extends Controller
{
    public function cities(DeliveryLookupRequest $request, DeliveryDirectoryService $directory)
    {
        $data = $request->validated();

        return response()->json([
            'items' => $directory->cities($data['carrier'], $data['q'] ?? null),
        ]);
    }

    public function warehouses(DeliveryLookupRequest $request, DeliveryDirectoryService $directory)
    {
        $data = $request->validated();

        return response()->json([
            'items' => $directory->warehouses($data['carrier'], $data['city_ref'] ?? null, $data['q'] ?? null),
        ]);
    }
}
