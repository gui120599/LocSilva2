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
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Leandrocfe\FilamentPtbrFormFields\Document;

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

                Filter::make('numero')
                    ->label('Número')
                    ->schema([
                        TextInput::make('numero')
                            ->label('Nº do Orçamento')
                            ->placeholder('Ex: ORC-2024-0001'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder =>
                        $query->when($data['numero'] ?? null, fn($q, $v) => $q->where('numero', 'like', "%{$v}%"))
                    ),

                Filter::make('data_validade')
                    ->label('Data de Validade')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('data_validade_de')->label('Válido até (De)'),
                        DatePicker::make('data_validade_ate')->label('Válido até (Até)'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder =>
                        $query
                            ->when($data['data_validade_de'] ?? null, fn($q, $d) => $q->whereDate('data_validade', '>=', $d))
                            ->when($data['data_validade_ate'] ?? null, fn($q, $d) => $q->whereDate('data_validade', '<=', $d))
                    ),

                Filter::make('data_criacao')
                    ->label('Data de Criação')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('data_criacao_de')->label('Criado em (De)'),
                        DatePicker::make('data_criacao_ate')->label('Criado em (Até)'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder =>
                        $query
                            ->when($data['data_criacao_de'] ?? null, fn($q, $d) => $q->whereDate('created_at', '>=', $d))
                            ->when($data['data_criacao_ate'] ?? null, fn($q, $d) => $q->whereDate('created_at', '<=', $d))
                    ),

                Filter::make('cliente')
                    ->label('Cliente')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->components([
                                TextInput::make('cliente_nome')
                                    ->label('Nome do Cliente')
                                    ->placeholder('Digite o nome do cliente'),

                                Document::make('cliente_cpf_cnpj')
                                    ->label('CPF/CNPJ')
                                    ->dynamic()
                                    ->placeholder('Digite o CPF ou CNPJ'),
                            ]),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['cliente_nome'] ?? null, function ($q, $v) {
                                $q->where(function ($q) use ($v) {
                                    $q->whereHas('cliente', fn($q) => $q->where('nome', 'like', "%{$v}%"))
                                      ->orWhere('nome_cliente', 'like', "%{$v}%");
                                });
                            })
                            ->when(preg_replace('/\D/', '', $data['cliente_cpf_cnpj'] ?? '') ?: null, function ($q, $v) {
                                $q->whereHas('cliente', fn($q) => $q->where('cpf_cnpj', 'like', "%{$v}%"));
                            });
                    }),

                Filter::make('veiculo')
                    ->label('Veículo')
                    ->schema([
                        Grid::make()
                            ->columns(2)
                            ->components([
                                TextInput::make('veiculo_descricao')
                                    ->label('Descrição do Veículo')
                                    ->placeholder('Ex: Carreta, Caminhão...'),

                                TextInput::make('veiculo_placa')
                                    ->label('Placa')
                                    ->placeholder('ABC-1234'),
                            ]),
                    ])
                    ->query(fn(Builder $query, array $data): Builder =>
                        $query
                            ->when($data['veiculo_descricao'] ?? null, fn($q, $v) => $q->where('veiculo_descricao', 'like', "%{$v}%"))
                            ->when($data['veiculo_placa'] ?? null, fn($q, $v) => $q->where('veiculo_placa', 'like', "%{$v}%"))
                    ),

            ], layout: FiltersLayout::AboveContentCollapsible)
            ->filtersFormColumns(4)
            ->filtersFormSchema(fn(array $filters): array => [
                Section::make('Orçamento')
                    ->collapsed()
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        $filters['trashed']       ?? null,
                        $filters['numero']        ?? null,
                        $filters['status']        ?? null,
                        $filters['data_validade'] ?? null,
                        $filters['data_criacao']  ?? null,
                    ]),

                Section::make('Cliente')
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        $filters['cliente'] ?? null,
                    ]),

                Section::make('Veículo')
                    ->collapsed()
                    ->columnSpanFull()
                    ->schema([
                        $filters['veiculo'] ?? null,
                    ]),
            ])
            ->deferFilters(false)
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
