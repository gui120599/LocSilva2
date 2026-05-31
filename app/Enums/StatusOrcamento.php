<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum StatusOrcamento: string implements HasColor, HasIcon, HasLabel
{
    case Rascunho           = 'rascunho';
    case AguardandoAprovacao = 'aguardando_aprovacao';
    case Aprovado           = 'aprovado';
    case Reprovado          = 'reprovado';
    case Cancelado          = 'cancelado';
    case Convertido         = 'convertido';

    public function getLabel(): string
    {
        return match ($this) {
            self::Rascunho            => 'Rascunho',
            self::AguardandoAprovacao => 'Aguardando Aprovação',
            self::Aprovado            => 'Aprovado',
            self::Reprovado           => 'Reprovado',
            self::Cancelado           => 'Cancelado',
            self::Convertido          => 'Convertido em OS',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Rascunho            => 'gray',
            self::AguardandoAprovacao => 'warning',
            self::Aprovado            => 'success',
            self::Reprovado           => 'danger',
            self::Cancelado           => 'danger',
            self::Convertido          => 'info',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Rascunho            => 'heroicon-m-pencil',
            self::AguardandoAprovacao => 'heroicon-m-clock',
            self::Aprovado            => 'heroicon-m-check-circle',
            self::Reprovado           => 'heroicon-m-x-circle',
            self::Cancelado           => 'heroicon-m-no-symbol',
            self::Convertido          => 'heroicon-m-arrow-right-circle',
        };
    }
}
