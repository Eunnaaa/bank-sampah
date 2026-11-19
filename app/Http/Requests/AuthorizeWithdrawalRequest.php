<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class AuthorizeWithdrawalRequest extends FormRequest
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
            'amount' => 'required|integer|min:1',
            'current_pin' => 'required|digits:4',
            'channel' => 'nullable|in:tunai,transfer_bank,ewallet',
            'provider' => 'nullable|required_if:channel,transfer_bank,ewallet|string|max:50',
            'account_number' => 'nullable|required_if:channel,transfer_bank,ewallet|string|max:50',
            'account_holder' => 'nullable|required_if:channel,transfer_bank,ewallet|string|max:100',
        ];
    }
}
