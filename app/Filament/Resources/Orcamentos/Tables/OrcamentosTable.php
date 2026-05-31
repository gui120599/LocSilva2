<?php

namespace App\Filament\Resources\Orcamentos\Tables;

use App\Enums\StatusOrcamento;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OrcamentosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero')
                    ->label('Nº')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->sortable(),

                TextColumn::make('cliente.nome')
                    ->label('Cliente')
                    ->default(fn($record) => $record->nome_cliente ?? '—')
                    ->searchable()
                    ->sortable(),

                TextColumn::make('veiculo_descricao')
                    ->label('Veículo')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('veiculo_placa')
                    ->label('Placa')
                    ->searchable()
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('valor_total')
                    ->label('Total')
                    ->money('BRL')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('data_validade')
                    ->label('Válido até')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(),

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
            ->defaultSort('id', 'desc')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusOrcamento::class)
                    ->multiple(),
            ])
            ->recordActions([
                Action::make('aprovar')
                    ->label('Aprovar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(Orcamento $record) => $record->status === StatusOrcamento::AguardandoAprovacao)
                    ->requiresConfirmation()
                    ->action(fn(Orcamento $record) => $record->aprovar()),

                Action::make('converter_os')
                    ->label('Converter em OS')
                    ->icon('heroicon-o-arrow-right-circle')
                    ->color('info')
                    ->visible(fn(Orcamento $record) => $record->podeConverterEmOS())
                    ->requiresConfirmation()
                    ->modalHeading('Converter Orçamento em Ordem de Serviço')
                    ->modalDescription('Uma nova Ordem de Serviço será criada com os itens deste orçamento.')
                    ->action(function (Orcamento $record) {
                        $numero = 'OS-' . now()->format('Y') . '-' . str_pad(
                            OrdemServico::withTrashed()->count() + 1,
                            4,
                            '0',
                            STR_PAD_LEFT
                        );

                        $os = OrdemServico::create([
                            'numero'            => $numero,
                            'orcamento_id'      => $record->id,
                            'cliente_id'        => $record->cliente_id,
                            'nome_cliente'      => $record->nome_cliente,
                            'telefone_cliente'  => $record->telefone_cliente,
                            'veiculo_descricao' => $record->veiculo_descricao,
                            'veiculo_placa'     => $record->veiculo_placa,
                            'status'            => \App\Enums\StatusOrdemServico::Aberta->value,
                            'data_abertura'     => now(),
                            'valor_subtotal'    => $record->valor_subtotal,
                            'valor_desconto'    => $record->valor_desconto,
                            'valor_acrescimo'   => $record->valor_acrescimo,
                            'valor_total'       => $record->valor_total,
                            'valor_pago'        => 0,
                            'valor_saldo'       => $record->valor_total,
                            'user_id'           => filament()->auth()->id(),
                        ]);

                        foreach ($record->itens as $item) {
                            $os->itens()->create([
                                'tipo'           => $item->tipo,
                                'servico_id'     => $item->servico_id,
                                'produto_id'     => $item->produto_id,
                                'descricao'      => $item->descricao,
                                'quantidade'     => $item->quantidade,
                                'valor_unitario' => $item->valor_unitario,
                                'valor_total'    => $item->valor_total,
                            ]);
                        }

                        $record->marcarComoConvertido();

                        Notification::make()
                            ->success()
                            ->title("OS {$numero} criada com sucesso!")
                            ->send();
                    }),

                Action::make('reprovar')
                    ->label('Reprovar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(Orcamento $record) => $record->status === StatusOrcamento::AguardandoAprovacao)
                    ->requiresConfirmation()
                    ->action(fn(Orcamento $record) => $record->reprovar()),

                Action::make('imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn(Orcamento $record): string => route('print-orcamento', ['id' => $record->id]))
                    ->openUrlInNewTab(),

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
