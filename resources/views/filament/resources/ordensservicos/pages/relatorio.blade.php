<x-filament-panels::page>
    <form wire:submit="gerarRelatorio">
        {{ $this->form }}

        <div class="mt-6 flex justify-end gap-3">
            <x-filament::button
                type="submit"
                size="lg"
                icon="heroicon-o-printer"
                color="primary"
            >
                Gerar Relatório
            </x-filament::button>
        </div>
    </form>

    <div class="mt-6 p-4 border border-blue-200 dark:border-blue-800 bg-blue-50 dark:bg-blue-950/30 rounded-xl">
        <h4 class="font-semibold text-blue-800 dark:text-blue-300 mb-2 flex items-center gap-2 text-sm">
            <x-heroicon-o-information-circle class="w-5 h-5" />
            Dicas de Uso
        </h4>
        <ul class="text-sm text-blue-700 dark:text-blue-400 space-y-1 list-disc list-inside">
            <li>Selecione o período e o tipo de data que deseja analisar</li>
            <li>Use os filtros opcionais para refinar sua busca</li>
            <li>O relatório será aberto em uma nova aba para impressão</li>
            <li>Deixe os filtros opcionais vazios para incluir todos os registros</li>
        </ul>
    </div>

    @script
    <script>
        $wire.on('abrir-relatorio', ({ url }) => {
            window.open(url, '_blank');
        });
    </script>
    @endscript
</x-filament-panels::page>
