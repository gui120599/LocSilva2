<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatusOrdemServico: string implements HasColor, HasIcon, HasLabel
{
    case Aberta           = 'aberta';
    case EmAndamento      = 'em_andamento';
    case AguardandoPecas  = 'aguardando_pecas';
    case Concluida        = 'concluida';
    case Cancelada        = 'cancelada';

    public function getLabel(): string
    {
        return match ($this) {
            self::Aberta          => 'Aberta',
            self::EmAndamento     => 'Em Andamento',
            self::AguardandoPecas => 'Aguardando Peças',
            self::Concluida       => 'Concluída',
            self::Cancelada       => 'Cancelada',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Aberta          => 'info',
            self::EmAndamento     => 'warning',
            self::AguardandoPecas => 'warning',
            self::Concluida       => 'success',
            self::Cancelada       => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Aberta          => 'heroicon-m-folder-open',
            self::EmAndamento     => 'heroicon-m-wrench-screwdriver',
            self::AguardandoPecas => 'heroicon-m-cube',
            self::Concluida       => 'heroicon-m-check-badge',
            self::Cancelada       => 'heroicon-m-no-symbol',
        };
    }
}
