<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCountyRequest;
use App\Http\Requests\UpdateCountyRequest;
use App\Http\Resources\CountyResource;
use App\Models\County;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CountyController extends Controller
{
    public function index(Request $request): AnonymousResourceCollection
    {
        $query = County::with('region');

        if ($request->has('region_id')) {
            $query->where('region_id', $request->input('region_id'));
        }

        if ($request->has('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('name', 'ilike', "%{$search}%")
                  ->orWhere('name_ar', 'ilike', "%{$search}%")
                  ->orWhere('code', 'ilike', "%{$search}%");
            });
        }

        $counties = $query->orderBy('name')->get();

        return CountyResource::collection($counties);
    }

    public function store(StoreCountyRequest $request): JsonResponse
    {
        $county = County::create($request->validated());
        $county->load('region');

        return (new CountyResource($county))
            ->response()
            ->setStatusCode(201);
    }

    public function show(County $county): CountyResource
    {
        $county->load('region');

        return new CountyResource($county);
    }

    public function update(UpdateCountyRequest $request, County $county): CountyResource
    {
        $county->update($request->validated());
        $county->load('region');

        return new CountyResource($county);
    }

    public function destroy(County $county): JsonResponse
    {
        $county->delete();

        return response()->json(null, 204);
    }
}
