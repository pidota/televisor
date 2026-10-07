<?php

namespace App\Services;

use App\Enums\MediaAssetStatus;
use App\Enums\MediaAssetType;
use App\Models\MediaAsset;
use App\Models\User;
use App\Support\MediaFileValidator;
use App\Support\MediaMetadataExtractor;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use RuntimeException;

class MediaLibraryService
{
    public function __construct(
        private readonly MediaFileValidator $validator,
        private readonly MediaMetadataExtractor $metadata,
        private readonly AuditLogger $audit,
    ) {}

    public function store(UploadedFile $file, MediaAssetType $type, User $user, ?string $displayName = null): MediaAsset
    {
        $this->validator->validate($file, $type);

        $uuid = (string) Str::uuid();
        $disk = config('media.disk', 'local');
        $extension = strtolower($file->getClientOriginalExtension());
        $storedName = 'original.'.$extension;
        $directory = trim(config('media.path_prefix', 'media').'/'.$uuid, '/');
        $relativePath = $directory.'/'.$storedName;

        $detectedMime = $this->detectMime($file);

        return DB::transaction(function () use ($file, $type, $user, $displayName, $uuid, $disk, $extension, $directory, $storedName, $relativePath, $detectedMime) {
            $asset = MediaAsset::query()->create([
                'uuid' => $uuid,
                'name' => $displayName ?: pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME),
                'type' => $type,
                'disk' => $disk,
                'path' => $relativePath,
                'original_filename' => $file->getClientOriginalName(),
                'mime_type' => $detectedMime,
                'extension' => $extension,
                'size_bytes' => $file->getSize(),
                'status' => MediaAssetStatus::Processing,
                'uploaded_by' => $user->id,
            ]);

            Storage::disk($disk)->putFileAs($directory, $file, $storedName);

            $absolutePath = Storage::disk($disk)->path($relativePath);
            $checksum = hash_file('sha256', $absolutePath);
            $meta = $this->metadata->extract($absolutePath, $type);

            $asset->update([
                'checksum_sha256' => $checksum,
                'width' => $meta['width'],
                'height' => $meta['height'],
                'duration_seconds' => $meta['duration_seconds'],
                'status' => MediaAssetStatus::Ready,
            ]);

            $this->audit->log($user, 'media.uploaded', $asset, [
                'type' => $type->value,
                'original_filename' => $asset->original_filename,
            ]);

            return $asset->fresh();
        });
    }

    /**
     * @throws ValidationException
     */
    public function delete(MediaAsset $asset, User $user): void
    {
        if ($asset->playlistItems()->exists()) {
            throw ValidationException::withMessages([
                'media' => 'No se puede eliminar: el contenido está en una o más playlists.',
            ]);
        }

        DB::transaction(function () use ($asset, $user) {
            $disk = $asset->disk;
            $path = $asset->path;

            $this->audit->log($user, 'media.deleted', $asset, [
                'name' => $asset->name,
            ]);

            $asset->delete();

            if ($path && Storage::disk($disk)->exists($path)) {
                Storage::disk($disk)->delete($path);
                $directory = dirname($path);
                if ($directory !== '.' && $directory !== '') {
                    Storage::disk($disk)->deleteDirectory($directory);
                }
            }
        });
    }

    /**
     * @param  array{name?: string, valid_from?: ?string, valid_until?: ?string}  $data
     */
    public function updateMetadata(MediaAsset $asset, array $data, User $user): MediaAsset
    {
        $asset->fill([
            'name' => $data['name'] ?? $asset->name,
            'valid_from' => $data['valid_from'] ?? $asset->valid_from,
            'valid_until' => $data['valid_until'] ?? $asset->valid_until,
        ]);
        $asset->save();

        $this->audit->log($user, 'media.updated', $asset, $data);

        return $asset;
    }

    private function detectMime(UploadedFile $file): string
    {
        $path = $file->getRealPath();
        if ($path === false) {
            return $file->getMimeType() ?: 'application/octet-stream';
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = $finfo ? finfo_file($finfo, $path) : false;
        if ($finfo) {
            finfo_close($finfo);
        }

        return is_string($mime) && $mime !== '' ? $mime : ($file->getMimeType() ?: 'application/octet-stream');
    }

    public function absolutePath(MediaAsset $asset): string
    {
        if (! Storage::disk($asset->disk)->exists($asset->path)) {
            throw new RuntimeException('Archivo no encontrado en almacenamiento.');
        }

        return Storage::disk($asset->disk)->path($asset->path);
    }
}
