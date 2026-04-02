<?php

namespace Init\Commerce\Cart\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum CartStatus: string implements HasColor, HasIcon, HasLabel
{
    case ACTIVE = 'active';
    case MERGED = 'merged';
    case CONVERTED = 'converted';
    case ABANDONED = 'abandoned';

    public function getLabel(): string
    {
        return match ($this) {
            self::ACTIVE => 'Активна',
            self::MERGED => 'Слита',
            self::CONVERTED => 'Конвертирована',
            self::ABANDONED => 'Брошена',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::ACTIVE => 'success',
            self::MERGED => 'info',
            self::CONVERTED => 'warning',
            self::ABANDONED => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::ACTIVE => 'heroicon-m-shopping-cart',
            self::MERGED => 'heroicon-m-arrows-right-left',
            self::CONVERTED => 'heroicon-m-check-badge',
            self::ABANDONED => 'heroicon-m-archive-box',
        };
    }

    public static function options(): array
    {
        $options = [];

        foreach (self::cases() as $case) {
            $options[$case->value] = $case->getLabel();
        }

        return $options;
    }
}
