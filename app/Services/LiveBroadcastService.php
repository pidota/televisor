<?php

namespace App\Services;

use App\Enums\ScreenStatus;
use App\Models\Screen;
use App\Models\Setting;

class LiveBroadcastService
{
    public function __construct(
        private readonly ScreenManifestService $manifests,
    ) {}

    public function isEnabled(): bool
    {
        return $this->get('live.enabled') === '1';
    }

    public function appliesToAllScreens(): bool
    {
        return $this->get('live.all_screens') === '1';
    }

    /**
     * @return list<int>
     */
    public function selectedScreenIds(): array
    {
        $decoded = json_decode($this->get('live.screen_ids') ?? '[]', true);

        if (! is_array($decoded)) {
            return [];
        }

        return array_values(array_map('intval', $decoded));
    }

    public function appliesTo(Screen $screen): bool
    {
        if (! $this->isEnabled() || $this->hlsUrl() === '') {
            return false;
        }

        if ($screen->status !== ScreenStatus::Active) {
            return false;
        }

        if ($this->appliesToAllScreens()) {
            return true;
        }

        return in_array($screen->id, $this->selectedScreenIds(), true);
    }

    public function hlsUrl(): string
    {
        return rtrim((string) config('live.hls_url'), '/');
    }

    /**
     * @param  list<int>  $screenIds
     */
    public function save(bool $enabled, bool $allScreens, array $screenIds): void
    {
        $previous = $this->affectedScreenIds();

        $this->put('live.enabled', $enabled ? '1' : '0');
        $this->put('live.all_screens', $allScreens ? '1' : '0');
        $this->put('live.screen_ids', json_encode(array_values(array_unique(array_map('intval', $screenIds)))) ?: '[]');

        $next = $this->affectedScreenIds();

        $this->manifests->bumpForScreenIds(array_values(array_unique([...$previous, ...$next])));
    }

    /**
     * @return list<int>
     */
    private function affectedScreenIds(): array
    {
        if (! $this->isEnabled()) {
            return [];
        }

        if ($this->appliesToAllScreens()) {
            return Screen::query()
                ->where('status', ScreenStatus::Active)
                ->pluck('id')
                ->map(fn ($id) => (int) $id)
                ->all();
        }

        return $this->selectedScreenIds();
    }

    private function get(string $key): ?string
    {
        $value = Setting::query()->where('key', $key)->value('value');

        return $value === null ? null : (string) $value;
    }

    private function put(string $key, string $value): void
    {
        Setting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
    }
}
