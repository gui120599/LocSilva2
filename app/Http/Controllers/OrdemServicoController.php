<?php

namespace App\Http\Controllers;

use App\Models\OrdemServico;
use Illuminate\View\View;

class OrdemServicoController extends Controller
{
    public function printOrdemServico($id): View
    {
        $os = OrdemServico::with(['cliente', 'itens', 'movimentos.metodoPagamento', 'tecnico', 'orcamento'])->findOrFail($id);
        return view('print-os', compact('os'));
    }
}
