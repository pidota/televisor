<?php

namespace App\Http\Requests\Screen;

use Illuminate\Foundation\Http\FormRequest;

class VerifyPairingCodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('pair', \App\Models\Screen::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'code' => ['required', 'string', 'size:6', 'regex:/^\d{6}$/'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'code.required' => 'Ingrese el código de vinculación.',
            'code.size' => 'El código debe tener 6 dígitos.',
            'code.regex' => 'El código solo debe contener números.',
        ];
    }
}
