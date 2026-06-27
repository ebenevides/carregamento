<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('eventos_ordem_carregamento', function (Blueprint $table) {
            $table->id();
            $table->uuid('ordem_carregamento_id');
            $table->foreign('ordem_carregamento_id')->references('id')->on('ordens_carregamento');

            $table->string('tipo', 50);
            $table->string('status_anterior', 40)->nullable();
            $table->string('status_novo', 40)->nullable();
            $table->string('origem', 30);
            $table->foreignId('usuario_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('usuario_nome', 100)->nullable();
            $table->text('observacao')->nullable();
            $table->jsonb('payload')->nullable();

            $table->timestamp('ocorrido_em')->useCurrent();
            $table->timestamps();

            $table->index('ordem_carregamento_id');
            $table->index('tipo');
            $table->index('ocorrido_em');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('eventos_ordem_carregamento');
    }
};
