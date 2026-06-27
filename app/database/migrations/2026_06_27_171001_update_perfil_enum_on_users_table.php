<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')->where('perfil', 'ADMINISTRADOR')->update(['perfil' => 'ADMIN']);
        DB::table('users')->where('perfil', 'SUPERVISOR')->update(['perfil' => 'EXPEDICAO']);
        DB::table('users')->whereIn('perfil', ['VISUALIZADOR', 'SISTEMA'])->update(['perfil' => 'MOTORISTA']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('perfil', 20)->default('MOTORISTA')->change();
        });
    }

    public function down(): void
    {
        DB::table('users')->where('perfil', 'ADMIN')->update(['perfil' => 'ADMINISTRADOR']);
        DB::table('users')->where('perfil', 'EXPEDICAO')->update(['perfil' => 'SUPERVISOR']);
        DB::table('users')->where('perfil', 'MOTORISTA')->update(['perfil' => 'VISUALIZADOR']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('perfil', 20)->default('VISUALIZADOR')->change();
        });
    }
};
