<?php

namespace App\Support;

use App\Enums\MediaAssetType;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use Symfony\Component\Mime\MimeTypes;

class MediaFileValidator
{
    public function __construct(
        private readonly SettingStore $settings,
    ) {}

    /**
     * @throws ValidationException
     */
    public function validate(UploadedFile $file, MediaAssetType $type): void
    {
        if (! $file->isValid()) {
            throw ValidationException::withMessages([
                'file' => 'La subida del archivo falló. Verifique el tamaño máximo permitido por PHP.',
            ]);
        }

        $maxMb = $this->settings->getInt('media.max_upload_mb', 512);
        $maxBytes = $maxMb * 1024 * 1024;

        if ($file->getSize() > $maxBytes) {
            throw ValidationException::withMessages([
                'file' => "El archivo supera el límite de {$maxMb} MB.",
            ]);
        }

        $extension = strtolower($file->getClientOriginalExtension());
        $detectedMime = $this->detectMimeType($file);

        $allowedExtensions = config('media.'.$type->value.'.extensions', []);
        $allowedMimes = config('media.'.$type->value.'.mime_types', []);

        if (! in_array($extension, $allowedExtensions, true)) {
            throw ValidationException::withMessages([
                'file' => 'La extensión del archivo no está permitida.',
            ]);
        }

        if (! in_array($detectedMime, $allowedMimes, true)) {
            throw ValidationException::withMessages([
                'file' => 'El tipo MIME detectado no coincide con un archivo permitido.',
            ]);
        }

        if ($type === MediaAssetType::Image) {
            $this->assertValidImage($file);
        }

        if ($type === MediaAssetType::Video) {
            $this->assertValidMp4($file);
        }
    }

    private function detectMimeType(UploadedFile $file): string
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

        if (is_string($mime) && $mime !== '') {
            return $mime;
        }

        return $file->getMimeType() ?: 'application/octet-stream';
    }

    /**
     * @throws ValidationException
     */
    private function assertValidImage(UploadedFile $file): void
    {
        $info = @getimagesize($file->getRealPath() ?: '');

        if ($info === false) {
            throw ValidationException::withMessages([
                'file' => 'El archivo no es una imagen válida.',
            ]);
        }
    }

    /**
     * @throws ValidationException
     */
    private function assertValidMp4(UploadedFile $file): void
    {
        $handle = fopen($file->getRealPath() ?: '', 'rb');
        if ($handle === false) {
            throw ValidationException::withMessages([
                'file' => 'No se pudo leer el archivo de video.',
            ]);
        }

        $header = fread($handle, 12);
        fclose($handle);

        if ($header === false || strlen($header) < 8) {
            throw ValidationException::withMessages([
                'file' => 'El archivo de video no es válido.',
            ]);
        }

        if (strpos($header, 'ftyp') === false) {
            throw ValidationException::withMessages([
                'file' => 'El video debe ser un MP4 válido.',
            ]);
        }
    }

    public function extensionMatchesMime(string $extension, string $mime): bool
    {
        $map = (new MimeTypes)->getMimeTypes($extension);

        return in_array($mime, $map, true);
    }
}
