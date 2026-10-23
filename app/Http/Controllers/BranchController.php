<?php

namespace App\Http\Controllers;

use App\Http\Requests\BranchRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

class BranchController extends Controller
{
    public function index(): View
    {
        return view('branches.index', [
            'branches' => Branch::withCount('users')->orderByDesc('is_active')->orderBy('name')->get(),
        ]);
    }

    public function store(BranchRequest $request): JsonResponse
    {
        $branch = Branch::create($request->validated());
        AuditLog::record('branch.created', $branch, null, $branch->toArray());

        return response()->json(['ok' => true, 'message' => 'Sucursal creada', 'data' => $branch], 201);
    }

    public function update(BranchRequest $request, Branch $branch): JsonResponse
    {
        $old = $branch->only(array_keys($request->validated()));
        $branch->update($request->validated());
        AuditLog::record('branch.updated', $branch, $old, $branch->only(array_keys($old)));

        return response()->json(['ok' => true, 'message' => 'Sucursal actualizada', 'data' => $branch]);
    }

    public function toggle(Branch $branch): JsonResponse
    {
        if ($branch->is_active && Branch::where('is_active', true)->count() === 1) {
            return response()->json(['ok' => false, 'message' => 'Debe quedar al menos una sucursal activa.'], 409);
        }

        $branch->update(['is_active' => ! $branch->is_active]);
        AuditLog::record($branch->is_active ? 'branch.activated' : 'branch.deactivated', $branch);

        return response()->json([
            'ok' => true,
            'message' => $branch->is_active ? 'Sucursal activada' : 'Sucursal desactivada',
            'data' => $branch,
        ]);
    }
}
