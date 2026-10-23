<?php

namespace App\Http\Controllers\Super;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\Business;
use App\Services\BusinessService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

/** Panel del dueño de la plataforma: alta y suspensión de negocios */
class BusinessController extends Controller
{
    public function index(): View
    {
        return view('super.businesses', [
            'businesses' => Business::withCount(['branches', 'users'])->orderBy('name')->get(),
            'industries' => Business::INDUSTRIES,
        ]);
    }

    public function store(Request $request, BusinessService $service): JsonResponse
    {
        $request->merge([
            'subdomain' => mb_strtolower(trim((string) $request->input('subdomain'))),
            'admin_username' => mb_strtolower(trim((string) $request->input('admin_username'))),
        ]);

        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            // subdominio: letras, números y guiones; sin guion al inicio ni al final
            'subdomain' => ['required', 'regex:/^[a-z0-9](?:[a-z0-9-]{1,28}[a-z0-9])$/', Rule::notIn(Business::RESERVED_SUBDOMAINS), 'unique:businesses,slug'],
            'ruc' => ['nullable', 'digits:11'],
            'industry' => ['required', Rule::in(array_keys(Business::INDUSTRIES))],
            'branch_name' => ['nullable', 'string', 'max:100'],
            'admin_name' => ['required', 'string', 'max:100'],
            'admin_username' => ['required', 'regex:/^[a-z0-9._]{3,30}$/', 'unique:users,username'],
            'admin_password' => ['required', Password::min(8)],
        ], [
            'subdomain.regex' => 'El subdominio debe tener de 3 a 30 caracteres: letras minúsculas, números o guiones.',
            'subdomain.not_in' => 'Ese subdominio está reservado, elige otro.',
            'subdomain.unique' => 'Ese subdominio ya lo usa otro negocio.',
            'admin_username.regex' => 'El usuario debe tener de 3 a 30 caracteres: letras minúsculas, números, punto o guion bajo (sin espacios).',
        ], [
            'name' => 'nombre del negocio',
            'subdomain' => 'subdominio',
            'ruc' => 'RUC',
            'industry' => 'rubro',
            'branch_name' => 'sucursal principal',
            'admin_name' => 'nombre del administrador',
            'admin_username' => 'usuario del administrador',
            'admin_password' => 'contraseña',
        ]);

        $business = $service->create($data);

        return response()->json([
            'ok' => true,
            'message' => "Negocio «{$business->name}» creado",
            'data' => ['url' => $business->url()],
        ], 201);
    }

    public function toggle(Business $business): JsonResponse
    {
        $business->update([
            'status' => $business->isActive() ? Business::STATUS_SUSPENDED : Business::STATUS_ACTIVE,
        ]);
        AuditLog::record($business->isActive() ? 'business.activated' : 'business.suspended', $business);

        return response()->json([
            'ok' => true,
            'message' => $business->isActive() ? 'Negocio activado' : 'Negocio suspendido',
        ]);
    }
}
