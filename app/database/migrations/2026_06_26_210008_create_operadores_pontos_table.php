<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('operadores_pontos', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('ponto_carregamento_id')->constrained('pontos_carregamento')->cascadeOnDelete();
            $table->boolean('padrao')->default(false);
            $table->timestamps();

            $table->unique(['user_id', 'ponto_carregamento_id']);
            $table->index('ponto_carregamento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('operadores_pontos');
    }
};
