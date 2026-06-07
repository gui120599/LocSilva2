<?php

namespace App\Http\Controllers;

use App\Enums\StatusOrcamento;
use App\Enums\StatusOrdemServico;
use App\Models\Cliente;
use App\Models\Orcamento;
use App\Models\OrdemServico;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class RelatorioController extends Controller
{
    public function gerarRelatorioOrcamentos(Request $request): View
    {
        $request->validate([
            'data_inicio' => 'required|date',
            'data_fim'    => 'required|date|after_or_equal:data_inicio',
            'tipo_data'   => 'nullable|in:criacao,validade',
            'status'      => 'nullable|string',
            'cliente_id'  => 'nullable|exists:clientes,id',
        ]);

        $query = Orcamento::with(['cliente', 'itens']);

        $tipoData  = $request->input('tipo_data', 'criacao');
        $dataInicio = Carbon::parse($request->data_inicio)->startOfDay();
        $dataFim    = Carbon::parse($request->data_fim)->endOfDay();

        match ($tipoData) {
            'validade' => $query->whereBetween('data_validade', [$dataInicio, $dataFim]),
            default    => $query->whereBetween('created_at',    [$dataInicio, $dataFim]),
        };

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        $orcamentos = $query->orderBy('created_at', 'desc')->get();

        $filtros = [
            'data_inicio'  => $request->data_inicio,
            'data_fim'     => $request->data_fim,
            'tipo_data'    => $tipoData,
            'status'       => $request->status,
            'cliente_id'   => $request->cliente_id,
            'cliente_nome' => $request->filled('cliente_id')
                ? Cliente::find($request->cliente_id)?->nome
                : null,
        ];

        return view('relatorio-orcamentos', compact('orcamentos', 'filtros'));
    }

    public function gerarRelatorioOrdensServicos(Request $request): View
    {
        $request->validate([
            'data_inicio' => 'required|date',
            'data_fim'    => 'required|date|after_or_equal:data_inicio',
            'tipo_data'   => 'nullable|in:abertura,previsao,conclusao,criacao',
            'status'      => 'nullable|string',
            'cliente_id'  => 'nullable|exists:clientes,id',
            'tecnico_id'  => 'nullable|exists:users,id',
        ]);

        $query = OrdemServico::with(['cliente', 'tecnico', 'itens', 'movimentos']);

        $tipoData   = $request->input('tipo_data', 'abertura');
        $dataInicio = Carbon::parse($request->data_inicio)->startOfDay();
        $dataFim    = Carbon::parse($request->data_fim)->endOfDay();

        match ($tipoData) {
            'previsao'  => $query->whereBetween('data_previsao_conclusao', [$dataInicio, $dataFim]),
            'conclusao' => $query->whereBetween('data_conclusao',          [$dataInicio, $dataFim]),
            'criacao'   => $query->whereBetween('created_at',              [$dataInicio, $dataFim]),
            default     => $query->whereBetween('data_abertura',           [$dataInicio, $dataFim]),
        };

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('cliente_id')) {
            $query->where('cliente_id', $request->cliente_id);
        }

        if ($request->filled('tecnico_id')) {
            $query->where('tecnico_id', $request->tecnico_id);
        }

        $ordens = $query->orderBy('data_abertura', 'desc')->get();

        $filtros = [
            'data_inicio'   => $request->data_inicio,
            'data_fim'      => $request->data_fim,
            'tipo_data'     => $tipoData,
            'status'        => $request->status,
            'cliente_id'    => $request->cliente_id,
            'cliente_nome'  => $request->filled('cliente_id')
                ? Cliente::find($request->cliente_id)?->nome
                : null,
            'tecnico_id'    => $request->tecnico_id,
            'tecnico_nome'  => $request->filled('tecnico_id')
                ? User::find($request->tecnico_id)?->name
                : null,
        ];

        return view('relatorio-ordensservicos', compact('ordens', 'filtros'));
    }
}
