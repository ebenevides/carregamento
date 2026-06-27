<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('divergencias_carregamento', function (Blueprint $table) {
            $table->id();
            $table->uuid('ordem_carregamento_id');
            $table->foreign('ordem_carregamento_id')->references('id')->on('ordens_carregamento');

            $table->string('tipo', 40);
            $table->string('status', 20)->default('ABERTA');
            $table->string('origem', 30);
            $table->text('descricao');

            $table->foreignId('registrado_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('registrado_por_nome', 100)->nullable();

            $table->foreignId('resolvido_por')->nullable()->constrained('users')->nullOnDelete();
            $table->string('resolvido_por_nome', 100)->nullable();
            $table->text('resolucao')->nullable();
            $table->timestamp('resolvido_em')->nullable();

            $table->timestamps();

            $table->index('ordem_carregamento_id');
            $table->index('status');
            $table->index('tipo');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('divergencias_carregamento');
    }
};
