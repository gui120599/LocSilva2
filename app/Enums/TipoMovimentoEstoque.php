<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TipoMovimentoEstoque: string implements HasColor, HasIcon, HasLabel
{
    case Entrada = 'entrada';
    case Saida   = 'saida';

    public function getLabel(): string
    {
        return match ($this) {
            self::Entrada => 'Entrada',
            self::Saida   => 'Saída',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Entrada => 'success',
            self::Saida   => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Entrada => 'heroicon-m-arrow-down-tray',
            self::Saida   => 'heroicon-m-arrow-up-tray',
        };
    }
}
