<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class UpdateSurveyChecklistRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Go/No-Go matrix — 10 items
            'gonogo_matrix'               => ['nullable', 'array'],
            'gonogo_matrix.*.item_key'    => ['required_with:gonogo_matrix', 'string', 'max:100'],
            'gonogo_matrix.*.category'    => ['required_with:gonogo_matrix', 'string', 'max:100'],
            'gonogo_matrix.*.result'      => ['required_with:gonogo_matrix', 'string', 'in:yes,no,n_a,n_r'],
            'gonogo_matrix.*.remarks'     => ['nullable', 'string', 'max:500'],

            // Installation feasibility — 8 items
            'feasibility'                 => ['nullable', 'array'],
            'feasibility.*.item_key'      => ['required_with:feasibility', 'string', 'max:100'],
            'feasibility.*.result'        => ['required_with:feasibility', 'string', 'in:yes,no,n_a,n_r'],
            'feasibility.*.remarks'       => ['nullable', 'string', 'max:500'],

            // Risk register — variable length
            'risk_register'               => ['nullable', 'array'],
            'risk_register.*.risk_type'   => ['required_with:risk_register', 'string', 'in:site_access,structural_timeline,weather,labor,material,client,regulatory,other'],
            'risk_register.*.severity'    => ['required_with:risk_register', 'string', 'in:high,medium,low'],
            'risk_register.*.mitigation_plan' => ['nullable', 'string', 'max:1000'],
        ];
    }
}
