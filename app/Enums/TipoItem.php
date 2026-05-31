<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum TipoItem: string implements HasColor, HasIcon, HasLabel
{
    case Servico = 'servico';
    case Produto = 'produto';
    case Outros  = 'outros';

    public function getLabel(): string
    {
        return match ($this) {
            self::Servico => 'Serviço',
            self::Produto => 'Produto',
            self::Outros  => 'Outros',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Servico => 'info',
            self::Produto => 'success',
            self::Outros  => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Servico => 'heroicon-m-wrench-screwdriver',
            self::Produto => 'heroicon-m-cube',
            self::Outros  => 'heroicon-m-ellipsis-horizontal-circle',
        };
    }
}
