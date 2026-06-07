<?php

use App\Http\Controllers\AluguelController;
use App\Http\Controllers\CaixaController;
use App\Http\Controllers\OrcamentoController;
use App\Http\Controllers\OrdemServicoController;
use App\Http\Controllers\RelatorioController;
use Illuminate\Support\Facades\Route;


Route::middleware(['auth'])->group(function () {

    Route::get('/print-aluguel/{id}', [AluguelController::class, 'printAluguel'])->name('print-aluguel');

    Route::get('/print-caixa/{id}', [CaixaController::class, 'printCaixa'])->name('print-caixa');

    Route::get('/print-retirada/{id}', [AluguelController::class, 'printRetirada'])->name('print-retirada');

    Route::get('/print-checklist/{id}', [AluguelController::class, 'printChecklist'])->name('print-checklist');

    Route::get('/print-devolucao/{id}', [AluguelController::class, 'printDevolucao'])->name('print-devolucao');

    Route::get('/print-orcamento/{id}', [OrcamentoController::class, 'printOrcamento'])->name('print-orcamento');

    Route::get('/print-os/{id}', [OrdemServicoController::class, 'printOrdemServico'])->name('print-os');

});

Route::middleware(['auth'])->prefix('relatorios')->group(function () {

    // Alugueis
    Route::get('/alugueis/gerar', [AluguelController::class, 'gerarRelatorioAlugueis'])
        ->name('relatorios.gerar-alugueis');

    // Orçamentos
    Route::get('/orcamentos/gerar', [RelatorioController::class, 'gerarRelatorioOrcamentos'])
        ->name('relatorios.gerar-orcamentos');

    // Ordens de Serviço
    Route::get('/ordensservicos/gerar', [RelatorioController::class, 'gerarRelatorioOrdensServicos'])
        ->name('relatorios.gerar-ordensservicos');
});

Route::get('/laravel/login', fn() => redirect(route('filament.admin.auth.login')))->name('login');
