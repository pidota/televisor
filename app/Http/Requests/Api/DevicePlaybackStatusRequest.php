<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DevicePlaybackStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        return [
            'playlist_id' => ['nullable', 'integer'],
            'content_id' => ['nullable', 'integer'],
            'media_asset_id' => ['nullable', 'integer'],
            'status' => ['nullable', 'string', 'max:30'],
        ];
    }
}
