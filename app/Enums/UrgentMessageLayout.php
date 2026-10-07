<?php

namespace App\Enums;

enum UrgentMessageLayout: string
{
    case TextFullscreen = 'text_fullscreen';

    case TextTicker = 'text_ticker';

    public function overridesPlaylist(): bool
    {
        return $this === self::TextFullscreen;
    }

    public function label(): string
    {
        return match ($this) {
            self::TextFullscreen => 'Pantalla completa',
            self::TextTicker => 'Cinta inferior (sobre playlist)',
        };
    }
}
