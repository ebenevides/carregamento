<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('produto_pilha_ponto', function (Blueprint $table) {
            $table->id();
            $table->string('produto_codigo', 30);
            $table->string('produto_descricao', 100)->nullable();
            $table->foreignId('pilha_produto_id')->constrained('pilhas_produto');
            $table->foreignId('ponto_carregamento_id')->constrained('pontos_carregamento');
            $table->boolean('padrao')->default(false);
            $table->boolean('ativo')->default(true);
            $table->timestamps();

            $table->index('produto_codigo');
            $table->index(['produto_codigo', 'padrao']);
            $table->unique(['produto_codigo', 'pilha_produto_id', 'ponto_carregamento_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('produto_pilha_ponto');
    }
};
