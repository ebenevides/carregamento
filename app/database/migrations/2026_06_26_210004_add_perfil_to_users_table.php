<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('perfil', 20)->default('VISUALIZADOR')->after('email');
            $table->foreignId('ponto_carregamento_id')->nullable()->after('perfil')->constrained('pontos_carregamento')->nullOnDelete();
            $table->boolean('ativo')->default(true)->after('ponto_carregamento_id');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['perfil', 'ponto_carregamento_id', 'ativo']);
        });
    }
};
