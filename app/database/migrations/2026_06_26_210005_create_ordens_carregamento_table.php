<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ordens_carregamento', function (Blueprint $table) {
            $table->uuid('id')->primary();

            // Dados Protheus
            $table->string('empresa', 10)->nullable();
            $table->string('filial', 10)->nullable();
            $table->string('pedido_numero', 20)->nullable();
            $table->string('pedido_item', 10)->nullable();
            $table->string('contrato_codigo', 20)->nullable();

            // Guardian
            $table->string('ticket_guardian', 20)->nullable()->unique();

            // Cliente
            $table->string('cliente_codigo', 20)->nullable();
            $table->string('cliente_loja', 10)->nullable();
            $table->string('cliente_nome', 150)->nullable();

            // Produto
            $table->string('produto_codigo', 30);
            $table->string('produto_descricao', 100)->nullable();
            $table->decimal('quantidade_prevista', 10, 3);
            $table->string('unidade', 10)->default('TN');

            // Veículo e motorista
            $table->string('placa_veiculo', 10);
            $table->string('placa_carreta', 10)->nullable();
            $table->string('motorista_nome', 100)->nullable();
            $table->string('motorista_documento', 20)->nullable();
            $table->string('transportadora_codigo', 20)->nullable();
            $table->string('transportadora_nome', 100)->nullable();

            // Pesagem
            $table->decimal('tara', 10, 3)->nullable();
            $table->decimal('peso_bruto', 10, 3)->nullable();
            $table->decimal('peso_liquido', 10, 3)->nullable();
            $table->decimal('tolerancia_percentual', 5, 2)->default(5.00);

            // Destino
            $table->foreignId('pilha_produto_id')->nullable()->constrained('pilhas_produto')->nullOnDelete();
            $table->foreignId('ponto_carregamento_id')->nullable()->constrained('pontos_carregamento')->nullOnDelete();
            $table->foreignId('equipamento_id')->nullable()->constrained('equipamentos')->nullOnDelete();

            // Operador responsável
            $table->foreignId('operador_id')->nullable()->constrained('users')->nullOnDelete();

            // Status e timestamps operacionais
            $table->string('status', 40)->default('CRIADO');
            $table->timestamp('iniciado_em')->nullable();
            $table->timestamp('concluido_em')->nullable();
            $table->timestamp('pesagem_final_em')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index('status');
            $table->index('placa_veiculo');
            $table->index('produto_codigo');
            $table->index(['ponto_carregamento_id', 'status']);
            $table->index('pedido_numero');
            $table->index('created_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ordens_carregamento');
    }
};
