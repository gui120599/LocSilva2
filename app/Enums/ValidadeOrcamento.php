<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum ValidadeOrcamento: int implements HasColor, HasIcon, HasLabel
{
    case SeteDias     = 7;
    case QuinzeDias   = 15;
    case TrintaDias   = 30;
    case SessentaDias = 60;
    case NoventaDias  = 90;

    public function getLabel(): string
    {
        return match ($this) {
            self::SeteDias     => '7 dias',
            self::QuinzeDias   => '15 dias',
            self::TrintaDias   => '30 dias',
            self::SessentaDias => '60 dias',
            self::NoventaDias  => '90 dias',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::SeteDias     => 'success',
            self::QuinzeDias   => 'info',
            self::TrintaDias   => 'primary',
            self::SessentaDias => 'warning',
            self::NoventaDias  => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::SeteDias     => 'heroicon-o-calendar',
            self::QuinzeDias   => 'heroicon-o-calendar',
            self::TrintaDias   => 'heroicon-o-calendar-days',
            self::SessentaDias => 'heroicon-o-calendar-days',
            self::NoventaDias  => 'heroicon-o-clock',
        };
    }
}
