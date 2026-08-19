<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class AddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [

            'label' => 'required|string|max:50',

            'address_line' => 'required|string|max:255',

            'city' => 'required|string|max:100',

            'state' => 'required|string|max:100',

            'country' => 'nullable|string|max:100',

            'latitude' => 'nullable|numeric',

            'longitude' => 'nullable|numeric',

            'is_default' => 'boolean',
        ];
    }
}