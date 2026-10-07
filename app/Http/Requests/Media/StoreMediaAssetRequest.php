<?php

namespace App\Http\Requests\Media;

use App\Enums\MediaAssetType;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rules\File;

class StoreMediaAssetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('create', \App\Models\MediaAsset::class) ?? false;
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $maxMb = app(\App\Support\SettingStore::class)->getInt('media.max_upload_mb', 512);
        $type = $this->mediaType();

        $extensions = config('media.'.$type->value.'.extensions', []);

        return [
            'file' => [
                'required',
                File::types($extensions)->max($maxMb * 1024),
            ],
            'name' => ['nullable', 'string', 'max:255'],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'file.required' => 'Seleccione un archivo para subir.',
            'file.max' => 'El archivo supera el tamaño máximo permitido.',
        ];
    }

    public function mediaType(): MediaAssetType
    {
        return $this->route('type') === 'videos'
            ? MediaAssetType::Video
            : MediaAssetType::Image;
    }
}
