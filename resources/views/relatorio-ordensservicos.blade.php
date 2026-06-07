<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Relatório de Ordens de Serviço</title>
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>
<body>
    <div class="p-1 max-w-7xl mx-auto ring-1 ring-gray-100/70">

        <!-- Cabeçalho -->
        <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b-2 border-primary-600 pb-2 mb-2">
            <div class="space-y-1">
                <h2 class="text-lg font-extrabold text-gray-800">RELATÓRIO DE ORDENS DE SERVIÇO</h2>
                <p class="text-xs text-gray-500">
                    Período: {{ \Carbon\Carbon::parse($filtros['data_inicio'])->format('d/m/Y') }}
                    até {{ \Carbon\Carbon::parse($filtros['data_fim'])->format('d/m/Y') }}
                </p>
                <p class="text-xs text-gray-500">Emitido em: {{ now()->format('d/m/Y \à\s H:i:s') }}</p>
            </div>
            <div>
                <img src="{{ asset('/logos/Logo LocSilva white.png') }}" alt="Logo LocSilva" class="h-14 mt-4 sm:mt-0 opacity-85">
            </div>
        </header>

        <!-- Empresa -->
        <div class="p-2 mb-2 bg-gray-50 border border-gray-200 rounded-lg">
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-[10px] text-gray-700">
                <div>
                    <p><strong>Empresa:</strong> 22.341.672 IVAN DE AQUINO SILVA - ME</p>
                    <p><strong>CNPJ:</strong> 22.341.672/0001-01</p>
                </div>
                <div>
                    <p><strong>Telefone:</strong> (62) 9 9323-9697</p>
                    <p><strong>Endereço:</strong> R. Maria Conceição, 245, Parque Amazonia, Goiânia - GO</p>
                </div>
            </div>
        </div>

        <!-- Filtros -->
        <div class="p-2 mb-2 border border-blue-200 bg-blue-50 rounded-lg">
            <h5 class="font-bold text-gray-800 mb-2 flex items-center text-sm">
                <x-heroicon-s-funnel class="w-5 h-5 mr-2 text-blue-600" /> FILTROS APLICADOS
            </h5>
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-2 text-[9px] text-gray-700">
                @if($filtros['status'])
                    <div class="p-2 bg-white rounded border border-gray-200">
                        <p class="text-gray-600">Status:</p>
                        <p class="font-bold">{{ \App\Enums\StatusOrdemServico::tryFrom($filtros['status'])?->getLabel() ?? $filtros['status'] }}</p>
                    </div>
                @endif
                @if($filtros['cliente_nome'])
                    <div class="p-2 bg-white rounded border border-gray-200">
                        <p class="text-gray-600">Cliente:</p>
                        <p class="font-bold">{{ $filtros['cliente_nome'] }}</p>
                    </div>
                @endif
                @if($filtros['tecnico_nome'])
                    <div class="p-2 bg-white rounded border border-gray-200">
                        <p class="text-gray-600">Técnico:</p>
                        <p class="font-bold">{{ $filtros['tecnico_nome'] }}</p>
                    </div>
                @endif
                <div class="p-2 bg-white rounded border border-gray-200">
                    <p class="text-gray-600">Tipo de Data:</p>
                    <p class="font-bold capitalize">{{ $filtros['tipo_data'] }}</p>
                </div>
            </div>
        </div>

        <!-- Resumo -->
        <div class="p-2 mb-2 border-2 border-green-300 bg-green-50 rounded-lg">
            <h5 class="font-bold text-gray-800 mb-2 flex items-center text-sm">
                <x-heroicon-s-chart-bar class="w-5 h-5 mr-2 text-green-600" /> RESUMO GERAL
            </h5>
            <div class="grid grid-cols-2 sm:grid-cols-5 gap-2 text-center">
                <div class="p-2 bg-white rounded-lg border border-gray-200">
                    <p class="text-[9px] text-gray-600 mb-1">Total de OS</p>
                    <p class="text-xl font-bold text-blue-700">{{ $ordens->count() }}</p>
                </div>
                <div class="p-2 bg-white rounded-lg border border-gray-200">
                    <p class="text-[9px] text-gray-600 mb-1">Valor Total</p>
                    <p class="text-lg font-bold text-green-600">
                        R$ {{ number_format($ordens->sum('valor_total'), 2, ',', '.') }}
                    </p>
                </div>
                <div class="p-2 bg-white rounded-lg border border-gray-200">
                    <p class="text-[9px] text-gray-600 mb-1">Total Pago</p>
                    <p class="text-lg font-bold text-green-700">
                        R$ {{ number_format($ordens->sum('valor_pago'), 2, ',', '.') }}
                    </p>
                </div>
                <div class="p-2 bg-white rounded-lg border border-gray-200">
                    <p class="text-[9px] text-gray-600 mb-1">Saldo Pendente</p>
                    <p class="text-lg font-bold text-orange-600">
                        R$ {{ number_format($ordens->sum('valor_saldo'), 2, ',', '.') }}
                    </p>
                </div>
                <div class="p-2 bg-white rounded-lg border border-gray-200">
                    <p class="text-[9px] text-gray-600 mb-1">Ticket Médio</p>
                    <p class="text-lg font-bold text-purple-700">
                        R$ {{ $ordens->count() ? number_format($ordens->sum('valor_total') / $ordens->count(), 2, ',', '.') : '0,00' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Tabela -->
        <div class="p-2 mb-2 border border-gray-200 bg-white rounded-lg">
            <h5 class="font-bold text-gray-800 mb-2 flex items-center text-sm">
                <x-heroicon-s-clipboard-document-list class="w-5 h-5 mr-2 text-primary-600" /> LISTAGEM DETALHADA
            </h5>

            @if($ordens->count())
                <div class="overflow-x-auto">
                    <table class="w-full text-[8px]">
                        <thead class="bg-gray-100 border-b-2 border-gray-300">
                            <tr>
                                <th class="text-left p-1 font-bold text-gray-700">Nº OS</th>
                                <th class="text-left p-1 font-bold text-gray-700">Cliente</th>
                                <th class="text-left p-1 font-bold text-gray-700">Veículo / Placa</th>
                                <th class="text-left p-1 font-bold text-gray-700">Técnico</th>
                                <th class="text-left p-1 font-bold text-gray-700">Abertura</th>
                                <th class="text-left p-1 font-bold text-gray-700">Previsão</th>
                                <th class="text-center p-1 font-bold text-gray-700">Itens</th>
                                <th class="text-right p-1 font-bold text-gray-700">Total</th>
                                <th class="text-right p-1 font-bold text-gray-700">Pago</th>
                                <th class="text-right p-1 font-bold text-gray-700">Saldo</th>
                                <th class="text-center p-1 font-bold text-gray-700">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($ordens as $os)
                                <tr class="border-b border-gray-100 hover:bg-gray-50">
                                    <td class="p-1 font-semibold text-gray-700">{{ $os->numero }}</td>
                                    <td class="p-1 text-gray-700">
                                        {{ \Illuminate\Support\Str::limit($os->cliente?->nome ?? $os->nome_cliente ?? '—', 18) }}
                                    </td>
                                    <td class="p-1 text-gray-700">
                                        {{ $os->veiculo_descricao ?? '—' }}
                                        @if($os->veiculo_placa)
                                            <br><span class="font-mono text-[7px] text-gray-500">{{ $os->veiculo_placa }}</span>
                                        @endif
                                    </td>
                                    <td class="p-1 text-gray-700">{{ $os->tecnico?->name ?? '—' }}</td>
                                    <td class="p-1 text-gray-700">{{ \Carbon\Carbon::parse($os->data_abertura)->format('d/m/Y') }}</td>
                                    <td class="p-1 text-gray-700">
                                        {{ $os->data_previsao_conclusao ? \Carbon\Carbon::parse($os->data_previsao_conclusao)->format('d/m/Y') : '—' }}
                                    </td>
                                    <td class="p-1 text-center font-bold text-blue-700">{{ $os->itens->count() }}</td>
                                    <td class="p-1 text-right font-semibold text-gray-800">
                                        R$ {{ number_format($os->valor_total, 2, ',', '.') }}
                                    </td>
                                    <td class="p-1 text-right font-semibold text-green-700">
                                        R$ {{ number_format($os->valor_pago, 2, ',', '.') }}
                                    </td>
                                    <td class="p-1 text-right font-semibold {{ $os->valor_saldo > 0 ? 'text-orange-600' : 'text-green-700' }}">
                                        R$ {{ number_format($os->valor_saldo, 2, ',', '.') }}
                                    </td>
                                    <td class="p-1 text-center">
                                        <span class="px-2 py-0.5 rounded text-[7px] font-bold bg-gray-100 text-gray-800">
                                            {{ $os->status instanceof \App\Enums\StatusOrdemServico ? $os->status->getLabel() : $os->status }}
                                        </span>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                        <tfoot class="bg-gray-100 border-t-2 border-gray-400">
                            <tr class="font-bold">
                                <td colspan="7" class="p-1 text-right text-gray-800">TOTAIS:</td>
                                <td class="p-1 text-right text-gray-800">R$ {{ number_format($ordens->sum('valor_total'), 2, ',', '.') }}</td>
                                <td class="p-1 text-right text-green-700">R$ {{ number_format($ordens->sum('valor_pago'), 2, ',', '.') }}</td>
                                <td class="p-1 text-right text-orange-600">R$ {{ number_format($ordens->sum('valor_saldo'), 2, ',', '.') }}</td>
                                <td class="p-1"></td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            @else
                <p class="text-center text-gray-500 italic py-8">Nenhuma OS encontrada com os filtros aplicados</p>
            @endif
        </div>

        <!-- Por Status -->
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-2 mb-2">
            @php $porStatus = $ordens->groupBy(fn($o) => $o->status instanceof \BackedEnum ? $o->status->value : $o->status); @endphp
            @foreach(\App\Enums\StatusOrdemServico::cases() as $status)
                @php $grupo = $porStatus->get($status->value, collect()); @endphp
                @if($grupo->count())
                    <div class="p-2 border border-gray-200 bg-white rounded-lg">
                        <h5 class="font-bold text-gray-800 mb-2 text-[10px]">{{ $status->getLabel() }}</h5>
                        <div class="space-y-1 text-[9px]">
                            <div class="flex justify-between">
                                <span class="text-gray-600">Quantidade:</span>
                                <span class="font-bold">{{ $grupo->count() }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Valor Total:</span>
                                <span class="font-bold">R$ {{ number_format($grupo->sum('valor_total'), 2, ',', '.') }}</span>
                            </div>
                            <div class="flex justify-between">
                                <span class="text-gray-600">Saldo Pendente:</span>
                                <span class="font-bold text-orange-600">R$ {{ number_format($grupo->sum('valor_saldo'), 2, ',', '.') }}</span>
                            </div>
                        </div>
                    </div>
                @endif
            @endforeach
        </div>

        <footer class="pt-3 border-t border-gray-200 text-center">
            <p class="text-xs text-gray-500">Este relatório é apenas para fins gerenciais e não tem valor fiscal</p>
            <p class="text-xs text-gray-400">Gerado em {{ now()->format('d/m/Y \à\s H:i:s') }} por {{ auth()->user()->name ?? 'Sistema' }}</p>
        </footer>
    </div>

    <script>window.print();</script>
</body>
</html>
