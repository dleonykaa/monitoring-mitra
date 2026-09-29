<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Models\District;
use App\Models\Village;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class RegionController extends Controller
{
    public function districts(): JsonResponse
    {
        return response()->json(District::query()->with('villages')->get());
    }

    public function storeDistrict(Request $request): JsonResponse
    {
        $district = District::query()->create($request->validate(['name' => ['required', 'string', 'unique:districts,name']]));

        return response()->json($district, 201);
    }

    public function storeVillage(Request $request, District $district): JsonResponse
    {
        $village = $district->villages()->create($request->validate([
            'name' => ['required', 'string'],
            'type' => ['nullable', 'string'],
        ]));

        return response()->json($village, 201);
    }
}
