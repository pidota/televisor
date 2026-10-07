<?php

namespace App\Http\Requests\UrgentMessage;

use App\Enums\UrgentMessageLayout;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
class StoreUrgentMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\UrgentMessage::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'body' => ['required', 'string', 'max:5000'],
            'layout' => ['required', Rule::enum(UrgentMessageLayout::class)],
            'starts_at' => ['required', 'date'],
            'ends_at' => ['required', 'date', 'after:starts_at'],
            'priority' => ['nullable', 'integer', 'min:0', 'max:999'],
            'applies_to_all_screens' => ['nullable', 'boolean'],
            'screens' => ['nullable', 'array'],
            'screens.*' => ['integer', 'exists:screens,id'],
            'publish' => ['nullable', 'boolean'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            if (! $this->boolean('applies_to_all_screens') && empty($this->input('screens'))) {
                $validator->errors()->add('screens', 'Seleccione al menos una pantalla o marque "Todas las pantallas".');
            }
        });
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'applies_to_all_screens' => $this->boolean('applies_to_all_screens'),
            'publish' => $this->boolean('publish'),
        ]);
    }
}
