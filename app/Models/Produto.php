<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Produto extends Model
{
    use SoftDeletes;

    protected $table = 'produtos';

    protected $fillable = [
        'nome',
        'foto',
        'descricao',
        'unidade',
        'valor_unitario',
        'estoque_atual',
        'estoque_minimo',
        'observacoes',
    ];

    protected $casts = [
        'valor_unitario' => 'decimal:2',
        'estoque_atual'  => 'decimal:3',
        'estoque_minimo' => 'decimal:3',
    ];

    /**
     * Movimentações de estoque do produto
     */
    public function movimentosEstoque(): HasMany
    {
        return $this->hasMany(MovimentoEstoque::class, 'produto_id');
    }

    /**
     * Um produto pode estar em vários itens de orçamento
     */
    public function itensOrcamento(): HasMany
    {
        return $this->hasMany(ItemOrcamento::class, 'produto_id');
    }

    /**
     * Um produto pode estar em vários itens de ordem de serviço
     */
    public function itensOrdemServico(): HasMany
    {
        return $this->hasMany(ItemOrdemServico::class, 'produto_id');
    }

    /**
     * Verifica se o estoque está abaixo do mínimo
     */
    public function isEstoqueBaixo(): bool
    {
        if (is_null($this->estoque_minimo)) {
            return false;
        }

        return $this->estoque_atual <= $this->estoque_minimo;
    }

    /**
     * Recalcula e salva o estoque atual com base nas movimentações
     */
    public function recalcularEstoque(): void
    {
        $entradas = $this->movimentosEstoque()->where('tipo', 'entrada')->sum('quantidade');
        $saidas   = $this->movimentosEstoque()->where('tipo', 'saida')->sum('quantidade');

        $this->update(['estoque_atual' => $entradas - $saidas]);
    }
}
