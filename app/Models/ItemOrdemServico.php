<?php

namespace App\Models;

use App\Enums\TipoItem;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ItemOrdemServico extends Model
{
    protected $table = 'itens_ordens_servicos';

    protected $fillable = [
        'ordem_servico_id',
        'tipo',
        'servico_id',
        'produto_id',
        'descricao',
        'quantidade',
        'valor_unitario',
        'valor_desconto',
        'valor_total',
        'concluido',
        'observacoes',
    ];

    protected $casts = [
        'tipo'           => TipoItem::class,
        'quantidade'     => 'decimal:3',
        'valor_unitario'  => 'decimal:2',
        'valor_desconto'  => 'decimal:2',
        'valor_total'     => 'decimal:2',
        'concluido'      => 'boolean',
    ];

    protected $attributes = [
        'concluido' => false,
    ];

    /**
     * Um item pertence a uma ordem de serviço
     */
    public function ordemServico(): BelongsTo
    {
        return $this->belongsTo(OrdemServico::class, 'ordem_servico_id');
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
     * Calcula o valor total do item
     */
    public function calcularValorTotal(): float
    {
        return $this->quantidade * $this->valor_unitario;
    }

    /**
     * Marca o item como concluído
     */
    public function marcarComoConcluido(): void
    {
        $this->update(['concluido' => true]);
    }
}
