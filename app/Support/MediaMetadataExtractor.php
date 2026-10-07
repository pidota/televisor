<?php

namespace App\Support;

use App\Enums\MediaAssetType;
use Illuminate\Support\Facades\Process;

class MediaMetadataExtractor
{
    /**
     * @return array{width: ?int, height: ?int, duration_seconds: ?int}
     */
    public function extract(string $absolutePath, MediaAssetType $type): array
    {
        if ($type === MediaAssetType::Image) {
            return $this->extractImage($absolutePath);
        }

        return $this->extractVideo($absolutePath);
    }

    /**
     * @return array{width: ?int, height: ?int, duration_seconds: ?int}
     */
    private function extractImage(string $absolutePath): array
    {
        $info = @getimagesize($absolutePath);

        if ($info === false) {
            return ['width' => null, 'height' => null, 'duration_seconds' => null];
        }

        return [
            'width' => $info[0] ?? null,
            'height' => $info[1] ?? null,
            'duration_seconds' => null,
        ];
    }

    /**
     * @return array{width: ?int, height: ?int, duration_seconds: ?int}
     */
    private function extractVideo(string $absolutePath): array
    {
        $result = [
            'width' => null,
            'height' => null,
            'duration_seconds' => null,
        ];

        if (! $this->ffprobeAvailable()) {
            return $result;
        }

        $duration = Process::run([
            'ffprobe',
            '-v', 'error',
            '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1',
            $absolutePath,
        ]);

        if ($duration->successful()) {
            $seconds = (float) trim($duration->output());
            if ($seconds > 0) {
                $result['duration_seconds'] = (int) round($seconds);
            }
        }

        $dimensions = Process::run([
            'ffprobe',
            '-v', 'error',
            '-select_streams', 'v:0',
            '-show_entries', 'stream=width,height',
            '-of', 'csv=s=x:p=0',
            $absolutePath,
        ]);

        if ($dimensions->successful()) {
            $parts = explode('x', trim($dimensions->output()));
            if (count($parts) === 2) {
                $result['width'] = (int) $parts[0];
                $result['height'] = (int) $parts[1];
            }
        }

        return $result;
    }

    private function ffprobeAvailable(): bool
    {
        $check = Process::run(['ffprobe', '-version']);

        return $check->successful();
    }
}
