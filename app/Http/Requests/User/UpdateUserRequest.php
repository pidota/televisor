<?php

namespace App\Http\Requests\User;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        $target = $this->route('user');

        return $target instanceof \App\Models\User
            && ($this->user()?->can('update', $target) ?? false);
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        /** @var \App\Models\User $user */
        $user = $this->route('user');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'password' => ['nullable', 'confirmed', Password::defaults()],
            'role' => ['required', Rule::in(['admin', 'operator'])],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var \App\Models\User $target */
            $target = $this->route('user');
            $actor = $this->user();

            if ($actor === null || ! $target->hasRole('admin')) {
                return;
            }

            if ($this->string('role')->toString() !== 'admin' && $this->isLastAdmin($target)) {
                $validator->errors()->add('role', 'Debe quedar al menos un administrador en el sistema.');
            }
        });
    }

    private function isLastAdmin(\App\Models\User $user): bool
    {
        return \App\Models\User::query()
            ->whereHas('roles', fn ($q) => $q->where('name', 'admin'))
            ->whereKeyNot($user->id)
            ->doesntExist();
    }
}
