<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('produto_pilha_ponto', function (Blueprint $table) {
            $table->string('produto_codigo', 30)->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('produto_pilha_ponto', function (Blueprint $table) {
            $table->string('produto_codigo', 30)->nullable(false)->change();
        });
    }
};
