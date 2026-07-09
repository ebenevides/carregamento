<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('ordens_carregamento', function (Blueprint $table) {
            $table->foreignId('motorista_user_id')
                ->nullable()
                ->after('operador_id')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('ordens_carregamento', function (Blueprint $table) {
            $table->dropConstrainedForeignId('motorista_user_id');
        });
    }
};
