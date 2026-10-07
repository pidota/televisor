<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;

class DeviceHeartbeatRequest extends FormRequest
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
            'status' => ['nullable', 'string', 'max:20'],
            'playlist_id' => ['nullable', 'integer'],
            'content_id' => ['nullable', 'integer'],
            'media_asset_id' => ['nullable', 'integer'],
            'manifest_version' => ['nullable', 'integer', 'min:0'],
            'storage_free' => ['nullable', 'integer', 'min:0'],
            'storage_total' => ['nullable', 'integer', 'min:0'],
            'app_version' => ['nullable', 'string', 'max:50'],
            'device_model' => ['nullable', 'string', 'max:255'],
            'android_version' => ['nullable', 'string', 'max:50'],
            'resolution' => ['nullable', 'string', 'max:20'],
            'payload' => ['nullable', 'array'],
        ];
    }
}
