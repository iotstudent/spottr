<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class resetPasswordRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [

            'user_id' => 'required|string',
            'password' => 'required|min:8|max:24|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%@&()+=-_]).*$/|confirmed'
        ];
    }
}
