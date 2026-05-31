<?php

namespace App\Models;

use App\Enums\MotivoMovimentoEstoque;
use App\Enums\TipoMovimentoEstoque;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MovimentoEstoque extends Model
{
    use SoftDeletes;

    protected $table = 'movimentos_estoques';

    protected $fillable = [
        'produto_id',
        'ordem_servico_id',
        'user_id',
        'tipo',
        'motivo',
        'quantidade',
        'valor_unitario',
        'observacoes',
    ];

    protected $casts = [
        'tipo'           => TipoMovimentoEstoque::class,
        'motivo'         => MotivoMovimentoEstoque::class,
        'quantidade'     => 'decimal:3',
        'valor_unitario' => 'decimal:2',
    ];

    /**
     * Um movimento pertence a um produto
     */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    /**
     * Um movimento pode estar vinculado a uma ordem de serviço
     */
    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
    }

    /**
     * Um movimento pertence a um usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Verifica se é uma entrada de estoque
     */
    public function isEntrada(): bool
    {
        return $this->tipo === TipoMovimentoEstoque::Entrada;
    }

    /**
     * Verifica se é uma saída de estoque
     */
    public function isSaida(): bool
    {
        return $this->tipo === TipoMovimentoEstoque::Saida;
    }
}
