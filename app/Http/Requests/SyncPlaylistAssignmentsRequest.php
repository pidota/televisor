<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SyncPlaylistAssignmentsRequest extends FormRequest
{
    public function authorize(): bool
    {
        $playlist = $this->route('playlist');

        return $playlist && $this->user()?->can('update', $playlist);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'screens' => ['nullable', 'array'],
            'screens.*' => ['integer', 'exists:screens,id'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['integer', 'exists:screen_groups,id'],
        ];
    }
}
