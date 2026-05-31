<?php

namespace App\Filament\Resources\Produtos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Leandrocfe\FilamentPtbrFormFields\Money;

class ProdutoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->description('Dados do Produto')
                    ->icon('heroicon-s-cube')
                    ->columns(4)
                    ->schema([
                        FileUpload::make('foto')
                            ->label('Foto do Produto')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('produtos')
                            ->columnSpanFull()
                            ->nullable(),

                        TextInput::make('nome')
                            ->required()
                            ->columnSpan(3)
                            ->maxLength(255),

                        Select::make('unidade')
                            ->label('Unidade')
                            ->columnSpan(1)
                            ->options([
                                'un'  => 'Unidade (un)',
                                'm'   => 'Metro (m)',
                                'kg'  => 'Quilograma (kg)',
                                'l'   => 'Litro (l)',
                                'm²'  => 'Metro Quadrado (m²)',
                                'pç'  => 'Peça (pç)',
                                'cx'  => 'Caixa (cx)',
                                'outros' => 'Outros',
                            ])
                            ->default('un')
                            ->required(),

                        TextInput::make('descricao')
                            ->label('Descrição')
                            ->columnSpanFull()
                            ->maxLength(500),

                        Money::make('valor_unitario')
                            ->label('Valor Unitário')
                            ->columnSpan(1)
                            ->required(),

                        TextInput::make('estoque_minimo')
                            ->label('Estoque Mínimo')
                            ->numeric()
                            ->columnSpan(1)
                            ->helperText('Alerta quando o estoque atingir esse valor'),

                        TextInput::make('estoque_atual')
                            ->label('Estoque Atual')
                            ->numeric()
                            ->columnSpan(1)
                            ->readOnly()
                            ->helperText('Calculado pelas movimentações')
                            ->visible(fn(string $operation): bool => $operation === 'edit'),
                    ]),

                Section::make()
                    ->description('Observações')
                    ->icon('heroicon-s-chat-bubble-bottom-center-text')
                    ->collapsed()
                    ->schema([
                        Textarea::make('observacoes')
                            ->columnSpanFull()
                            ->rows(3),
                    ]),
            ]);
    }
}
