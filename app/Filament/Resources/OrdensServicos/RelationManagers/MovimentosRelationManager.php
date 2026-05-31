<?php

namespace App\Filament\Resources\OrdensServicos\RelationManagers;

use App\Models\MetodoPagamento;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Leandrocfe\FilamentPtbrFormFields\Money;

class MovimentosRelationManager extends RelationManager
{
    protected static string $relationship = 'movimentos';

    protected static ?string $title = 'Recebimentos';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(4)
                    ->schema([
                        Hidden::make('tipo')
                            ->default('entrada'),

                        Hidden::make('ordem_servico_id')
                            ->default(fn() => $this->getOwnerRecord()->id),

                        Hidden::make('user_id')
                            ->default(fn() => filament()->auth()->id()),

                        ToggleButtons::make('metodo_pagamento_id')
                            ->label('Forma de Pagamento')
                            ->columnSpanFull()
                            ->options(fn() => MetodoPagamento::pluck('nome', 'id'))
                            ->icons([
                                1 => Heroicon::Banknotes,
                                2 => Heroicon::CreditCard,
                                3 => Heroicon::CreditCard,
                                4 => Heroicon::QrCode,
                            ])
                            ->default(1)
                            ->live()
                            ->grouped()
                            ->required(),

                        Select::make('cartao_pagamento_id')
                            ->label('Bandeira do Cartão')
                            ->relationship('bandeiraCartao', 'bandeira')
                            ->searchable()
                            ->columnSpan(2)
                            ->visible(fn(Get $get) => in_array($get('metodo_pagamento_id'), [2, 3]))
                            ->required(fn(Get $get) => in_array($get('metodo_pagamento_id'), [2, 3])),

                        TextInput::make('autorizacao')
                            ->label('Nº Autorização')
                            ->columnSpan(2)
                            ->visible(fn(Get $get) => in_array($get('metodo_pagamento_id'), [2, 3, 4])),

                        Money::make('valor_pago_movimento')
                            ->label('Valor Pago')
                            ->required()
                            ->live()
                            ->columnSpan(2)
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $valorPago = self::normalizeMoney($state ?? '0');
                                $metodo = MetodoPagamento::find($get('metodo_pagamento_id'));

                                if ($metodo && $metodo->taxa_tipo !== 'N/A' && $metodo->taxa_percentual > 0) {
                                    $taxa = ($valorPago * $metodo->taxa_percentual) / 100;

                                    if ($metodo->taxa_tipo === 'ACRESCENTAR') {
                                        $set('valor_acrescimo_movimento', number_format($taxa, 2, ',', '.'));
                                        $set('valor_desconto_movimento', '0,00');
                                    } else {
                                        $set('valor_desconto_movimento', number_format($taxa, 2, ',', '.'));
                                        $set('valor_acrescimo_movimento', '0,00');
                                    }
                                } else {
                                    $set('valor_acrescimo_movimento', '0,00');
                                    $set('valor_desconto_movimento', '0,00');
                                }

                                self::calcularTotalMovimento($set, $get);
                            }),

                        Money::make('valor_recebido_movimento')
                            ->label('Valor Recebido')
                            ->live()
                            ->columnSpan(2)
                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                $recebido = self::normalizeMoney($state ?? '0');
                                $pago = self::normalizeMoney($get('valor_pago_movimento'));
                                $troco = $recebido > $pago ? $recebido - $pago : 0;
                                $set('troco_movimento', number_format($troco, 2, ',', '.'));
                            }),

                        Money::make('troco_movimento')
                            ->label('Troco')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),

                        Money::make('valor_total_movimento')
                            ->label('Total do Pagamento')
                            ->disabled()
                            ->dehydrated()
                            ->columnSpan(2),

                        TextInput::make('descricao')
                            ->label('Descrição')
                            ->columnSpanFull()
                            ->placeholder('Ex: Entrada, pagamento parcial...'),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('descricao')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('user.name')
                    ->label('Responsável')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('metodoPagamento.nome')
                    ->label('Forma de Pagamento')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('valor_pago_movimento')
                    ->label('Valor Pago')
                    ->money('BRL')
                    ->sortable(),

                TextColumn::make('troco_movimento')
                    ->label('Troco')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('valor_total_movimento')
                    ->label('Total')
                    ->money('BRL')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('descricao')
                    ->label('Descrição')
                    ->limit(40)
                    ->toggleable(),

                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                TrashedFilter::make(),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar Recebimento')
                    ->after(function () {
                        $os = $this->getOwnerRecord();
                        $totalPago = $os->movimentos()->where('tipo', 'entrada')->sum('valor_total_movimento');
                        $os->update([
                            'valor_pago'  => $totalPago,
                            'valor_saldo' => max(0, $os->valor_total - $totalPago),
                        ]);
                    }),
            ])
            ->recordActions([
                EditAction::make(),
                DeleteAction::make()
                    ->after(function () {
                        $os = $this->getOwnerRecord();
                        $totalPago = $os->movimentos()->where('tipo', 'entrada')->sum('valor_total_movimento');
                        $os->update([
                            'valor_pago'  => $totalPago,
                            'valor_saldo' => max(0, $os->valor_total - $totalPago),
                        ]);
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    protected static function calcularTotalMovimento(Set $set, Get $get): void
    {
        $pago     = self::normalizeMoney($get('valor_pago_movimento'));
        $acrescimo = self::normalizeMoney($get('valor_acrescimo_movimento'));
        $desconto  = self::normalizeMoney($get('valor_desconto_movimento'));

        $total = $pago + $acrescimo - $desconto;
        $set('valor_total_movimento', number_format($total, 2, ',', '.'));
    }

    protected static function normalizeMoney(mixed $value): float
    {
        if (is_null($value) || $value === '') return 0;
        $value = str_replace('.', '', (string) $value);
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }
}
