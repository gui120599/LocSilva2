<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordens_servicos', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('orcamento_id')->nullable()->constrained('orcamentos')->nullOnDelete();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('nome_cliente')->nullable();
            $table->string('telefone_cliente')->nullable();
            $table->string('veiculo_descricao');
            $table->string('veiculo_placa')->nullable();
            $table->foreignId('tecnico_id')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('status')->default('aberta');
            $table->timestamp('data_abertura')->useCurrent();
            $table->timestamp('data_previsao_conclusao')->nullable();
            $table->timestamp('data_conclusao')->nullable();
            $table->decimal('valor_subtotal', 10, 2)->default(0);
            $table->decimal('valor_desconto', 10, 2)->default(0);
            $table->decimal('valor_acrescimo', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->decimal('valor_pago', 10, 2)->default(0);
            $table->decimal('valor_saldo', 10, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->text('observacoes_tecnicas')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordens_servicos');
    }
};
