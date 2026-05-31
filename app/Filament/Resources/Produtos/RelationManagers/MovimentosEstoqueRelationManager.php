<?php

namespace App\Filament\Resources\Produtos\RelationManagers;

use App\Enums\MotivoMovimentoEstoque;
use App\Enums\TipoMovimentoEstoque;
use App\Models\Produto;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Leandrocfe\FilamentPtbrFormFields\Money;

class MovimentosEstoqueRelationManager extends RelationManager
{
    protected static string $relationship = 'movimentosEstoque';

    protected static ?string $title = 'Movimentações de Estoque';

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make()
                    ->columns(4)
                    ->schema([
                        ToggleButtons::make('tipo')
                            ->label('Tipo')
                            ->options(TipoMovimentoEstoque::class)
                            ->required()
                            ->live()
                            ->grouped()
                            ->columnSpanFull()
                            ->afterStateUpdated(function (Set $set, $state) {
                                $set('motivo', null);
                            }),

                        Select::make('motivo')
                            ->label('Motivo')
                            ->columnSpanFull()
                            ->options(function (Get $get) {
                                $tipo = $get('tipo');

                                if ($tipo === TipoMovimentoEstoque::Entrada->value) {
                                    return collect(MotivoMovimentoEstoque::motivosEntrada())
                                        ->mapWithKeys(fn($m) => [$m->value => $m->getLabel()])
                                        ->toArray();
                                }

                                return collect(MotivoMovimentoEstoque::motivosSaida())
                                    ->mapWithKeys(fn($m) => [$m->value => $m->getLabel()])
                                    ->toArray();
                            })
                            ->required(),

                        TextInput::make('quantidade')
                            ->label('Quantidade')
                            ->numeric()
                            ->minValue(0.001)
                            ->step(0.001)
                            ->required()
                            ->columnSpan(2),

                        Money::make('valor_unitario')
                            ->label('Valor Unitário')
                            ->columnSpan(2),

                        Hidden::make('user_id')
                            ->default(fn() => filament()->auth()->id()),

                        Textarea::make('observacoes')
                            ->columnSpanFull()
                            ->rows(2),
                    ]),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('motivo')
            ->defaultSort('created_at', 'desc')
            ->columns([
                TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge()
                    ->sortable(),

                TextColumn::make('motivo')
                    ->label('Motivo')
                    ->badge()
                    ->sortable(),

                TextColumn::make('quantidade')
                    ->label('Quantidade')
                    ->numeric(decimalPlaces: 3)
                    ->sortable(),

                TextColumn::make('valor_unitario')
                    ->label('Valor Unitário')
                    ->money('BRL')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('ordemServico.numero')
                    ->label('OS')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('user.name')
                    ->label('Responsável')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('observacoes')
                    ->label('Observações')
                    ->limit(40)
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Data')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('tipo')
                    ->label('Tipo')
                    ->options(TipoMovimentoEstoque::class),
                SelectFilter::make('motivo')
                    ->label('Motivo')
                    ->options(MotivoMovimentoEstoque::class),
            ])
            ->headerActions([
                CreateAction::make()
                    ->label('Registrar Movimentação')
                    ->after(function (array $data) {
                        $produto = $this->getOwnerRecord();
                        if ($produto instanceof Produto) {
                            $produto->recalcularEstoque();
                        }
                    }),
            ])
            ->recordActions([
                EditAction::make()
                    ->after(function () {
                        $produto = $this->getOwnerRecord();
                        if ($produto instanceof Produto) {
                            $produto->recalcularEstoque();
                        }
                    }),
                DeleteAction::make()
                    ->after(function () {
                        $produto = $this->getOwnerRecord();
                        if ($produto instanceof Produto) {
                            $produto->recalcularEstoque();
                        }
                    }),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
