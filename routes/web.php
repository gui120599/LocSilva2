<?php

use App\Http\Controllers\AluguelController;
use App\Http\Controllers\CaixaController;
use App\Http\Controllers\OrcamentoController;
use App\Http\Controllers\OrdemServicoController;
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
    
    // Página de filtros
    Route::get('/alugueis/filtros', [AluguelController::class, 'filtrosAlugueis'])
        ->name('relatorios.filtros-alugueis');
    
    // Gerar relatório (POST para evitar URL muito longa)
    Route::post('/alugueis/gerar', [AluguelController::class, 'gerarRelatorioAlugueis'])
        ->name('relatorios.gerar-alugueis');
    
    // Exportar PDF (opcional)
    Route::post('/alugueis/exportar-pdf', [AluguelController::class, 'exportarPdfAlugueis'])
        ->name('relatorios.exportar-pdf-alugueis');
    
    // Exportar Excel (opcional)
    Route::post('/alugueis/exportar-excel', [AluguelController::class, 'exportarExcelAlugueis'])
        ->name('relatorios.exportar-excel-alugueis');
});

Route::get('/laravel/login', fn() => redirect(route('filament.admin.auth.login')))->name('login');
