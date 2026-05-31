<?php

namespace App\Models;

use App\Enums\TipoItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemOrcamento extends Model
{
    protected $table = 'itens_orcamentos';

    protected $fillable = [
        'orcamento_id',
        'tipo',
        'servico_id',
        'produto_id',
        'descricao',
        'quantidade',
        'valor_unitario',
        'valor_desconto',
        'valor_total',
        'observacoes',
    ];

    protected $casts = [
        'tipo'          => TipoItem::class,
        'quantidade'    => 'decimal:3',
        'valor_unitario' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
        'valor_total'   => 'decimal:2',
    ];

    /**
     * Um item pertence a um orçamento
     */
    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_id');
    }

    /**
     * Um item pode referenciar um serviço do catálogo
     */
    public function servico(): BelongsTo
    {
        return $this->belongsTo(Servico::class, 'servico_id');
    }

    /**
     * Um item pode referenciar um produto do catálogo
     */
    public function produto(): BelongsTo
    {
        return $this->belongsTo(Produto::class, 'produto_id');
    }

    /**
     * Calcula o valor total do item (quantidade × unitário - desconto)
     */
    public function calcularValorTotal(): float
    {
        return ($this->quantidade * $this->valor_unitario) - ($this->valor_desconto ?? 0);
    }
}
