<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class updateCorporateRequest extends FormRequest
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
            'company_name' => 'sometimes|string',
            'company_size' => 'sometimes|numeric',
            'company_address' => 'sometimes|string',
            'company_description' => 'sometimes|string',
            'industry_id' => 'sometimes|exists:industries,id',
            'phone' => 'sometimes|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'email' => [
                'sometimes',
                'email',
                Rule::unique('users', 'email')->ignore($this->user()->id),
            ],
            'pic' => 'sometimes|nullable|image|mimes:jpeg,jpg,png|max:10240',

        ];
    }
}
