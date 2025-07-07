<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class registerCorporateRequest extends FormRequest
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

            'company_name' => 'required|string',
            'company_address' => 'required|string',
            'company_description' => 'required|string',
            'industry_id' => 'required|exists:industries,id',
            'phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'email' => 'required|email|unique:users,email',
            'pic' => 'required|image|mimes:jpeg,jpg,png|max:10240',
            'kyc_doc' => 'required|mimes:jpeg,jpg,png,pdf,doc|max:10240',
            'tags' => 'nullable|array',
            'tags.*' => 'string',
            'password' => 'required|min:8|max:24|regex:/^.*(?=.{3,})(?=.*[a-zA-Z])(?=.*[0-9])(?=.*[\d\x])(?=.*[!$#%@&()+=-_]).*$/|confirmed'
        ];
    }
}
