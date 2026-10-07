<?php

namespace App\Http\Requests\UrgentMessage;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUrgentMessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        $message = $this->route('urgentMessage');

        return $message && $this->user()?->can('update', $message);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return (new StoreUrgentMessageRequest)->rules();
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'applies_to_all_screens' => $this->boolean('applies_to_all_screens'),
            'publish' => $this->boolean('publish'),
        ]);
    }
}
