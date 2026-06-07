<?php

namespace App\Filament\Resources\OrdensServicos\Pages;

use App\Enums\StatusOrdemServico;
use App\Filament\Resources\OrdensServicos\OrdemServicoResource;
use App\Models\Cliente;
use App\Models\User;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Exceptions\Halt;

class Relatorio extends Page
{
    protected static string $resource = OrdemServicoResource::class;

    protected static ?string $title = 'Relatório de Ordens de Serviço';

    protected string $view = 'filament.resources.ordensservicos.pages.relatorio';

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill([
            'data_inicio' => now()->startOfMonth()->format('Y-m-d'),
            'data_fim'    => now()->format('Y-m-d'),
            'tipo_data'   => 'abertura',
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
                                'abertura'  => 'Data de Abertura',
                                'previsao'  => 'Previsão de Conclusão',
                                'conclusao' => 'Data de Conclusão',
                                'criacao'   => 'Data de Criação',
                            ])
                            ->default('abertura')
                            ->required(),
                    ]),

                Section::make('Filtros Opcionais')
                    ->icon('heroicon-o-funnel')
                    ->columns(3)
                    ->collapsed()
                    ->schema([
                        Select::make('status')
                            ->label('Status')
                            ->options(StatusOrdemServico::class)
                            ->placeholder('Todos'),

                        Select::make('cliente_id')
                            ->label('Cliente')
                            ->options(fn() => Cliente::orderBy('nome')->pluck('nome', 'id'))
                            ->searchable()
                            ->placeholder('Todos os Clientes'),

                        Select::make('tecnico_id')
                            ->label('Técnico')
                            ->options(fn() => User::orderBy('name')->pluck('name', 'id'))
                            ->searchable()
                            ->placeholder('Todos os Técnicos'),
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
        $url    = route('relatorios.gerar-ordensservicos', $params);

        $this->dispatch('abrir-relatorio', url: $url);
    }
}
