<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreContentRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'description' => ['required', 'string', 'max:500000'],
        ];
    }

    public function messages(): array
    {
        return [
            'description.required' => 'Konten tidak boleh kosong.',
            'description.max'      => 'Konten terlalu panjang (maksimal 500.000 karakter).',
        ];
    }
}
