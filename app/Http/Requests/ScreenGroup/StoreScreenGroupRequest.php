<?php

namespace App\Http\Requests\ScreenGroup;

use Illuminate\Foundation\Http\FormRequest;

class StoreScreenGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\ScreenGroup::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:2000'],
        ];
    }
}
