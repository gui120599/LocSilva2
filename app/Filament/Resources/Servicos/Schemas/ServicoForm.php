<?php

namespace App\Filament\Resources\Servicos\Schemas;

use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Leandrocfe\FilamentPtbrFormFields\Money;

class ServicoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make()
                    ->description('Dados do Serviço')
                    ->icon('heroicon-s-wrench-screwdriver')
                    ->columns(3)
                    ->schema([
                        FileUpload::make('foto')
                            ->label('Foto do Serviço')
                            ->image()
                            ->imageEditor()
                            ->disk('public')
                            ->directory('servicos')
                            ->columnSpanFull()
                            ->nullable(),

                        TextInput::make('nome')
                            ->required()
                            ->columnSpan(2)
                            ->maxLength(255),

                        Select::make('unidade')
                            ->label('Unidade')
                            ->columnSpan(1)
                            ->options([
                                'serviço' => 'Por Serviço',
                                'hora'    => 'Por Hora',
                                'diária'  => 'Por Diária',
                                'metro'   => 'Por Metro',
                                'outros'  => 'Outros',
                            ])
                            ->default('serviço')
                            ->required(),

                        TextInput::make('descricao')
                            ->label('Descrição')
                            ->columnSpanFull()
                            ->maxLength(500),

                        Money::make('valor_padrao')
                            ->label('Valor Padrão')
                            ->columnSpan(1)
                            ->required(),
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
