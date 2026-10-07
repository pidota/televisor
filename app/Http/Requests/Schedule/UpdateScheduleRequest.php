<?php

namespace App\Http\Requests\Schedule;

use Illuminate\Foundation\Http\FormRequest;

class UpdateScheduleRequest extends FormRequest
{
    public function authorize(): bool
    {
        $schedule = $this->route('schedule');

        return $schedule && $this->user()?->can('update', $schedule);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return (new StoreScheduleRequest)->rules();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'use_time_rules' => $this->boolean('use_time_rules'),
            'is_active' => $this->boolean('is_active'),
        ]);
    }
}
