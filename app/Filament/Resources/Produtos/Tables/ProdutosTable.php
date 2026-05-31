<?php

namespace App\Filament\Resources\Produtos\Tables;

use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class ProdutosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('unidade')
                    ->label('Unidade')
                    ->badge()
                    ->color('gray')
                    ->sortable(),

                TextColumn::make('valor_unitario')
                    ->label('Valor Unitário')
                    ->money('BRL')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('estoque_atual')
                    ->label('Estoque Atual')
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->badge()
                    ->color(fn($record) => $record?->isEstoqueBaixo() ? 'danger' : 'info'),

                TextColumn::make('estoque_minimo')
                    ->label('Estoque Mínimo')
                    ->numeric(decimalPlaces: 3)
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('deleted_at')
                    ->label('Deletado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('nome')
            ->filters([
                TrashedFilter::make(),
            ])
            ->recordActions([
                EditAction::make(),
                RestoreAction::make(),
                DeleteAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->persistFiltersInSession()
            ->persistSortInSession()
            ->persistSearchInSession();
    }
}
