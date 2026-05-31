<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('itens_orcamentos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('orcamento_id')->constrained('orcamentos')->cascadeOnDelete();
            $table->string('tipo')->default('servico');
            $table->foreignId('servico_id')->nullable()->constrained('servicos')->nullOnDelete();
            $table->foreignId('produto_id')->nullable()->constrained('produtos')->nullOnDelete();
            $table->string('descricao');
            $table->decimal('quantidade', 10, 3)->default(1);
            $table->decimal('valor_unitario', 10, 2)->default(0);
            $table->decimal('valor_acrescimo', 10, 2)->default(0);
            $table->decimal('valor_desconto', 10, 2)->default(0);
            $table->decimal('valor_total', 10, 2)->default(0);
            $table->text('observacoes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('itens_orcamentos');
    }
};
