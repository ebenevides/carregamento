<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pontos_carregamento', function (Blueprint $table) {
            $table->string('unidade_britagem', 10)->nullable()->after('descricao');

            $table->index('unidade_britagem');
        });
    }

    public function down(): void
    {
        Schema::table('pontos_carregamento', function (Blueprint $table) {
            $table->dropIndex(['unidade_britagem']);
            $table->dropColumn('unidade_britagem');
        });
    }
};
