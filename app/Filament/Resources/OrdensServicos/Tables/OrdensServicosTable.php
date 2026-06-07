<?php

namespace App\Filament\Resources\OrdensServicos\Tables;

use App\Enums\StatusOrcamento;
use App\Enums\StatusOrdemServico;
use App\Models\BandeiraCartaoPagamento;
use App\Models\MetodoPagamento;
use App\Models\OrdemServico;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\RestoreAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\ToggleButtons;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Grid;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentPtbrFormFields\Document;
use Leandrocfe\FilamentPtbrFormFields\Money;

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

                SelectFilter::make('tecnico_id')
                    ->label('Técnico')
                    ->options(fn() => User::pluck('name', 'id'))
                    ->searchable()
                    ->multiple(),

                Filter::make('numero')
                    ->label('Número')
                    ->schema([
                        TextInput::make('numero')
                            ->label('Nº da OS')
                            ->placeholder('Ex: OS-2024-0001'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder =>
                        $query->when($data['numero'] ?? null, fn($q, $v) => $q->where('numero', 'like', "%{$v}%"))
                    ),

                Filter::make('data_abertura')
                    ->label('Data de Abertura')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('data_abertura_de')->label('Abertura (De)'),
                        DatePicker::make('data_abertura_ate')->label('Abertura (Até)'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder =>
                        $query
                            ->when($data['data_abertura_de'] ?? null, fn($q, $d) => $q->whereDate('data_abertura', '>=', $d))
                            ->when($data['data_abertura_ate'] ?? null, fn($q, $d) => $q->whereDate('data_abertura', '<=', $d))
                    ),

                Filter::make('data_previsao')
                    ->label('Previsão de Conclusão')
                    ->columns(2)
                    ->columnSpanFull()
                    ->schema([
                        DatePicker::make('data_previsao_de')->label('Previsão (De)'),
                        DatePicker::make('data_previsao_ate')->label('Previsão (Até)'),
                    ])
                    ->query(fn(Builder $query, array $data): Builder =>
                        $query
                            ->when($data['data_previsao_de'] ?? null, fn($q, $d) => $q->whereDate('data_previsao_conclusao', '>=', $d))
                            ->when($data['data_previsao_ate'] ?? null, fn($q, $d) => $q->whereDate('data_previsao_conclusao', '<=', $d))
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
                Section::make('Ordem de Serviço')
                    ->collapsed()
                    ->columnSpanFull()
                    ->columns(4)
                    ->schema([
                        $filters['trashed']        ?? null,
                        $filters['numero']         ?? null,
                        $filters['status']         ?? null,
                        $filters['tecnico_id']     ?? null,
                        $filters['data_abertura']  ?? null,
                        $filters['data_previsao']  ?? null,
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
                    ->modalDescription(function (OrdemServico $record): string {
                        $saldo = max(0, (float) $record->valor_total - $record->movimentos()->where('tipo', 'entrada')->sum('valor_total_movimento'));
                        if ($saldo > 0.009) {
                            return 'Os serviços serão marcados como concluídos. Há saldo pendente de R$ ' . number_format($saldo, 2, ',', '.') . ' — a OS ficará com status Pendente.';
                        }
                        return 'Confirma a conclusão dos serviços desta OS? O pagamento está quitado.';
                    })
                    ->action(fn(OrdemServico $record) => $record->concluir()),

                Action::make('finalizar')
                    ->label('Finalizar')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->visible(fn(OrdemServico $record) => $record->status === StatusOrdemServico::Pendente)
                    ->modalHeading('Finalizar Ordem de Serviço')
                    ->modalWidth(Width::FourExtraLarge)
                    ->fillForm(fn(OrdemServico $record) => [
                        'valor_total_os'    => number_format((float) ($record->valor_total ?? 0), 2, ',', '.'),
                        'valor_pago_os'     => number_format((float) ($record->valor_pago ?? 0), 2, ',', '.'),
                        'valor_saldo_atual' => number_format((float) ($record->valor_saldo ?? 0), 2, ',', '.'),
                    ])
                    ->form([
                        Section::make('Resumo Financeiro')
                            ->icon('heroicon-o-calculator')
                            ->columns(3)
                            ->schema([
                                Money::make('valor_total_os')
                                    ->label('Total da OS')
                                    ->readOnly(),

                                Money::make('valor_pago_os')
                                    ->label('Já Pago')
                                    ->readOnly()
                                    ->extraAttributes(['class' => 'text-green-600 font-semibold']),

                                Money::make('valor_saldo_atual')
                                    ->label('Saldo Restante')
                                    ->readOnly()
                                    ->extraAttributes(['class' => 'text-red-600 font-bold']),
                            ]),

                        Section::make('Registrar Pagamentos')
                            ->icon('heroicon-o-banknotes')
                            ->description('Adicione os pagamentos recebidos')
                            ->schema([
                                Repeater::make('movimentos_novos')
                                    ->label('')
                                    ->addActionLabel('Adicionar Pagamento')
                                    ->deletable(true)
                                    ->reorderable(false)
                                    ->collapsible()
                                    ->cloneable()
                                    ->defaultItems(1)
                                    ->columns(4)
                                    ->itemLabel(
                                        fn(array $state): ?string =>
                                        isset($state['valor_total_movimento']) && $state['valor_total_movimento']
                                            ? 'Pagamento: R$ ' . number_format((float) $state['valor_total_movimento'], 2, ',', '.')
                                            : 'Novo Pagamento'
                                    )
                                    ->schema([
                                        Hidden::make('user_id')
                                            ->default(fn() => filament()->auth()->id()),

                                        Hidden::make('tipo')
                                            ->default('entrada'),

                                        ToggleButtons::make('metodo_pagamento_id')
                                            ->label('Forma de Pagamento')
                                            ->required()
                                            ->live()
                                            ->options(fn() => MetodoPagamento::pluck('nome', 'id'))
                                            ->icons([
                                                1 => 'heroicon-o-banknotes',
                                                2 => 'heroicon-o-credit-card',
                                                3 => 'heroicon-o-credit-card',
                                                4 => 'heroicon-o-qr-code',
                                            ])
                                            ->colors([
                                                1 => 'success',
                                                2 => 'info',
                                                3 => 'warning',
                                                4 => 'primary',
                                            ])
                                            ->inline()
                                            ->default(1)
                                            ->columnSpan(4),

                                        Select::make('cartao_pagamento_id')
                                            ->label('Bandeira do Cartão')
                                            ->options(fn() => BandeiraCartaoPagamento::pluck('bandeira', 'id'))
                                            ->searchable()
                                            ->visible(fn(Get $get) => in_array($get('metodo_pagamento_id'), [2, 3]))
                                            ->required(fn(Get $get) => in_array($get('metodo_pagamento_id'), [2, 3]))
                                            ->columnSpan(4),

                                        TextInput::make('autorizacao')
                                            ->label('Nº Autorização')
                                            ->placeholder('000000')
                                            ->maxLength(20)
                                            ->visible(fn(Get $get) => in_array($get('metodo_pagamento_id'), [2, 3, 4]))
                                            ->columnSpan(4),

                                        Money::make('valor_pago_movimento')
                                            ->label('Valor Pago')
                                            ->required()
                                            ->live(true)
                                            ->columnSpan(2)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $valorPago = self::normalizeMoney($state ?? 0);
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
                                            })
                                            ->helperText('Valor que será pago nesse pagamento'),

                                        Money::make('valor_recebido_movimento')
                                            ->label('Valor Recebido')
                                            ->live(true)
                                            ->columnSpan(2)
                                            ->afterStateUpdated(function (Set $set, Get $get, $state) {
                                                $recebido = self::normalizeMoney($state ?? 0);
                                                $pago = self::normalizeMoney($get('valor_pago_movimento'));
                                                $set('troco_movimento', number_format(max(0, $recebido - $pago), 2, ',', '.'));
                                            })
                                            ->helperText('Valor que está sendo entregue pelo cliente'),

                                        Money::make('troco_movimento')
                                            ->label('Troco')
                                            ->disabled()
                                            ->dehydrated()
                                            ->extraAttributes(['class' => 'text-red-600 font-semibold'])
                                            ->helperText('Valor que será devolvido ao cliente')
                                            ->columnSpan(1),

                                        Money::make('valor_total_movimento')
                                            ->label('Total')
                                            ->required()
                                            ->disabled()
                                            ->dehydrated()
                                            ->extraAttributes(['class' => 'font-bold text-lg text-green-600'])
                                            ->columnSpan(2),

                                        Money::make('valor_acrescimo_movimento')
                                            ->hidden()
                                            ->dehydrated()
                                            ->default('0,00'),

                                        Money::make('valor_desconto_movimento')
                                            ->hidden()
                                            ->dehydrated()
                                            ->default('0,00'),
                                    ]),
                            ]),
                    ])
                    ->action(function (OrdemServico $record, array $data) {
                        DB::beginTransaction();
                        try {
                            foreach ($data['movimentos_novos'] ?? [] as $movimento) {
                                $valorTotal = self::normalizeMoney($movimento['valor_total_movimento'] ?? 0);
                                if ($valorTotal <= 0) {
                                    continue;
                                }

                                $record->movimentos()->create([
                                    'user_id'                   => filament()->auth()->id(),
                                    'tipo'                      => 'entrada',
                                    'metodo_pagamento_id'       => $movimento['metodo_pagamento_id'] ?? null,
                                    'cartao_pagamento_id'       => $movimento['cartao_pagamento_id'] ?? null,
                                    'autorizacao'               => $movimento['autorizacao'] ?? null,
                                    'valor_pago_movimento'      => self::normalizeMoney($movimento['valor_pago_movimento'] ?? 0),
                                    'valor_acrescimo_movimento' => self::normalizeMoney($movimento['valor_acrescimo_movimento'] ?? 0),
                                    'valor_desconto_movimento'  => self::normalizeMoney($movimento['valor_desconto_movimento'] ?? 0),
                                    'valor_recebido_movimento'  => self::normalizeMoney($movimento['valor_recebido_movimento'] ?? 0),
                                    'troco_movimento'           => self::normalizeMoney($movimento['troco_movimento'] ?? 0),
                                    'valor_total_movimento'     => $valorTotal,
                                ]);
                            }

                            $totalPago  = (float) $record->movimentos()->where('tipo', 'entrada')->sum('valor_total_movimento');
                            $totalOS    = (float) $record->valor_total;
                            $saldo      = max(0, $totalOS - $totalPago);
                            $novoStatus = $saldo <= 0.009 ? StatusOrdemServico::Concluida : StatusOrdemServico::Pendente;

                            $record->update([
                                'status'      => $novoStatus,
                                'valor_pago'  => $totalPago,
                                'valor_saldo' => $saldo,
                            ]);

                            DB::commit();

                            $titulo = $novoStatus === StatusOrdemServico::Concluida ? 'OS finalizada com sucesso!' : 'Pagamento registrado';
                            $corpo  = $novoStatus === StatusOrdemServico::Pendente
                                ? 'Saldo restante: R$ ' . number_format($saldo, 2, ',', '.')
                                : 'OS quitada e concluída.';

                            Notification::make()->success()->title($titulo)->body($corpo)->send();
                        } catch (\Exception $e) {
                            DB::rollBack();
                            Notification::make()->danger()->title('Erro ao finalizar OS')->body($e->getMessage())->send();
                        }
                    }),

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

    protected static function calcularTotalMovimento(Set $set, Get $get): void
    {
        $pago      = self::normalizeMoney($get('valor_pago_movimento'));
        $acrescimo = self::normalizeMoney($get('valor_acrescimo_movimento') ?? '0,00');
        $desconto  = self::normalizeMoney($get('valor_desconto_movimento') ?? '0,00');

        $set('valor_total_movimento', number_format($pago + $acrescimo - $desconto, 2, ',', '.'));
    }

    protected static function normalizeMoney(mixed $value): float
    {
        if (is_null($value) || $value === '') {
            return 0;
        }
        $value = str_replace('.', '', (string) $value);
        $value = str_replace(',', '.', $value);
        return (float) $value;
    }
}
