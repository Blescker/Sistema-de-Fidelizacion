<?php

namespace App\Http\Requests;

use App\Support\CurrentBusiness;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class BranchRequest extends FormRequest
{
    public function rules(): array
    {
        $businessId = app(CurrentBusiness::class)->id();

        return [
            'name' => [
                'required', 'string', 'max:100',
                Rule::unique('branches')
                    ->where('business_id', $businessId)
                    ->ignore($this->route('branch')),
            ],
            'address' => ['nullable', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:20'],
        ];
    }

    public function attributes(): array
    {
        return ['name' => 'nombre', 'address' => 'dirección', 'phone' => 'teléfono'];
    }
}
