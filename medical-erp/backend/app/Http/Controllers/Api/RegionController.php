<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreRegionRequest;
use App\Http\Requests\UpdateRegionRequest;
use App\Http\Resources\RegionResource;
use App\Models\Region;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RegionController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = Region::query();

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('name_ar', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        if ($request->boolean('include_counties')) {
            $query->with('counties');
        }

        $regions = $query->withCount('counties')->orderBy('name')->get();

        return RegionResource::collection($regions);
    }

    public function store(StoreRegionRequest $request): JsonResponse
    {
        $region = Region::create($request->validated());

        return (new RegionResource($region))
            ->response()
            ->setStatusCode(201);
    }

    public function show(Request $request, Region $region): RegionResource
    {
        if ($request->boolean('include_counties')) {
            $region->load('counties');
        }

        $region->loadCount('counties');

        return new RegionResource($region);
    }

    public function update(UpdateRegionRequest $request, Region $region): RegionResource
    {
        $region->update($request->validated());

        return new RegionResource($region);
    }

    public function destroy(Region $region): JsonResponse
    {
        $region->delete();

        return response()->json(null, 204);
    }
}
