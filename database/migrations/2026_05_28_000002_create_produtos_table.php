<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produtos', function (Blueprint $table) {
            $table->id();
            $table->string('foto')->nullable();
            $table->string('nome');
            $table->text('descricao')->nullable();
            $table->string('unidade')->default('un');
            $table->decimal('valor_unitario', 10, 2)->default(0);
            $table->decimal('estoque_atual', 10, 3)->default(0);
            $table->decimal('estoque_minimo', 10, 3)->nullable();
            $table->text('observacoes')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produtos');
    }
};
