<?php

namespace App\Filament\Resources\Orcamentos\Pages;

use App\Enums\StatusOrcamento;
use App\Filament\Resources\Orcamentos\OrcamentoResource;
use App\Models\Cliente;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;

class Relatorio extends Page
{
    protected static string $resource = OrcamentoResource::class;

    protected static ?string $title = 'Relatório de Orçamentos';

    protected string $view = 'filament.resources.orcamentos.pages.relatorio';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'data_inicio' => now()->startOfMonth()->format('Y-m-d'),
            'data_fim'    => now()->format('Y-m-d'),
            'tipo_data'   => 'criacao',
        ]);
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Período')
                    ->icon('heroicon-o-calendar-days')
                    ->description('Selecione o intervalo de datas e o campo a ser filtrado')
                    ->columns(3)
                    ->schema([
                        DatePicker::make('data_inicio')
                            ->label('Data Início')
                            ->required()
                            ->native(false),

                        DatePicker::make('data_fim')
                            ->label('Data Fim')
                            ->required()
                            ->native(false)
                            ->afterOrEqual('data_inicio'),

                        Select::make('tipo_data')
                            ->label('Tipo de Data')
                            ->options([
                                'criacao'    => 'Data de Criação',
                                'validade'   => 'Data de Validade',
                            ])
                            ->default('criacao')
                            ->required(),
                    ]),

                Section::make('Filtros Opcionais')
                    ->icon('heroicon-o-funnel')
                    ->columns(2)
                    ->collapsed()
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(StatusOrcamento::class)
                            ->placeholder('Todos'),

                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->options(fn() => Cliente::orderBy('nome')->pluck('nome', 'id'))
                            ->searchable()
                            ->placeholder('Todos os Clientes'),
                    ]),
            ])
            ->statePath('data');
    }

    public function gerarRelatorio(): void
    {
        try {
            $data = $this->form->getState();
        } catch (Halt) {
            return;
        }

        $params = array_filter($data, fn($v) => $v !== null && $v !== '');
        $url    = route('relatorios.gerar-orcamentos', $params);

        $this->dispatch('abrir-relatorio', url: $url);
    }
}
