<?php

namespace App\Http\Requests\Admin;

use Illuminate\Foundation\Http\FormRequest;

class DashboardQueryRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'activity_limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
            'urgent_limit'   => ['sometimes', 'integer', 'min:1', 'max:20'],
        ];
    }
}
