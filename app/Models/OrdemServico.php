<?php

namespace App\Models;

use App\Enums\StatusOrdemServico;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class OrdemServico extends Model
{
    use SoftDeletes;

    protected $table = 'ordens_servicos';

    protected $fillable = [
        'numero',
        'orcamento_id',
        'cliente_id',
        'nome_cliente',
        'telefone_cliente',
        'veiculo_descricao',
        'veiculo_placa',
        'tecnico_id',
        'user_id',
        'status',
        'data_abertura',
        'data_previsao_conclusao',
        'data_conclusao',
        'valor_subtotal',
        'valor_desconto',
        'valor_acrescimo',
        'valor_total',
        'valor_pago',
        'valor_saldo',
        'observacoes',
        'observacoes_tecnicas',
    ];

    protected $casts = [
        'status'                  => StatusOrdemServico::class,
        'data_abertura'           => 'datetime',
        'data_previsao_conclusao' => 'datetime',
        'data_conclusao'          => 'datetime',
        'valor_subtotal'          => 'decimal:2',
        'valor_desconto'          => 'decimal:2',
        'valor_acrescimo'         => 'decimal:2',
        'valor_total'             => 'decimal:2',
        'valor_pago'              => 'decimal:2',
        'valor_saldo'             => 'decimal:2',
    ];

    /**
     * Uma OS pode ter sido originada de um orçamento
     */
    public function orcamento(): BelongsTo
    {
        return $this->belongsTo(Orcamento::class, 'orcamento_id');
    }

    /**
     * Uma OS pode pertencer a um cliente cadastrado
     */
    public function cliente(): BelongsTo
    {
        return $this->belongsTo(Cliente::class, 'cliente_id');
    }

    /**
     * Técnico responsável pela execução da OS
     */
    public function tecnico(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tecnico_id');
    }

    /**
     * Usuário que abriu a OS
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Uma OS tem vários itens (serviços e produtos)
     */
    public function itens(): HasMany
    {
        return $this->hasMany(ItemOrdemServico::class, 'ordem_servico_id');
    }

    /**
     * Uma OS pode ter vários movimentos de caixa (recebimentos)
     */
    public function movimentos(): HasMany
    {
        return $this->hasMany(MovimentoCaixa::class, 'ordem_servico_id');
    }

    /**
     * Movimentações de estoque geradas por esta OS
     */
    public function movimentosEstoque(): HasMany
    {
        return $this->hasMany(MovimentoEstoque::class, 'ordem_servico_id');
    }

    /**
     * Calcula o total já pago via movimentos de caixa
     */
    public function getTotalPagoAttribute(): float
    {
        return $this->movimentos()
            ->where('tipo', 'entrada')
            ->sum('valor_total_movimento');
    }

    /**
     * Calcula o saldo restante
     */
    public function getSaldoAttribute(): float
    {
        return max(0, $this->valor_total - $this->total_pago);
    }

    /**
     * Verifica se está totalmente paga
     */
    public function isPaga(): bool
    {
        return $this->valor_saldo <= 0;
    }

    /**
     * Verifica se está em atraso
     */
    public function isAtrasada(): bool
    {
        $statusEmAberto = [
            StatusOrdemServico::Aberta,
            StatusOrdemServico::EmAndamento,
            StatusOrdemServico::AguardandoPecas,
        ];

        if (!in_array($this->status, $statusEmAberto)) {
            return false;
        }

        return !is_null($this->data_previsao_conclusao)
            && $this->data_previsao_conclusao->isPast();
    }

    /**
     * Conclui a OS
     */
    public function concluir(): void
    {
        $this->update([
            'status'         => StatusOrdemServico::Concluida,
            'data_conclusao' => now(),
        ]);
    }

    /**
     * Cancela a OS
     */
    public function cancelar(?string $motivo = null): void
    {
        $observacoes = $this->observacoes ?? '';

        if ($motivo) {
            $observacoes .= "\n\nCancelado em " . now()->format('d/m/Y H:i') . ": {$motivo}";
        }

        $this->update([
            'status'      => StatusOrdemServico::Cancelada,
            'observacoes' => $observacoes,
        ]);
    }
}
