<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('movimentos_caixas', function (Blueprint $table) {
            $table->foreignId('orcamento_id')
                ->nullable()
                ->after('aluguel_id')
                ->constrained('orcamentos')
                ->nullOnDelete();

            $table->foreignId('ordem_servico_id')
                ->nullable()
                ->after('orcamento_id')
                ->constrained('ordens_servicos')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('movimentos_caixas', function (Blueprint $table) {
            $table->dropForeign(['orcamento_id']);
            $table->dropForeign(['ordem_servico_id']);
            $table->dropColumn(['orcamento_id', 'ordem_servico_id']);
        });
    }
};
