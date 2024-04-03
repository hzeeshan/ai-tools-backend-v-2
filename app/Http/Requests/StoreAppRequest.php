<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreAppRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'name' => 'required|max:255',
            'slug' => 'required|max:255',
            //'short_description' => 'required',
            'platform_id' => 'required|exists:platforms,id',
            //'license_type' => 'required|exists:license_types,id',
            //'website_link' => 'required|url',
            'price_plans' => 'required|json',
            'mainImage' => 'required|file|image|max:1024', // max 1MB
            'otherImages.*' => 'nullable|file|image|max:512', // max 500KB
        ];
    }
}
