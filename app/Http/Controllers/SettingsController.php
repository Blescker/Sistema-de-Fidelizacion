<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Business;
use App\Support\CurrentBusiness;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SettingsController extends Controller
{
    public function edit(): View
    {
        return view('settings.edit', ['industries' => Business::INDUSTRIES]);
    }

    public function update(Request $request, CurrentBusiness $current): JsonResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'ruc' => ['nullable', 'digits:11'],
            'industry' => ['required', Rule::in(array_keys(Business::INDUSTRIES))],
            'points_label' => ['required', 'string', 'max:30'],
            'primary_color' => ['required', 'regex:/^#[0-9A-Fa-f]{6}$/'],
        ], [], [
            'name' => 'nombre del negocio',
            'ruc' => 'RUC',
            'industry' => 'rubro',
            'points_label' => 'nombre de los puntos',
            'primary_color' => 'color',
        ]);

        $business = $current->get();
        $old = $business->only(array_keys($data));
        $business->update($data);
        AuditLog::record('business.updated', $business, $old, $business->only(array_keys($data)));

        return response()->json(['ok' => true, 'message' => 'Configuración guardada']);
    }
}
