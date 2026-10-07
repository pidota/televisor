<?php

namespace App\Http\Requests\ScreenGroup;

use Illuminate\Foundation\Http\FormRequest;

class SyncScreenGroupMembersRequest extends FormRequest
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
            'screens' => ['nullable', 'array'],
            'screens.*' => ['integer', 'exists:screens,id'],
        ];
    }
}
