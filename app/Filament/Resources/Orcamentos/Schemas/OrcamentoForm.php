<?php

namespace App\Filament\Resources\Orcamentos\Schemas;

use App\Enums\StatusOrcamento;
use App\Enums\TipoItem;
use App\Enums\ValidadeOrcamento;
use App\Models\Cliente;
use App\Models\Produto;
use App\Models\Servico;
use Filament\Actions\Action;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\ToggleButtons;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Components\Wizard;
use Filament\Schemas\Components\Wizard\Step;
use Filament\Schemas\Schema;
use Leandrocfe\FilamentPtbrFormFields\Money;
use Leandrocfe\FilamentPtbrFormFields\PhoneNumber;

class OrcamentoForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Wizard::make([

                    Step::make('Cliente & Veículo')
                        ->description('Identifique o cliente e o veículo a ser atendido')
                        ->icon('heroicon-o-user')
                        ->columns(2)
                        ->schema([
                            Section::make('Cliente')
                                ->icon('heroicon-s-user-group')
                                ->columns(2)
                                ->schema([
                                    Select::make('cliente_id')
                                        ->label('Cliente Cadastrado')
                                        ->relationship('cliente', 'nome')
                                        ->searchable()
                                        ->preload()
                                        ->live()
                                        ->columnSpanFull()
                                        ->afterStateUpdated(function (Set $set, $state) {
                                            if (!$state) {
                                                return;
                                            }
                                            $cliente = Cliente::find($state);
                                            if ($cliente) {
                                                $set('nome_cliente', $cliente->nome);
                                                $set('telefone_cliente', $cliente->telefone);
                                            }
                                        })
                                        ->helperText('Deixe vazio para clientes não cadastrados'),

                                    TextInput::make('nome_cliente')
                                        ->label('Nome do Cliente')
                                        ->columnSpan(1)
                                        ->maxLength(255),

                                    PhoneNumber::make('telefone_cliente')
                                        ->label('Telefone')
                                        ->columnSpan(1),
                                ]),

                            Section::make('Veículo')
                                ->icon('heroicon-s-truck')
                                ->columns(2)
                                ->schema([
                                    TextInput::make('veiculo_descricao')
                                        ->label('Descrição do Veículo')
                                        ->required()
                                        ->columnSpan(1)
                                        ->placeholder('Ex: Reboque, Carreta Baú, Tanque...')
                                        ->validationMessages([
                                            'required' => 'Descrição do veículo é obrigatório.'
                                        ])
                                        ->maxLength(255),

                                    TextInput::make('veiculo_placa')
                                        ->label('Placa')
                                        ->columnSpan(1)
                                        ->placeholder('AAA-0000')
                                        ->maxLength(8),
                                ]),
                        ]),

                    Step::make('Itens do Orçamento')
                        ->description('Adicione os serviços e produtos do orçamento')
                        ->icon('heroicon-o-clipboard-document-list')
                        ->schema([
                            Repeater::make('itens')
                                ->relationship()
                                ->addActionLabel('Adicionar Item')
                                ->collapsible()
                                ->cloneable()
                                ->reorderable(false)
                                ->defaultItems(1)
                                ->columns(6)
                                ->live()
                                ->itemLabel(function (array $state): ?string {
                                    $descricao = $state['descricao'] ?? null;
                                    $valor = isset($state['valor_total'])
                                        ? ' — R$ ' . number_format((float) $state['valor_total'], 2, ',', '.')
                                        : '';
                                    return $descricao ? $descricao . $valor : 'Novo Item';
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
                                        ->readOnly(fn(Get $get): bool => in_array($get('tipo'), [null, TipoItem::Servico->value, TipoItem::Servico, TipoItem::Produto->value, TipoItem::Produto]))
                                        ->required()
                                        ->columnSpan(6)
                                        ->validationMessages([
                                            'required' => 'A Descrição é obrigatório.'
                                        ])
                                        ->maxLength(255),

                                    TextInput::make('quantidade')
                                        ->label('Qtd.')
                                        ->default(1)
                                        ->minValue(1)
                                        ->required()
                                        ->readOnly()
                                        ->live()
                                        ->columnSpan(1)
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
                                        ->readOnly(fn(Get $get): bool => in_array($get('tipo'), [null, TipoItem::Servico->value, TipoItem::Servico, TipoItem::Produto->value, TipoItem::Produto]))
                                        ->live(true)
                                        ->columnSpan(1)
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::calcularValorTotalItem($get, $set))
                                        ->extraInputAttributes([
                                            'class' => 'text-start',
                                        ]),

                                    Money::make('valor_acrescimo')
                                        ->label('Acréscimo')
                                        ->default('0,00')
                                        ->live(true)
                                        ->readOnly(fn(Get $get): bool => self::normalizeMoney($get('../../_total_acrescimo_itens') ?? '0') > 0)
                                        ->hint(fn(Get $get): ?string => self::normalizeMoney($get('../../valor_acrescimo') ?? '0') > 0 ? 'Definido pelo global' : null)
                                        ->hintIcon(fn(Get $get): ?string => self::normalizeMoney($get('../../valor_acrescimo') ?? '0') > 0 ? 'heroicon-o-lock-closed' : null)
                                        ->hintColor('warning')
                                        ->columnSpan(1)
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::calcularValorTotalItem($get, $set))
                                        ->extraInputAttributes([
                                            'class' => 'text-start',
                                        ]),

                                    Money::make('valor_desconto')
                                        ->label('Desconto')
                                        ->default('0,00')
                                        ->live(true)
                                        ->readOnly(fn(Get $get): bool => self::normalizeMoney($get('../../valor_desconto') ?? '0') > 0)
                                        ->hint(fn(Get $get): ?string => self::normalizeMoney($get('../../valor_desconto') ?? '0') > 0 ? 'Definido pelo global' : null)
                                        ->hintIcon(fn(Get $get): ?string => self::normalizeMoney($get('../../valor_desconto') ?? '0') > 0 ? 'heroicon-o-lock-closed' : null)
                                        ->hintColor('warning')
                                        ->columnSpan(1)
                                        ->afterStateUpdated(fn(Get $get, Set $set) => self::calcularValorTotalItem($get, $set))
                                        ->extraInputAttributes([
                                            'class' => 'text-start',
                                        ]),

                                    Money::make('valor_total')
                                        ->label('Total do Item')
                                        ->readOnly()
                                        ->columnSpan(2)
                                        ->extraInputAttributes([
                                            'class' => 'text-start',
                                        ]),

                                    Textarea::make('observacoes')
                                        ->label('Obs.')
                                        ->columnSpanFull()
                                        ->rows(1),
                                ]),
                        ]),

                    Step::make('Valores & Condições')
                        ->description('Defina descontos, acréscimos e validade do orçamento')
                        ->icon('heroicon-o-currency-dollar')
                        ->columns(2)
                        ->schema([
                            Section::make('Resumo Financeiro')
                                ->columnSpan(1)
                                ->icon('heroicon-o-calculator')
                                ->schema([
                                    Money::make('valor_subtotal')
                                        ->label('Subtotal dos Itens')
                                        ->readOnly(),

                                    Money::make('valor_acrescimo')
                                        ->label('(+) Acréscimos')
                                        ->default('0,00')
                                        ->live(true)
                                        ->readOnly(fn(Get $get): bool =>
                                            self::normalizeMoney($get('_total_acrescimo_itens') ?? '0') > 0
                                        )
                                        ->helperText(fn(Get $get): string =>
                                            self::normalizeMoney($get('_total_acrescimo_itens') ?? '0') > 0 &&
                                            self::normalizeMoney($get('valor_acrescimo') ?? '0') <= 0
                                                ? 'Bloqueado — itens possuem acréscimo próprio. Zere os itens para usar o campo global.'
                                                : 'Distribuído igualmente entre os itens ao confirmar.'
                                        )
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::distribuirAcrescimoAosItens($get, $set);
                                            self::recalcularTotais($get, $set);
                                        }),

                                    Money::make('valor_desconto')
                                        ->label('(-) Descontos')
                                        ->default('0,00')
                                        ->live(true)
                                        ->readOnly(fn(Get $get): bool =>
                                            self::normalizeMoney($get('_total_desconto_itens') ?? '0') > 0 &&
                                            self::normalizeMoney($get('valor_desconto') ?? '0') <= 0
                                        )
                                        ->helperText(fn(Get $get): string =>
                                            self::normalizeMoney($get('_total_desconto_itens') ?? '0') > 0 &&
                                            self::normalizeMoney($get('valor_desconto') ?? '0') <= 0
                                                ? 'Bloqueado — itens possuem desconto próprio. Zere os itens para usar o campo global.'
                                                : 'Distribuído igualmente entre os itens ao confirmar.'
                                        )
                                        ->afterStateUpdated(function (Get $get, Set $set) {
                                            self::distribuirDescontoAosItens($get, $set);
                                            self::recalcularTotais($get, $set);
                                        }),

                                    Money::make('valor_total')
                                        ->label('Valor Total')
                                        ->readOnly()
                                        ->extraAttributes(['class' => 'font-bold text-lg']),
                                ]),

                            Section::make('Condições')
                                ->columnSpan(1)
                                ->icon('heroicon-o-document-text')
                                ->schema([
                                    TextInput::make('numero')
                                        ->label('Nº do Orçamento')
                                        ->required()
                                        ->readOnly()
                                        ->unique(ignoreRecord: true)
                                        ->default(fn() => 'ORC-' . now()->format('Y') . '-' . str_pad(
                                            \App\Models\Orcamento::withTrashed()->count() + 1,
                                            4,
                                            '0',
                                            STR_PAD_LEFT
                                        ))
                                        ->maxLength(20),

                                    Hidden::make('status')
                                        ->default(StatusOrcamento::AguardandoAprovacao->value),

                                    ToggleButtons::make('_prazo_validade')
                                        ->label('Prazo de Validade')
                                        ->options(ValidadeOrcamento::class)
                                        ->grouped()
                                        ->dehydrated(false)
                                        ->live()
                                        ->afterStateUpdated(function (Set $set, $state) {
                                            if ($state) {
                                                $dias = $state instanceof ValidadeOrcamento ? $state->value : (int) $state;
                                                $set('data_validade', today()->addDays($dias)->toDateString());
                                            }
                                        })
                                        ->helperText('Atalho para preencher a data de validade abaixo.'),

                                    DatePicker::make('data_validade')
                                        ->label('Válido até (data exata)')
                                        ->minDate(today()),

                                    Textarea::make('observacoes')
                                        ->label('Observações')
                                        ->rows(3),
                                ]),

                            Hidden::make('user_id')
                                ->default(fn() => filament()->auth()->id()),

                            TextInput::make('_total_acrescimo_itens')
                                ->default('0,00')
                                ->dehydrated(false),

                            TextInput::make('_total_desconto_itens')
                                ->default('0,00')
                                ->dehydrated(false),
                        ]),
                ]),
            ]);
    }

    protected static function calcularValorTotalItem(Get $get, Set $set): void
    {
        $quantidade    = (float) ($get('quantidade') ?? 1);
        $valorUnitario = self::normalizeMoney($get('valor_unitario') ?? '0,00');
        $valorAcrescimo = self::normalizeMoney($get('valor_acrescimo') ?? '0,00');
        $valorDesconto = self::normalizeMoney($get('valor_desconto') ?? '0,00');

        $total = max(0, ($quantidade * $valorUnitario) + $valorAcrescimo - $valorDesconto);
        $set('valor_total', number_format($total, 2, ',', '.'));

        // Dispara o recálculo global usando caminhos relativos ao contexto do item.
        // "../../" sobe: campo → item → contexto pai do Repeater (raiz do Wizard).
        self::recalcularTotaisFromItem($get, $set);
    }

    // Chamado a partir do contexto do Repeater (add/remove de itens).
    protected static function recalcularTotais(Get $get, Set $set): void
    {
        $itens = $get('itens') ?? [];

        // Subtotal bruto: só quantidade × valor_unitário.
        // Os descontos por item são exibição de referência para o gestor;
        // o desconto global é aplicado uma única vez no total geral,
        // evitando dupla contagem quando o desconto global foi distribuído aos itens.
        $subtotal = collect($itens)->sum(function ($item) {
            $qtd  = (float) ($item['quantidade'] ?? 1);
            $unit = self::normalizeMoney($item['valor_unitario'] ?? '0,00');

            return $qtd * $unit;
        });

        $acrescimo = collect($itens)->sum(function ($item) {
            $acres = self::normalizeMoney($item['valor_acrescimo'] ?? '0,00');
            return $acres;
        });
        $desconto = collect($itens)->sum(function ($item) {
            $desc = self::normalizeMoney($item['valor_desconto'] ?? '0,00');
            return $desc;
        });

        $total = $subtotal + $acrescimo - $desconto;

        $set('valor_subtotal',           number_format($subtotal,   2, ',', '.'));
        $set('valor_acrescimo',          number_format($acrescimo,  2, ',', '.'));
        $set('valor_desconto',           number_format($desconto,   2, ',', '.'));
        $set('valor_total',              number_format(max(0, $total), 2, ',', '.'));
        // Alimenta os hidden fields que controlam o readOnly dos campos globais
        $set('_total_acrescimo_itens',   number_format($acrescimo,  2, ',', '.'));
        $set('_total_desconto_itens',    number_format($desconto,   2, ',', '.'));
    }

    // Chamado de dentro de um item do Repeater — usa "../../" para alcançar o estado raiz.
    protected static function recalcularTotaisFromItem(Get $get, Set $set): void
    {
        $itens = $get('../../itens') ?? [];

        $subtotal  = collect($itens)->sum(fn($item) => (float) ($item['quantidade'] ?? 1) * self::normalizeMoney($item['valor_unitario'] ?? '0,00'));
        $acrescimo = collect($itens)->sum(fn($item) => self::normalizeMoney($item['valor_acrescimo'] ?? '0,00'));
        $desconto  = collect($itens)->sum(fn($item) => self::normalizeMoney($item['valor_desconto'] ?? '0,00'));

        $total = $subtotal + $acrescimo - $desconto;

        $set('../../valor_subtotal',          number_format($subtotal,       2, ',', '.'));
        $set('../../valor_acrescimo',         number_format($acrescimo,      2, ',', '.'));
        $set('../../valor_desconto',          number_format($desconto,       2, ',', '.'));
        $set('../../valor_total',             number_format(max(0, $total),  2, ',', '.'));
        // Mantém os hidden fields sincronizados a partir do contexto do item
        $set('../../_total_acrescimo_itens',  number_format($acrescimo,      2, ',', '.'));
        $set('../../_total_desconto_itens',   number_format($desconto,       2, ',', '.'));
    }

    protected static function algumItemTemDesconto(Get $get): bool
    {
        return collect($get('itens') ?? [])->contains(
            fn($item) => self::normalizeMoney($item['valor_desconto'] ?? '0,00') > 0
        );
    }

    protected static function algumItemTemAcrescimo(Get $get): bool
    {
        return collect($get('itens') ?? [])->contains(
            fn($item) => self::normalizeMoney($item['valor_acrescimo'] ?? '0,00') > 0
        );
    }

    protected static function distribuirDescontoAosItens(Get $get, Set $set): void
    {
        $globalDesconto = self::normalizeMoney($get('valor_desconto') ?? '0,00');
        $itens = $get('itens') ?? [];

        if (empty($itens)) {
            return;
        }

        if ($globalDesconto <= 0) {
            foreach ($itens as &$item) {
                $bruto = (float) ($item['quantidade'] ?? 1) * self::normalizeMoney($item['valor_unitario'] ?? '0,00');
                $acr   = self::normalizeMoney($item['valor_acrescimo'] ?? '0,00');
                $item['valor_desconto'] = '0,00';
                $item['valor_total']    = number_format(max(0, $bruto + $acr), 2, ',', '.');
            }
            unset($item);
            $set('itens', $itens);
            return;
        }

        $count = count($itens);
        $parcela = round($globalDesconto / $count, 2);
        $somaAplicada = 0;
        $indice = 0;

        foreach ($itens as &$item) {
            $indice++;
            $parcelaItem   = $indice === $count ? round($globalDesconto - $somaAplicada, 2) : $parcela;
            $somaAplicada += $parcelaItem;

            $bruto = (float) ($item['quantidade'] ?? 1) * self::normalizeMoney($item['valor_unitario'] ?? '0,00');
            $acr   = self::normalizeMoney($item['valor_acrescimo'] ?? '0,00');

            $item['valor_desconto'] = number_format($parcelaItem, 2, ',', '.');
            $item['valor_total']    = number_format(max(0, $bruto + $acr - $parcelaItem), 2, ',', '.');
        }
        unset($item);

        $set('itens', $itens);
    }

    protected static function distribuirAcrescimoAosItens(Get $get, Set $set): void
    {
        $globalAcrescimo = self::normalizeMoney($get('valor_acrescimo') ?? '0,00');
        $itens = $get('itens') ?? [];

        if (empty($itens)) {
            return;
        }

        if ($globalAcrescimo <= 0) {
            foreach ($itens as &$item) {
                $bruto = (float) ($item['quantidade'] ?? 1) * self::normalizeMoney($item['valor_unitario'] ?? '0,00');
                $desc  = self::normalizeMoney($item['valor_desconto'] ?? '0,00');
                $item['valor_acrescimo'] = '0,00';
                $item['valor_total']     = number_format(max(0, $bruto - $desc), 2, ',', '.');
            }
            unset($item);
            $set('itens', $itens);
            return;
        }

        $count = count($itens);
        $parcela = round($globalAcrescimo / $count, 2);
        $somaAplicada = 0;
        $indice = 0;

        foreach ($itens as &$item) {
            $indice++;
            $parcelaItem   = $indice === $count ? round($globalAcrescimo - $somaAplicada, 2) : $parcela;
            $somaAplicada += $parcelaItem;

            $bruto = (float) ($item['quantidade'] ?? 1) * self::normalizeMoney($item['valor_unitario'] ?? '0,00');
            $desc  = self::normalizeMoney($item['valor_desconto'] ?? '0,00');

            $item['valor_acrescimo'] = number_format($parcelaItem, 2, ',', '.');
            $item['valor_total']     = number_format(max(0, $bruto + $parcelaItem - $desc), 2, ',', '.');
        }
        unset($item);

        $set('itens', $itens);
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
