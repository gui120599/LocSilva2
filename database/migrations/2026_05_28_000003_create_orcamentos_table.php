<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orcamentos', function (Blueprint $table) {
            $table->id();
            $table->string('numero')->unique();
            $table->foreignId('cliente_id')->nullable()->constrained('clientes')->nullOnDelete();
            $table->string('nome_cliente')->nullable();
            $table->string('telefone_cliente')->nullable();
            $table->string('veiculo_descricao');
            $table->string('veiculo_placa')->nullable();
            $table->string('status')->default('rascunho');
            $table->date('data_validade')->nullable();
            $table->decimal('valor_subtotal', 10, 2)->default(0);
            $table->decimal('valor_desconto', 10, 2)->default(0);
            $table->decimal('valor_acrescimo', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->timestamp('aprovado_em')->nullable();
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orcamentos');
    }
};
