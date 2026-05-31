<?php

namespace App\Filament\Resources\OrdensServicos\Tables;

use App\Enums\StatusOrcamento;
use App\Enums\StatusOrdemServico;
use App\Models\OrdemServico;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;

class OrdensServicosTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('numero')
                    ->label('Nº OS')
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
                    ->badge()
                    ->color('gray')
                    ->toggleable(),

                TextColumn::make('tecnico.name')
                    ->label('Técnico')
                    ->searchable()
                    ->toggleable(),

                TextColumn::make('valor_total')
                    ->label('Total')
                    ->money('BRL')
                    ->sortable()
                    ->badge()
                    ->color('success'),

                TextColumn::make('valor_saldo')
                    ->label('Saldo')
                    ->money('BRL')
                    ->sortable()
                    ->badge()
                    ->color(fn($record) => ($record?->valor_saldo ?? 0) > 0 ? 'danger' : 'success'),

                TextColumn::make('data_abertura')
                    ->label('Abertura')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('data_previsao_conclusao')
                    ->label('Previsão')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(),

                TextColumn::make('data_conclusao')
                    ->label('Concluída em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('orcamento.numero')
                    ->label('Orçamento')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),

                TextColumn::make('created_at')
                    ->label('Criado em')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('id', 'desc')
            ->filters([
                TrashedFilter::make(),
                SelectFilter::make('status')
                    ->label('Status')
                    ->options(StatusOrdemServico::class)
                    ->multiple(),
            ])
            ->recordActions([
                Action::make('iniciar')
                    ->label('Iniciar')
                    ->icon('heroicon-o-play')
                    ->color('warning')
                    ->visible(fn(OrdemServico $record) => $record->status === StatusOrdemServico::Aberta)
                    ->requiresConfirmation()
                    ->action(fn(OrdemServico $record) => $record->update(['status' => StatusOrdemServico::EmAndamento])),

                Action::make('concluir')
                    ->label('Concluir')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->visible(fn(OrdemServico $record) => in_array($record->status, [
                        StatusOrdemServico::EmAndamento,
                        StatusOrdemServico::AguardandoPecas,
                    ]))
                    ->requiresConfirmation()
                    ->modalHeading('Concluir Ordem de Serviço')
                    ->modalDescription('Confirma a conclusão dos serviços desta OS?')
                    ->action(fn(OrdemServico $record) => $record->concluir()),

                Action::make('cancelar')
                    ->label('Cancelar')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->visible(fn(OrdemServico $record) => in_array($record->status, [
                        StatusOrdemServico::Aberta,
                        StatusOrdemServico::EmAndamento,
                        StatusOrdemServico::AguardandoPecas,
                    ]))
                    ->modalHeading('Cancelar Ordem de Serviço')
                    ->schema(fn(OrdemServico $record) => array_filter([
                        Textarea::make('motivo')
                            ->label('Motivo do Cancelamento')
                            ->required()
                            ->rows(3),

                        $record->orcamento_id
                            ? Select::make('acao_orcamento')
                            ->label('O que fazer com o Orçamento vinculado?')
                            ->helperText("Orçamento {$record->orcamento?->numero} também está associado a esta OS.")
                            ->options([
                                'aprovado'  => 'Retornar orçamento em Aprovado',
                                'reprovar' => 'Reprovar o orçamento',
                                'cancelar' => 'Cancelar o orçamento',
                            ])
                            ->default('nenhuma')
                            ->required()
                            : null,
                    ]))
                    ->action(function (OrdemServico $record, array $data) {
                        $record->cancelar($data['motivo']);

                        $acao = $data['acao_orcamento'] ?? 'cancelar';

                        $orcamento = $record->orcamento;
                        if ($orcamento) {
                            match ($acao) {
                                'aprovado' => $orcamento->aprovado(),
                                'reprovar' => $orcamento->reprovar(),
                                'cancelar' => $orcamento->cancelar(),
                                default    => null,
                            };
                        }

                        Notification::make()->success()->title('OS cancelada.')->send();
                    }),

                Action::make('imprimir')
                    ->label('Imprimir')
                    ->icon('heroicon-o-printer')
                    ->color('gray')
                    ->url(fn(OrdemServico $record): string => route('print-os', ['id' => $record->id]))
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
