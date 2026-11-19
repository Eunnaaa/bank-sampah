<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class StoreWeighingRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'resident_id' => 'required|uuid',
            'request_id' => 'required|uuid',
            'items' => 'required|array|min:1|max:100',
            'items.*.category_id' => 'required|uuid',
            'items.*.weight' => 'required|numeric|min:0.01|max:99999999.99|decimal:0,2',
            'notes' => 'nullable|string|max:255',
        ];
    }
}
