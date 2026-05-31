<?php

namespace App\Http\Controllers;

use App\Models\Orcamento;
use Illuminate\View\View;

class OrcamentoController extends Controller
{
    public function printOrcamento($id): View
    {
        $orcamento = Orcamento::with(['cliente', 'itens', 'movimentos.metodoPagamento', 'user'])->findOrFail($id);
        return view('print-orcamento', compact('orcamento'));
    }
}
