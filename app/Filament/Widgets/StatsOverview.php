<?php

namespace App\Filament\Widgets;

use App\Models\Aluguel;
use App\Models\Carreta;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use Filament\Schemas\Components\Grid;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends StatsOverviewWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $carretas = Carreta::all()->count();
        $carretasAlugadas = Carreta::where('status', 'alugada')->count();
        $carretasDisponiveis = Carreta::where('status', 'disponivel')->count();
        $carretasManutencao = Carreta::where('status', 'manutencao')->count();
        $alugueis = Aluguel::all()->count();
        $alugueisAtivos = Aluguel::where('status', 'ativo')->count();
        $alugueisPendentes = Aluguel::where('status', 'pendente')->count();
        $alugueisFinalizados = Aluguel::where('status', 'finalizado')->count();
        $alugueisCancelados = Aluguel::where('status', 'cancelado')->count();
        $clientes = Cliente::all()->count();
        $orcamentos = Orcamento::all()->count();
        $orcamentosReprovados = Orcamento::where('status', 'reprovado')->count();
        $orcamentosAprovados = Orcamento::where('status', 'aprovado')->count();
        $orcamentosCancelados = Orcamento::where('status', 'cancelado')->count();
        $orcamentosAguardandoAprova = Orcamento::where('status', 'aguardando_aprovacao')->count();
        $orcamentosConvertidos = Orcamento::where('status', 'convertido')->count();
        $ordemServicos = OrdemServico::all()->count();
        $ordemServicosConcluidas = OrdemServico::where('status', 'concluida')->count();
        $ordemServicosAbertas = OrdemServico::where('status', 'aberta')->count();
        $ordemServicosEmAndamento = OrdemServico::where('status', 'em_andamento')->count();
        $ordemServicosCanceladas = OrdemServico::where('status', 'cancelada')->count();

        $clientesComAlugueisAtivos = Cliente::whereHas('alugueis', function ($query) {
            $query->where('status', 'ativo');
        })->count();

        $clientesComAlugueisPendentes = Cliente::whereHas('alugueis', function ($query) {
            $query->where('status', 'pendente');
        })->count();


        return [
            Grid::make()
                ->columnSpanFull()
                ->columns(6)
                ->schema([
                    Stat::make('Aluguéis', $alugueis)
                        ->columnSpan(3)
                        ->description($alugueisAtivos . ' Ativos - ' . $alugueisPendentes . ' Pendentes - ' . $alugueisFinalizados . ' Finalizados - ' . $alugueisCancelados . ' Cancelados')
                        ->icon('heroicon-o-banknotes'),

                    Stat::make('Ordens de Serviços', $ordemServicos)
                        ->columnSpan(3)
                        ->description($ordemServicosAbertas . ' Abertas - ' . $ordemServicosEmAndamento  . ' Em Andamento - ' . $ordemServicosConcluidas . ' Concluidas - ' . $ordemServicosCanceladas . ' Canceladas')
                        ->icon('heroicon-o-clipboard-document-list'),

                    Stat::make('Orçamentos', $orcamentos)
                        ->columnSpan(6)
                        ->description($orcamentosConvertidos . ' Convertidos em OS - ' . $orcamentosAprovados . ' Aprovados - ' . $orcamentosReprovados . ' Reprovados - ' . $orcamentosAguardandoAprova . ' Aguardando Aprovação - ' . $orcamentosCancelados . ' Cancelados')
                        ->icon('heroicon-o-document-text'),

                    Stat::make('Carretas/Reboques', $carretas)
                        ->columnSpan(3)
                        ->description($carretasAlugadas . ' Alugadas - ' . $carretasDisponiveis . ' Disponíveis - ' . $carretasManutencao . ' em Manutenção')
                        ->icon('heroicon-o-truck'),

                    Stat::make('Clientes', $clientes)
                        ->columnSpan(3)
                        ->description($clientesComAlugueisAtivos . ' com Aluguéis Ativos - ' . $clientesComAlugueisPendentes . ' com ALugueis Pendentes')
                        ->icon('heroicon-o-user-group'),
                ]),


        ];
    }
}
