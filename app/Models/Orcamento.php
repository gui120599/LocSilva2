<?php

namespace App\Models;

use App\Enums\StatusOrcamento;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use PhpParser\Node\Expr\Cast\Void_;

class Orcamento extends Model
{
    use SoftDeletes;

    protected $table = 'orcamentos';

    protected $fillable = [
        'numero',
        'cliente_id',
        'nome_cliente',
        'telefone_cliente',
        'veiculo_descricao',
        'veiculo_placa',
        'status',
        'data_validade',
        'valor_subtotal',
        'valor_desconto',
        'valor_acrescimo',
        'valor_total',
        'observacoes',
        'aprovado_em',
        'user_id',
    ];

    protected $casts = [
        'status'         => StatusOrcamento::class,
        'data_validade'  => 'date',
        'aprovado_em'    => 'datetime',
        'valor_subtotal' => 'decimal:2',
        'valor_desconto' => 'decimal:2',
        'valor_acrescimo' => 'decimal:2',
        'valor_total'    => 'decimal:2',
    ];

    /**
     * Um orçamento pode pertencer a um cliente cadastrado
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Um orçamento é criado por um usuário
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Um orçamento tem vários itens
     */
    public function itens(): HasMany
    {
        return $this->hasMany(ItemOrcamento::class, 'orcamento_id');
    }

    /**
     * Um orçamento pode ser convertido em uma ordem de serviço
     */
    public function ordemServico(): HasOne
    {
        return $this->hasOne(OrdemServico::class, 'orcamento_id');
    }

    /**
     * Um orçamento pode ter vários movimentos de caixa (pagamentos/adiantamentos)
     */
    public function movimentos(): HasMany
    {
        return $this->hasMany(MovimentoCaixa::class, 'orcamento_id');
    }

    /**
     * Calcula o valor total com base nos itens
     */
    public function calcularValorTotal(): float
    {
        $subtotal = $this->itens->sum('valor_total');

        return $subtotal
            + ($this->valor_acrescimo ?? 0)
            - ($this->valor_desconto ?? 0);
    }

    /**
     * Verifica se o orçamento está expirado
     */
    public function isExpirado(): bool
    {
        if (is_null($this->data_validade)) {
            return false;
        }

        return $this->data_validade->isPast()
            && $this->status === StatusOrcamento::AguardandoAprovacao;
    }

    /**
     * Verifica se pode ser convertido em OS
     */
    public function podeConverterEmOS(): bool
    {
        return $this->status === StatusOrcamento::Aprovado
            && is_null($this->ordemServico);
    }

    /**
     * Aprova o orçamento
     */
    public function aprovar(): void
    {
        $this->update([
            'status'      => StatusOrcamento::Aprovado,
            'aprovado_em' => now(),
        ]);
    }
    /**
     * Retorna o status para aprovado
     */
    public function aprovado(): void
    {
        $this->update([
            'status' => StatusOrcamento::Aprovado,
        ]);
    }

    

    /**
     * Reprova o orçamento
     */
    public function reprovar(): void
    {
        $this->update(['status' => StatusOrcamento::Reprovado]);
    }

    /**
     * Cancela o orçamento
     */
    public function cancelar(): void
    {
        $this->update(['status' => StatusOrcamento::Cancelado]);
    }

    /**
     * Marca como convertido em OS
     */
    public function marcarComoConvertido(): void
    {
        $this->update(['status' => StatusOrcamento::Convertido]);
    }
}
