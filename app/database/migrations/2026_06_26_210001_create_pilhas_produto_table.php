<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pilhas_produto', function (Blueprint $table) {
            $table->id();
            $table->string('codigo', 20)->unique();
            $table->string('descricao', 100);
            $table->string('produto_codigo', 30)->nullable();
            $table->string('produto_descricao', 100)->nullable();
            $table->boolean('ativa')->default(true);
            $table->text('observacao')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('produto_codigo');
            $table->index('ativa');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pilhas_produto');
    }
};
