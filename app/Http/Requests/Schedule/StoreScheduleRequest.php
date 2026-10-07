<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class StoreScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\Schedule::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'playlist_id' => ['required', 'integer', 'exists:playlists,id'],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
            'is_active' => ['nullable', 'boolean'],
            'screens' => ['nullable', 'array'],
            'screens.*' => ['integer', 'exists:screens,id'],
            'groups' => ['nullable', 'array'],
            'groups.*' => ['integer', 'exists:screen_groups,id'],
            'use_time_rules' => ['nullable', 'boolean'],
            'days' => ['nullable', 'array'],
            'days.*' => ['integer', 'min:1', 'max:7'],
            'start_time' => ['nullable', 'date_format:H:i'],
            'end_time' => ['nullable', 'date_format:H:i'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'use_time_rules' => $this->boolean('use_time_rules'),
            'is_active' => $this->boolean('is_active', true),
        ]);
    }
}
