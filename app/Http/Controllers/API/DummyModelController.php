<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\DummyModel;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DummyModelController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth:sanctum')->except(['index', 'show']);
        $this->middleware('permission:dummy-model.create')->only('store');
        $this->middleware('permission:dummy-model.update')->only('update');
        $this->middleware('permission:dummy-model.delete')->only('destroy');
    }

    public function index(): JsonResponse
    {
        return response()->json(DummyModel::all());
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $dummy = DummyModel::create($validated);

        return response()->json($dummy, 201);
    }

    public function show(DummyModel $dummyModel): JsonResponse
    {
        return response()->json($dummyModel);
    }

    public function update(Request $request, DummyModel $dummyModel): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
        ]);

        $dummyModel->update($validated);

        return response()->json($dummyModel->refresh());
    }

    public function destroy(DummyModel $dummyModel): JsonResponse
    {
        $dummyModel->delete();

        return response()->json([], 204);
    }
}
