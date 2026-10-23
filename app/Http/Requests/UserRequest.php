<?php

namespace App\Http\Requests;

use App\Support\CurrentBusiness;
use App\Support\DefaultRoles;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UserRequest extends FormRequest
{
    protected function prepareForValidation(): void
    {
        $this->merge(['username' => mb_strtolower(trim((string) $this->input('username')))]);
    }

    public function rules(): array
    {
        $businessId = app(CurrentBusiness::class)->id();
        $editing = $this->route('user') !== null;

        return [
            'name' => ['required', 'string', 'max:100'],
            'username' => ['required', 'regex:/^[a-z0-9._]{3,30}$/', Rule::unique('users')->where('business_id', $businessId)->ignore($this->route('user'))],
            'phone' => ['nullable', 'string', 'max:20'],
            'role' => ['required', Rule::in(array_keys(DefaultRoles::ROLES))],
            // el cajero siempre tiene sucursal; el admin puede no tenerla (= todas)
            'branch_id' => [
                Rule::requiredIf($this->input('role') === DefaultRoles::CASHIER),
                'nullable',
                Rule::exists('branches', 'id')->where('business_id', $businessId)->where('is_active', true),
            ],
            'password' => [$editing ? 'nullable' : 'required', Password::min(8)],
        ];
    }

    public function messages(): array
    {
        return ['username.regex' => 'El usuario debe tener de 3 a 30 caracteres: letras minúsculas, números, punto o guion bajo (sin espacios).'];
    }

    public function attributes(): array
    {
        return [
            'name' => 'nombre',
            'username' => 'usuario',
            'phone' => 'teléfono',
            'role' => 'rol',
            'branch_id' => 'sucursal',
            'password' => 'contraseña',
        ];
    }

    /** Datos listos para User::create / update (sin contraseña vacía) */
    public function userData(): array
    {
        $data = $this->safe()->only(['name', 'username', 'phone', 'branch_id']);

        if ($this->filled('password')) {
            $data['password'] = $this->input('password');
        }

        return $data;
    }
}
