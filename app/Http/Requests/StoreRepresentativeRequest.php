<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreRepresentativeRequest extends FormRequest
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

            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'job_title' => 'required|string',
            'bio' => 'required|string',
            'phone' => 'nullable|regex:/^([0-9\s\-\+\(\)]*)$/|min:10',
            'email' => 'required|email',
            'pic' => 'sometimes|nullable|mimes:jpeg,jpg,png|max:10240',
        ];
    }
}
