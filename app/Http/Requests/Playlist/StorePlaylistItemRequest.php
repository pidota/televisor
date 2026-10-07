<?php

namespace App\Http\Requests\Playlist;

use Illuminate\Foundation\Http\FormRequest;

class StorePlaylistItemRequest extends FormRequest
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
            'media_asset_id' => ['required', 'integer', 'exists:media_assets,id'],
            'duration_seconds' => ['nullable', 'integer', 'min:1', 'max:3600'],
        ];
    }
}
