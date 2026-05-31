<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum MotivoMovimentoEstoque: string implements HasColor, HasIcon, HasLabel
{
    case Compra   = 'compra';
    case UsoOS    = 'uso_os';
    case Venda    = 'venda';
    case Dano     = 'dano';
    case Descarte = 'descarte';
    case Ajuste   = 'ajuste';
    case Outros   = 'outros';

    public function getLabel(): string
    {
        return match ($this) {
            self::Compra   => 'Compra',
            self::UsoOS    => 'Uso em OS',
            self::Venda    => 'Venda',
            self::Dano     => 'Dano',
            self::Descarte => 'Descarte',
            self::Ajuste   => 'Ajuste de Estoque',
            self::Outros   => 'Outros',
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::Compra   => 'success',
            self::UsoOS    => 'info',
            self::Venda    => 'info',
            self::Dano     => 'danger',
            self::Descarte => 'danger',
            self::Ajuste   => 'warning',
            self::Outros   => 'gray',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::Compra   => 'heroicon-m-shopping-cart',
            self::UsoOS    => 'heroicon-m-wrench-screwdriver',
            self::Venda    => 'heroicon-m-banknotes',
            self::Dano     => 'heroicon-m-exclamation-triangle',
            self::Descarte => 'heroicon-m-trash',
            self::Ajuste   => 'heroicon-m-adjustments-horizontal',
            self::Outros   => 'heroicon-m-ellipsis-horizontal-circle',
        };
    }

    /**
     * Retorna os motivos válidos para entradas de estoque
     */
    public static function motivosEntrada(): array
    {
        return [self::Compra, self::Ajuste, self::Outros];
    }

    /**
     * Retorna os motivos válidos para saídas de estoque
     */
    public static function motivosSaida(): array
    {
        return [self::UsoOS, self::Venda, self::Dano, self::Descarte, self::Ajuste, self::Outros];
    }
}
