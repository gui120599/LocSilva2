<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Orçamento {{ $orcamento->numero }}</title>
    <link rel="preconnect" href="https://fonts.bunny.net">
    <link href="https://fonts.bunny.net/css?family=instrument-sans:400,500,600" rel="stylesheet" />
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    @endif
</head>

<body>
    <div class="p-1 max-w-6xl mx-auto ring-1 ring-gray-100/70">

        <!-- Header -->
        <header class="flex flex-col sm:flex-row justify-between items-start sm:items-center border-b border-gray-100 pb-2 mb-2">
            <div class="space-y-1">
                <h2 class="font-extrabold text-gray-800">
                    Orçamento <span class="text-primary-600">{{ $orcamento->numero }}</span>
                </h2>
                <p class="text-sm text-gray-500">
                    Emitido em: {{ $orcamento->created_at->format('d/m/Y \à\s H:i') }}
                </p>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-gray-100 text-gray-700">
                    Status: {{ $orcamento->status->getLabel() }}
                </span>
            </div>
            <div>
                <img src="{{ asset('/logos/Logo LocSilva white.png') }}" alt="Logo LocSilva" class="h-14 mt-4 sm:mt-0 opacity-85">
            </div>
        </header>

        <!-- Dados principais -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 mb-2">

            <!-- Cliente -->
            <div class="p-3 border border-gray-100 bg-gray-50/50 rounded-xl">
                <h3 class="text-base font-bold text-gray-700 mb-1 flex items-center">
                    <x-heroicon-s-user class="w-4 h-4 mr-2 text-primary-500" /> Cliente
                </h3>
                <div class="space-y-1 text-[9px] text-gray-600">
                    <p><strong>Nome: </strong> {{ $orcamento->nome_cliente ?? $orcamento->cliente?->nome ?? '—' }}</p>
                    <p><strong>Telefone: </strong> {{ $orcamento->telefone_cliente ?? $orcamento->cliente?->telefone ?? '—' }}</p>
                    @if ($orcamento->cliente)
                        <p><strong>CPF/CNPJ: </strong> {{ $orcamento->cliente->cpf_cnpj ?? '—' }}</p>
                        <p><strong>Endereço: </strong> {{ $orcamento->cliente->endereco ?? 'Não informado' }}</p>
                    @endif
                </div>
            </div>

            <!-- Empresa -->
            <div class="p-3 border border-gray-100 bg-gray-50/50 rounded-xl">
                <h3 class="text-base font-bold text-gray-700 mb-1 flex items-center">
                    <x-heroicon-s-building-office-2 class="w-4 h-4 mr-2 text-primary-500" /> Prestador
                </h3>
                <div class="space-y-1 text-[9px] text-gray-600">
                    <p><strong>Empresa: </strong> 22.341.672 IVAN DE AQUINO SILVA - ME</p>
                    <p><strong>CNPJ: </strong> 22.341.672/0001-01</p>
                    <p><strong>Telefone: </strong> (62) 9 9323-9697</p>
                    <p><strong>Endereço: </strong> R. Maria Conceição, 245, Parque Amazonia, Goiânia - GO, 74840-750</p>
                </div>
            </div>

            <!-- Veículo -->
            <div class="p-3 border border-gray-100 bg-gray-50/50 rounded-xl">
                <h3 class="text-base font-bold text-gray-700 mb-1 flex items-center">
                    <x-heroicon-s-truck class="w-4 h-4 mr-2 text-primary-500" /> Veículo
                </h3>
                <div class="space-y-1 text-[9px] text-gray-600">
                    <p><strong>Descrição: </strong> {{ $orcamento->veiculo_descricao ?? '—' }}</p>
                    <p><strong>Placa: </strong>
                        @if ($orcamento->veiculo_placa)
                            <span class="font-mono bg-blue-100 text-blue-800 px-1.5 py-0.5 rounded">{{ $orcamento->veiculo_placa }}</span>
                        @else
                            —
                        @endif
                    </p>
                    @if ($orcamento->data_validade)
                        <p><strong>Válido até: </strong> {{ $orcamento->data_validade->format('d/m/Y') }}</p>
                    @endif
                </div>
            </div>
        </div>

        <!-- Itens do Orçamento -->
        <div class="p-2 border border-gray-200 bg-white rounded-xl mb-2">
            <h5 class="font-bold text-gray-800 mb-2 flex items-center">
                <x-heroicon-s-clipboard-document-list class="w-5 h-5 mr-2 text-primary-600" /> Itens do Orçamento
            </h5>
            <table class="w-full text-[9px] border-collapse">
                <thead>
                    <tr class="bg-gray-50 text-gray-600">
                        <th class="text-left px-2 py-1 border border-gray-100">Tipo</th>
                        <th class="text-left px-2 py-1 border border-gray-100">Descrição</th>
                        <th class="text-right px-2 py-1 border border-gray-100">Qtd.</th>
                        <th class="text-right px-2 py-1 border border-gray-100">Valor Unit.</th>
                        <th class="text-right px-2 py-1 border border-gray-100">Desconto</th>
                        <th class="text-right px-2 py-1 border border-gray-100">Total</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($orcamento->itens as $item)
                        <tr class="border-b border-gray-100">
                            <td class="px-2 py-1 border border-gray-100 capitalize">
                                {{ $item->tipo instanceof \App\Enums\TipoItem ? $item->tipo->getLabel() : $item->tipo }}
                            </td>
                            <td class="px-2 py-1 border border-gray-100">{{ $item->descricao }}</td>
                            <td class="px-2 py-1 border border-gray-100 text-right">
                                {{ number_format((float) $item->quantidade, 2, ',', '.') }}
                            </td>
                            <td class="px-2 py-1 border border-gray-100 text-right">
                                R$ {{ number_format((float) $item->valor_unitario, 2, ',', '.') }}
                            </td>
                            <td class="px-2 py-1 border border-gray-100 text-right">
                                R$ {{ number_format((float) ($item->valor_desconto ?? 0), 2, ',', '.') }}
                            </td>
                            <td class="px-2 py-1 border border-gray-100 text-right font-semibold text-green-700">
                                R$ {{ number_format((float) $item->valor_total, 2, ',', '.') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-2 py-2 text-center text-gray-400 italic">Nenhum item adicionado.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Resumo Financeiro -->
        <div class="p-2 border border-gray-200 bg-primary-50/30 rounded-xl mb-2">
            <h5 class="font-bold text-gray-800 mb-2 flex items-center">
                <x-heroicon-s-currency-dollar class="w-5 h-5 mr-2 text-primary-600" /> Resumo Financeiro
            </h5>
            <dl class="space-y-1 text-[10px] max-w-xs ml-auto">
                <div class="flex justify-between text-gray-700">
                    <dt>Subtotal dos Itens:</dt>
                    <dd>R$ {{ number_format((float) ($orcamento->valor_subtotal ?? 0), 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between text-gray-700">
                    <dt>(+) Acréscimos:</dt>
                    <dd>R$ {{ number_format((float) ($orcamento->valor_acrescimo ?? 0), 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between text-gray-700">
                    <dt>(-) Descontos:</dt>
                    <dd>R$ {{ number_format((float) ($orcamento->valor_desconto ?? 0), 2, ',', '.') }}</dd>
                </div>
                <div class="flex justify-between font-bold text-xs pt-1 border-t border-gray-200">
                    <dt class="text-gray-800">VALOR TOTAL:</dt>
                    <dd class="text-green-600">R$ {{ number_format((float) ($orcamento->valor_total ?? 0), 2, ',', '.') }}</dd>
                </div>
            </dl>
        </div>

        <!-- Pagamentos (se houver) -->
        @if ($orcamento->movimentos->count())
            <div class="p-2 border border-gray-200 bg-white rounded-xl mb-2">
                <h5 class="font-bold text-gray-700 mb-2 flex items-center">
                    <x-heroicon-s-banknotes class="w-5 h-5 mr-2 text-primary-500" /> Pagamentos / Adiantamentos
                </h5>
                <ul class="space-y-1 text-[10px]">
                    @foreach ($orcamento->movimentos as $pagamento)
                        <li class="flex justify-between items-center border-b border-gray-100 pb-1 last:border-b-0">
                            <span class="text-gray-600">
                                {{ $pagamento->created_at->format('d/m/Y') }} —
                                {{ $pagamento->metodoPagamento->nome ?? 'Método não especificado' }}
                                @if ($pagamento->descricao) — {{ $pagamento->descricao }} @endif
                            </span>
                            <span class="font-semibold text-green-700">
                                R$ {{ number_format((float) $pagamento->valor_total_movimento, 2, ',', '.') }}
                            </span>
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <!-- Observações -->
        @if ($orcamento->observacoes)
            <div class="p-2 border border-gray-200 bg-white rounded-xl mb-2">
                <h5 class="font-bold text-gray-700 mb-1 flex items-center">
                    <x-heroicon-s-chat-bubble-left-ellipsis class="w-5 h-5 mr-2 text-gray-500" /> Observações
                </h5>
                <p class="text-[10px] text-gray-600 whitespace-pre-line">{{ $orcamento->observacoes }}</p>
            </div>
        @endif

        <!-- Assinaturas -->
        <div class="grid grid-cols-2 gap-12 text-center mt-6">
            <div>
                <div class="border-b border-gray-400 h-16 w-3/4 mx-auto mb-2"></div>
                <p class="font-semibold text-gray-800 text-sm">
                    {{ $orcamento->nome_cliente ?? $orcamento->cliente?->nome ?? 'Cliente' }}
                </p>
                <p class="text-xs text-gray-500">Contratante</p>
            </div>
            <div>
                <div class="border-b border-gray-400 h-16 w-3/4 mx-auto mb-2"></div>
                <p class="font-semibold text-gray-800 text-sm">22.341.672 IVAN DE AQUINO SILVA - ME</p>
                <p class="text-xs text-gray-500">Prestador (22.341.672/0001-01)</p>
            </div>
        </div>

        <footer class="mt-6 pt-4 border-t border-gray-100 text-center">
            <p class="text-xs text-gray-400">
                Documento gerado em {{ now()->format('d/m/Y \à\s H:i:s') }} | LocSilva Serviços
            </p>
        </footer>

    </div>
    <script>
        window.print();
    </script>
</body>
</html>
