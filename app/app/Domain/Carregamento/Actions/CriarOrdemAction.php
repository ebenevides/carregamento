<?php

namespace App\Domain\Carregamento\Actions;

use App\Domain\Carregamento\DTOs\AlterarStatusDTO;
use App\Domain\Carregamento\DTOs\CriarOrdemDTO;
use App\Domain\Carregamento\Enums\OrigemEvento;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Enums\TipoEvento;
use App\Domain\Carregamento\Models\EventoOrdemCarregamento;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Carregamento\Services\ResolverDestinoProdutoService;
use Illuminate\Support\Facades\DB;

class CriarOrdemAction
{
    public function __construct(
        private readonly ResolverDestinoProdutoService $resolverDestino,
        private readonly AlterarStatusOrdemAction $alterarStatus,
        private readonly RegistrarDivergenciaAction $registrarDivergencia,
    ) {}

    public function execute(CriarOrdemDTO $dto, OrigemEvento $origem = OrigemEvento::API): OrdemCarregamento
    {
        return DB::transaction(function () use ($dto, $origem) {
            $destino = $this->resolverDestino->resolver($dto->produtoCodigo);

            $ordem = OrdemCarregamento::create([
                'empresa'               => $dto->empresa,
                'filial'                => $dto->filial,
                'pedido_numero'         => $dto->pedidoNumero,
                'pedido_item'           => $dto->pedidoItem,
                'contrato_codigo'       => $dto->contratoCodigo,
                'ticket_guardian'       => $dto->ticketGuardian,
                'cliente_codigo'        => $dto->clienteCodigo,
                'cliente_loja'          => $dto->clienteLoja,
                'cliente_nome'          => $dto->clienteNome,
                'produto_codigo'        => $dto->produtoCodigo,
                'produto_descricao'     => $dto->produtoDescricao,
                'quantidade_prevista'   => $dto->quantidadePrevista,
                'unidade'               => $dto->unidade,
                'placa_veiculo'         => $dto->placaVeiculo,
                'placa_carreta'         => $dto->placaCarreta,
                'motorista_nome'        => $dto->motoristaNome,
                'motorista_documento'   => $dto->motoristaDocumento,
                'transportadora_codigo' => $dto->transportadoraCodigo,
                'transportadora_nome'   => $dto->transportadoraNome,
                'tara'                  => $dto->tara,
                'tolerancia_percentual' => $dto->toleranciaPercentual,
                'pilha_produto_id'      => $destino->pilhaProdutoId,
                'ponto_carregamento_id' => $destino->pontoCarregamentoId,
                'status'                => StatusOrdem::CRIADO,
            ]);

            // Evento inicial direto: sem transição, status já é CRIADO
            EventoOrdemCarregamento::create([
                'ordem_carregamento_id' => $ordem->id,
                'tipo'                  => TipoEvento::ORDEM_CRIADA,
                'status_anterior'       => null,
                'status_novo'           => StatusOrdem::CRIADO,
                'origem'                => $origem,
                'observacao'            => 'Ordem criada',
                'payload'               => ['produto_codigo' => $dto->produtoCodigo],
                'ocorrido_em'           => now(),
            ]);

            if (!$destino->resolvido) {
                $this->registrarDivergencia->execute($ordem, $destino->tipoDivergencia, $origem,
                    "Produto {$dto->produtoCodigo}: " . $destino->tipoDivergencia->label()
                );
            } elseif ($dto->tara !== null) {
                $this->alterarStatus->execute($ordem, new AlterarStatusDTO(
                    novoStatus: StatusOrdem::TARA_REALIZADA,
                    tipoEvento: TipoEvento::TARA_REALIZADA,
                    origem: $origem,
                    observacao: "Tara: {$dto->tara} kg",
                    payload: ['tara' => $dto->tara],
                ));
            }

            return $ordem->fresh(['pilhaProduto', 'pontoCarregamento', 'eventos']);
        });
    }
}
