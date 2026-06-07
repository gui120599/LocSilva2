<?php

namespace App\Filament\Resources\OrdensServicos\Schemas;

use App\Enums\StatusOrdemServico;
use App\Enums\TipoItem;
use App\Filament\Resources\Clientes\Schemas\ClienteForm;
use App\Models\Cliente;
use App\Models\MetodoPagamento;
use App\Models\OrdemServico;
use App\Models\Produto;
use App\Models\Servico;
use App\Models\User;
use Filament\Actions\Action;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Tabs;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Leandrocfe\FilamentPtbrFormFields\Money;
use Leandrocfe\FilamentPtbrFormFields\PhoneNumber;

class OrdemServicoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Tabs::make()
                    ->persistTabInQueryString()
                    ->tabs([

                        Tab::make('OS')
                            ->icon('heroicon-o-clipboard-document-list')
                            ->schema([
                                Section::make('Identificação')
                                    ->columns(3)
                                    ->schema([
                                        TextInput::make('numero')
                                            ->label('Nº da OS')
                                            ->required()
                                            ->unique(ignoreRecord: true)
                                            ->default(fn() => 'OS-' . now()->format('Y') . '-' . str_pad(
                                                OrdemServico::withTrashed()->count() + 1,
                                                4,
                                                '0',
                                                STR_PAD_LEFT
                                            ))
                                            ->columnSpan(1),

                                        Select::make('orcamento_id')
                                            ->label('Orçamento de Origem')
                                            ->relationship('orcamento', 'numero')
                                            ->searchable()
                                            ->preload()
                                            ->columnSpan(1)
                                            ->helperText('Opcional — preencha se a OS veio de um orçamento'),

                                        Select::make('tecnico_id')
                                            ->label('Técnico Responsável')
                                            ->options(fn() => User::pluck('name', 'id'))
                                            ->searchable()
                                            ->columnSpan(1),

                                        ToggleButtons::make('status')
                                            ->label('Status')
                                            ->options(
                                                collect(StatusOrdemServico::cases())
                                                    ->reject(fn($case) => in_array($case, [StatusOrdemServico::Cancelada, StatusOrdemServico::Pendente]))
                                                    ->mapWithKeys(fn($case) => [$case->value => $case->getLabel()])
                                            )
                                            ->colors(
                                                collect(StatusOrdemServico::cases())
                                                    ->reject(fn($case) => in_array($case, [StatusOrdemServico::Cancelada, StatusOrdemServico::Pendente]))
                                                    ->mapWithKeys(fn($case) => [$case->value => $case->getColor()])
                                            )
                                            ->icons(
                                                collect(StatusOrdemServico::cases())
                                                    ->reject(fn($case) => in_array($case, [StatusOrdemServico::Cancelada, StatusOrdemServico::Pendente]))
                                                    ->mapWithKeys(fn($case) => [$case->value => $case->getIcon()])
                                            )
                                            ->required()
                                            ->columns([
                                                'default' => 2,
                                                'sm'      => 3,
                                                'lg'      => 5,
                                            ])
                                            ->default(StatusOrdemServico::Aberta->value)
                                            ->columnSpanFull(),

                                        Hidden::make('user_id')
                                            ->default(fn() => filament()->auth()->id()),
                                    ]),

                                Section::make('Cliente & Veículo')
                                    ->icon('heroicon-s-user-group')
                                    ->columns(2)
                                    ->schema([
                                        Select::make('cliente_id')
                                            ->label('Cliente Cadastrado')
                                            ->relationship('cliente', 'nome')
                                            ->createOptionForm(ClienteForm::getComponents())
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->columnSpanFull()
                                            ->afterStateUpdated(function (Set $set, $state) {
                                                if (!$state) return;
                                                $cliente = Cliente::find($state);
                                                if ($cliente) {
                                                    $set('nome_cliente', $cliente->nome);
                                                    $set('telefone_cliente', $cliente->telefone);
                                                }
                                            }),

                                        TextInput::make('nome_cliente')
                                            ->label('Nome do Cliente')
                                            ->columnSpan(1)
                                            ->maxLength(255),

                                        PhoneNumber::make('telefone_cliente')
                                            ->label('Telefone')
                                            ->columnSpan(1),

                                        TextInput::make('veiculo_descricao')
                                            ->label('Descrição do Veículo')
                                            ->required()
                                            ->default('Carreta')
                                            ->columnSpan(1)
                                            ->maxLength(255),

                                        TextInput::make('veiculo_placa')
                                            ->label('Placa')
                                            ->columnSpan(1)
                                            ->maxLength(8),
                                    ]),

                                Section::make('Datas')
                                    ->icon('heroicon-s-calendar')
                                    ->columns(3)
                                    ->schema([
                                        DateTimePicker::make('data_abertura')
                                            ->label('Data de Abertura')
                                            ->required()
                                            ->default(now())
                                            ->seconds(false),

                                        DateTimePicker::make('data_previsao_conclusao')
                                            ->label('Previsão de Conclusão')
                                            ->seconds(false)
                                            ->nullable(),

                                        DateTimePicker::make('data_conclusao')
                                            ->label('Data de Conclusão')
                                            ->seconds(false)
                                            ->nullable()
                                            ->visible(fn(string $operation): bool => $operation === 'edit'),
                                    ]),
                            ]),

                        Tab::make('Itens')
                            ->icon('heroicon-o-list-bullet')
                            ->schema([
                                Repeater::make('itens')
                                    ->relationship()
                                    ->addActionLabel('Adicionar Item')
                                    ->collapsible()
                                    ->cloneable()
                                    ->reorderable(false)
                                    ->defaultItems(1)
                                    ->columns(5)
                                    ->live()
                                    ->itemLabel(function (array $state): ?string {
                                        $descricao = $state['descricao'] ?? null;
                                        $concluido = ($state['concluido'] ?? false) ? ' ✓' : '';
                                        $valor = isset($state['valor_total'])
                                            ? ' — R$ ' . number_format((float) $state['valor_total'], 2, ',', '.')
                                            : '';
                                        return $descricao ? $descricao . $valor . $concluido : 'Novo Item';
                                    })
                                    ->afterStateUpdated(fn(Get $get, Set $set) => self::recalcularTotais($get, $set))
                                    ->schema([
                                        ToggleButtons::make('tipo')
                                            ->label('Tipo')
                                            ->options(TipoItem::class)
                                            ->required()
                                            ->grouped()
                                            ->live()
                                            ->default(TipoItem::Servico->value)
                                            ->columnSpan(3)
                                            ->afterStateUpdated(function (Set $set) {
                                                $set('servico_id', null);
                                                $set('produto_id', null);
                                                $set('descricao', null);
                                                $set('valor_unitario', '0,00');
                                                $set('valor_total', '0,00');
                                            }),

                                        Select::make('servico_id')
                                            ->label('Serviço')
                                            ->columnSpan(3)
                                            ->allowHtml()
                                            ->searchable()
                                            ->live()
                                            ->getSearchResultsUsing(function (string $search) {
                                                return Servico::where('nome', 'like', "%{$search}%")
                                                    ->limit(10)
                                                    ->get()
                                                    ->mapWithKeys(fn($s) => [$s->id => self::getItemOptionString($s->nome, (float) $s->valor_padrao, $s->foto)])
                                                    ->toArray();
                                            })
                                            ->options(fn() => Servico::limit(10)->get()->mapWithKeys(fn($s) => [$s->id => self::getItemOptionString($s->nome, (float) $s->valor_padrao, $s->foto)])->toArray())
                                            ->getOptionLabelUsing(fn($value) => ($s = Servico::find($value)) ? self::getItemOptionString($s->nome, (float) $s->valor_padrao, $s->foto) : null)
                                            ->visible(fn(Get $get): bool => in_array($get('tipo'), [null, TipoItem::Servico->value, TipoItem::Servico]))
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                if (!$state) return;
                                                $servico = Servico::find($state);
                                                if ($servico) {
                                                    $set('descricao', $servico->nome);
                                                    $set('valor_unitario', number_format($servico->valor_padrao, 2, ',', '.'));
                                                    self::calcularValorTotalItem($get, $set);
                                                }
                                            }),

                                        Select::make('produto_id')
                                            ->label('Produto')
                                            ->columnSpan(3)
                                            ->allowHtml()
                                            ->searchable()
                                            ->live()
                                            ->getSearchResultsUsing(function (string $search) {
                                                return Produto::where('nome', 'like', "%{$search}%")
                                                    ->limit(10)
                                                    ->get()
                                                    ->mapWithKeys(fn($p) => [$p->id => self::getItemOptionString($p->nome, (float) $p->valor_unitario, $p->foto)])
                                                    ->toArray();
                                            })
                                            ->options(fn() => Produto::limit(10)->get()->mapWithKeys(fn($p) => [$p->id => self::getItemOptionString($p->nome, (float) $p->valor_unitario, $p->foto)])->toArray())
                                            ->getOptionLabelUsing(fn($value) => ($p = Produto::find($value)) ? self::getItemOptionString($p->nome, (float) $p->valor_unitario, $p->foto) : null)
                                            ->visible(fn(Get $get): bool => in_array($get('tipo'), [TipoItem::Produto->value, TipoItem::Produto]))
                                            ->afterStateUpdated(function (Get $get, Set $set, $state) {
                                                if (!$state) return;
                                                $produto = Produto::find($state);
                                                if ($produto) {
                                                    $set('descricao', $produto->nome);
                                                    $set('valor_unitario', number_format($produto->valor_unitario, 2, ',', '.'));
                                                    self::calcularValorTotalItem($get, $set);
                                                }
                                            }),

                                        TextInput::make('descricao')
                                            ->label('Descrição')
                                            ->required()
                                            ->columnSpan(6)
                                            ->maxLength(255),

                                        TextInput::make('quantidade')
                                            ->label('Qtd.')
                                            ->default(1)
                                            ->minValue(1)
                                            ->required()
                                            ->readOnly()
                                            ->live()
                                            ->prefixActions([
                                                Action::make('increment')
                                                    ->icon('heroicon-m-plus')
                                                    ->action(function (Get $get, Set $set, $state) {
                                                        $novaQtd = ($state ?? 1) + 1;
                                                        $set('quantidade', $novaQtd);

                                                        // CORRETO: Passe $get e $set (objetos), não $get() e $set()
                                                        self::calcularValorTotalItem($get, $set);
                                                    }),
                                            ])
                                            ->suffixActions([
                                                Action::make('decrement')
                                                    ->icon('heroicon-m-minus')
                                                    ->action(function (Get $get, Set $set, $state) { // Adicione $state aqui
                                                        $novaQtd = max(1, ($state ?? 1) - 1);
                                                        $set('quantidade', $novaQtd);

                                                        // CORRETO: Passe $get e $set (objetos), não $get() e $set()
                                                        self::calcularValorTotalItem($get, $set);
                                                    }),
                                            ])
                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::calcularValorTotalItem($get, $set))
                                            ->extraInputAttributes([
                                                'class' => 'text-center',
                                            ]),

                                        Money::make('valor_unitario')
                                            ->label('Valor Unitário')
                                            ->required()
                                            ->live()
                                            ->columnSpan(1)
                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::calcularValorTotalItem($get, $set)),

                                        Money::make('valor_acrescimo')
                                            ->label('Acréscimo')
                                            ->default('0,00')
                                            ->live(true)
                                            ->columnSpan(1)
                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::calcularValorTotalItem($get, $set)),

                                        Money::make('valor_desconto')
                                            ->label('Desconto')
                                            ->default('0,00')
                                            ->live(true)
                                            ->columnSpan(1)
                                            ->afterStateUpdated(fn(Get $get, Set $set) => self::calcularValorTotalItem($get, $set)),

                                        Money::make('valor_total')
                                            ->label('Total do Item')
                                            ->readOnly()
                                            ->columnSpan(1),

                                        ToggleButtons::make('concluido')
                                            ->label('Concluído?')
                                            ->boolean()
                                            ->grouped()
                                            ->default(false)
                                            ->columnSpan(2),

                                        Textarea::make('observacoes')
                                            ->label('Obs.')
                                            ->columnSpan(4)
                                            ->rows(1),
                                    ]),
                            ]),

                        Tab::make('Financeiro')
                            ->icon('heroicon-o-calculator')
                            ->schema([
                                
                                Section::make('Registrar Pagamentos')
                                    ->icon('heroicon-o-banknotes')
                                    ->description('Adicione os pagamentos recebidos')
                                    ->schema([
                                        Repeater::make('movimentos')
                                            ->relationship()
                                            ->addActionLabel('Adicionar Pagamento')
                                            ->deletable(true)
                                            ->reorderable(false)
                                            ->collapsible()
                                            ->cloneable()
                                            ->defaultItems(0)
                                            ->columns(4)
                                            ->itemLabel(
                                                fn(array $state): ?string =>
                                                isset($state['valor_total_movimento'])
                                                    ? 'Pagamento: R$ ' . number_format((float) $state['valor_total_movimento'], 2, ',', '.')
                                                    : 'Novo Pagamento'
                                            )
                                            ->live()
                                            ->afterStateUpdated(fn($state, Set $set, Get $get) => self::atualizarTotaisOS($state, $set, $get))
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
                                                    ->relationship('bandeiraCartao', 'bandeira')
                                                    ->searchable()
                                                    ->preload()
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
                                            ]),
                                    ]),
                            ]),

                        Tab::make('Observações')
                            ->icon('heroicon-o-chat-bubble-bottom-center-text')
                            ->schema([
                                Section::make()
                                    ->columns(1)
                                    ->schema([
                                        Textarea::make('observacoes')
                                            ->label('Observações Gerais')
                                            ->rows(4),

                                        Textarea::make('observacoes_tecnicas')
                                            ->label('Observações Técnicas')
                                            ->rows(4),
                                    ]),
                            ]),

                    ])
                    ->columnSpan(6),

                Section::make('Resumo Financeiro')
                    ->icon('heroicon-s-calculator')
                    ->schema([
                        Money::make('valor_subtotal')
                            ->label('Subtotal')
                            ->readOnly(),

                        Money::make('valor_acrescimo')
                            ->label('(+) Acréscimos')
                            ->default('0,00')
                            ->readOnly()
                            ->dehydrated(),

                        Money::make('valor_desconto')
                            ->label('(-) Descontos')
                            ->default('0,00')
                            ->readOnly()
                            ->dehydrated(),

                        Money::make('valor_total')
                            ->label('Total da OS')
                            ->readOnly()
                            ->extraAttributes(['class' => 'font-bold text-lg']),

                        Money::make('valor_pago')
                            ->label('Total Pago')
                            ->readOnly()
                            ->extraAttributes(['class' => 'text-green-600 font-semibold'])
                            ->visible(fn(string $operation): bool => $operation === 'edit'),

                        Money::make('valor_saldo')
                            ->label('Saldo Restante')
                            ->readOnly()
                            ->extraAttributes(['class' => 'text-red-600 font-bold text-lg'])
                            ->visible(fn(string $operation): bool => $operation === 'edit'),
                    ])
                    ->columnSpan(2),
            ])
            ->columns(8);
    }

    protected static function calcularValorTotalItem(Get $get, Set $set): void
    {
        $quantidade     = (float) ($get('quantidade') ?? 1);
        $valorUnitario  = self::normalizeMoney($get('valor_unitario')  ?? '0,00');
        $valorAcrescimo = self::normalizeMoney($get('valor_acrescimo') ?? '0,00');
        $valorDesconto  = self::normalizeMoney($get('valor_desconto')  ?? '0,00');

        $total = max(0, ($quantidade * $valorUnitario) + $valorAcrescimo - $valorDesconto);
        $set('valor_total', number_format($total, 2, ',', '.'));

        self::recalcularTotaisFromItem($get, $set);
    }

    protected static function recalcularTotais(Get $get, Set $set): void
    {
        $itens = $get('itens') ?? [];

        $subtotal  = collect($itens)->sum(fn($item) => (float) ($item['quantidade'] ?? 1) * self::normalizeMoney($item['valor_unitario'] ?? '0,00'));
        $acrescimo = collect($itens)->sum(fn($item) => self::normalizeMoney($item['valor_acrescimo'] ?? '0,00'));
        $desconto  = collect($itens)->sum(fn($item) => self::normalizeMoney($item['valor_desconto']  ?? '0,00'));
        $total     = $subtotal + $acrescimo - $desconto;

        $set('valor_subtotal',  number_format($subtotal,       2, ',', '.'));
        $set('valor_acrescimo', number_format($acrescimo,      2, ',', '.'));
        $set('valor_desconto',  number_format($desconto,       2, ',', '.'));
        $set('valor_total',     number_format(max(0, $total),  2, ',', '.'));
        $set('valor_saldo',     number_format(max(0, $total - self::normalizeMoney($get('valor_pago') ?? '0,00')), 2, ',', '.'));
    }

    protected static function recalcularTotaisFromItem(Get $get, Set $set): void
    {
        $itens = $get('../../itens') ?? [];

        $subtotal  = collect($itens)->sum(fn($item) => (float) ($item['quantidade'] ?? 1) * self::normalizeMoney($item['valor_unitario'] ?? '0,00'));
        $acrescimo = collect($itens)->sum(fn($item) => self::normalizeMoney($item['valor_acrescimo'] ?? '0,00'));
        $desconto  = collect($itens)->sum(fn($item) => self::normalizeMoney($item['valor_desconto']  ?? '0,00'));
        $total     = $subtotal + $acrescimo - $desconto;

        $set('../../valor_subtotal',  number_format($subtotal,       2, ',', '.'));
        $set('../../valor_acrescimo', number_format($acrescimo,      2, ',', '.'));
        $set('../../valor_desconto',  number_format($desconto,       2, ',', '.'));
        $set('../../valor_total',     number_format(max(0, $total),  2, ',', '.'));
        $set('../../valor_saldo',     number_format(max(0, $total - self::normalizeMoney($get('../../valor_pago') ?? '0,00')), 2, ',', '.'));
    }

    protected static function calcularTotalMovimento(Set $set, Get $get): void
    {
        $pago      = self::normalizeMoney($get('valor_pago_movimento'));
        $acrescimo = self::normalizeMoney($get('valor_acrescimo_movimento'));
        $desconto  = self::normalizeMoney($get('valor_desconto_movimento'));

        $set('valor_total_movimento', number_format($pago + $acrescimo - $desconto, 2, ',', '.'));
    }

    protected static function atualizarTotaisOS(array $movimentos, Set $set, Get $get): void
    {
        $totalPago = collect($movimentos)->sum(
            fn($m) => isset($m['valor_total_movimento']) ? self::normalizeMoney($m['valor_total_movimento']) : 0
        );

        $valorTotal = self::normalizeMoney($get('valor_total') ?? '0,00');

        $set('valor_pago',  number_format($totalPago,               2, ',', '.'));
        $set('valor_saldo', number_format(max(0, $valorTotal - $totalPago), 2, ',', '.'));
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

    protected static function getItemOptionString(string $name, float $price, ?string $foto): string
    {
        return view('filament.components.select-user-results')
            ->with('name', $name)
            ->with('email', $price)
            ->with('image', $foto)
            ->with('suffix', '')
            ->render();
    }
}
