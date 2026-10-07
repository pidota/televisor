<?php

namespace App\Http\Requests\ScreenGroup;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScreenGroupRequest extends FormRequest
{
    public function authorize(): bool
    {
        $group = $this->route('screenGroup');

        return $group && $this->user()?->can('update', $group);
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
