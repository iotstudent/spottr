<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreProductRequest extends FormRequest
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

            'brand_id' => 'nullable|exists:brands,id',
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'sub_category_id' => 'nullable|exists:sub_categories,id',
            'description' => 'nullable|string',
            'weight' => 'nullable|string|max:255',
            'dimension' => 'nullable|string|max:255',
            'additional_specification' => 'nullable|string',
            'attribute' => 'nullable|array',
            'variants' => 'nullable|array',
            'tags' => 'nullable|string',
            'is_available' => 'boolean',
            'price' => 'required|numeric|min:0',
            'product_code' => 'nullable|string|max:255|unique:products,product_code',
            'product_image_one' => 'sometimes|nullable|image|mimes:jpeg,jpg,png|max:10240',
            'product_image_two' => 'sometimes|nullable|image|mimes:jpeg,jpg,png|max:10240',
            'product_image_three' => 'sometimes|nullable|image|mimes:jpeg,jpg,png|max:10240',
            'product_image_four' => 'sometimes|nullable|image|mimes:jpeg,jpg,png|max:10240',
        ];
    }
}
