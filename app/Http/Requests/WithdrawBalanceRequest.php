<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class WithdrawBalanceRequest extends FormRequest
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
            'amount' => 'required|integer|min:1',
            'notes' => 'nullable|string|max:255',
            'authorization_code' => 'required|string|size:12|alpha_num:ascii',
            'admin_password' => 'required|string|max:255',
        ];
    }
}
