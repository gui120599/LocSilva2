<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Servico extends Model
{
    use SoftDeletes;

    protected $table = 'servicos';

    protected $fillable = [
        'nome',
        'foto',
        'descricao',
        'unidade',
        'valor_padrao',
        'observacoes',
    ];

    protected $casts = [
        'valor_padrao' => 'decimal:2',
    ];

    /**
     * Um serviço pode estar em vários itens de orçamento
     */
    public function itensOrcamento(): HasMany
    {
        return $this->hasMany(ItemOrcamento::class, 'servico_id');
    }

    /**
     * Um serviço pode estar em vários itens de ordem de serviço
     */
    public function itensOrdemServico(): HasMany
    {
        return $this->hasMany(ItemOrdemServico::class, 'servico_id');
    }
}
