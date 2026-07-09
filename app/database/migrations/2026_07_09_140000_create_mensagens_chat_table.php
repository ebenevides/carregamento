<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mensagens_chat', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('ordem_carregamento_id')
                ->constrained('ordens_carregamento')
                ->cascadeOnDelete();
            $table->foreignId('remetente_id')
                ->constrained('users');
            $table->string('perfil_remetente', 40);
            $table->text('mensagem');
            $table->timestamp('lida_em')->nullable();
            $table->timestamps();

            $table->index('ordem_carregamento_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mensagens_chat');
    }
};
