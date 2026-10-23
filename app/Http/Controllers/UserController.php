<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\User;
use App\Support\DefaultRoles;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        return view('users.index', [
            'users' => User::with('roles', 'branch')->orderByDesc('is_active')->orderBy('name')->get(),
            'branches' => Branch::where('is_active', true)->orderBy('name')->get(),
            'roles' => DefaultRoles::LABELS,
        ]);
    }

    public function store(UserRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request) {
            $user = User::create($request->userData());
            $user->syncRoles([$request->input('role')]);

            return $user;
        });

        AuditLog::record('user.created', $user, null, $user->only('name', 'username', 'branch_id') + ['role' => $request->input('role')]);

        return response()->json(['ok' => true, 'message' => 'Usuario creado'], 201);
    }

    public function update(UserRequest $request, User $user): JsonResponse
    {
        $old = $user->only('name', 'username', 'phone', 'branch_id') + ['role' => $user->roles->first()?->name];

        if ($user->is($request->user()) && $request->input('role') !== $old['role']) {
            return response()->json(['ok' => false, 'message' => 'No puedes cambiar tu propio rol.'], 409);
        }

        DB::transaction(function () use ($request, $user) {
            $user->update($request->userData());
            $user->syncRoles([$request->input('role')]);
        });

        AuditLog::record('user.updated', $user, $old, $user->only('name', 'username', 'phone', 'branch_id') + ['role' => $request->input('role')]);

        return response()->json(['ok' => true, 'message' => 'Usuario actualizado']);
    }

    public function toggle(Request $request, User $user): JsonResponse
    {
        if ($user->is($request->user())) {
            return response()->json(['ok' => false, 'message' => 'No puedes desactivar tu propio usuario.'], 409);
        }

        $user->update(['is_active' => ! $user->is_active]);
        AuditLog::record($user->is_active ? 'user.activated' : 'user.deactivated', $user);

        return response()->json([
            'ok' => true,
            'message' => $user->is_active ? 'Usuario activado' : 'Usuario desactivado',
        ]);
    }
}
