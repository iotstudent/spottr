<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreFeedbackRequest extends FormRequest
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

            'user_id' => 'required|exists:users,id',
            'category' => 'required|string',
            'description' => 'sometimes|nullable|string',
            'screen_shot' => 'sometimes|nullable|mimes:jpeg,jpg,png|max:10240',

        ];
    }
}
