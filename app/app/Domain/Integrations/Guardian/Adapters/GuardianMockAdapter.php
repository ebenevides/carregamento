<?php

namespace App\Domain\Integrations\Guardian\Adapters;

use App\Domain\Integrations\Guardian\DTOs\FilaGuardianDTO;
use App\Domain\Integrations\Guardian\DTOs\TicketGuardianDTO;
use Illuminate\Validation\ValidationException;

class GuardianMockAdapter implements GuardianAdapterInterface
{
    private array $tickets = [
        '0000001' => [
            'ticket'      => '0000001',
            'status'      => 'ATIVO',
            'placa'       => 'ABC1D23',
            'motorista'   => 'João da Silva',
            'tara'        => 15.2,
            'peso_bruto'  => 47.2,
            'peso_liquido' => 32.0,
            'data_entrada' => '2026-06-26T08:00:00Z',
            'data_saida'   => null,
            'ub'          => 'UB-1',
        ],
        '0000002' => [
            'ticket'      => '0000002',
            'status'      => 'ATIVO',
            'placa'       => 'XYZ9Z99',
            'motorista'   => 'Maria Souza',
            'tara'        => 14.8,
            'peso_bruto'  => null,
            'peso_liquido' => null,
            'data_entrada' => '2026-06-26T09:30:00Z',
            'data_saida'   => null,
            'ub'          => 'UB-2',
        ],
    ];

    public function consultarTicket(string $ticket): TicketGuardianDTO
    {
        $data = $this->tickets[$ticket] ?? null;

        if ($data === null) {
            throw ValidationException::withMessages([
                'ticket' => "Ticket {$ticket} não encontrado no Guardian (mock).",
            ]);
        }

        return new TicketGuardianDTO(
            ticket: $data['ticket'],
            status: $data['status'],
            placa: $data['placa'],
            motorista: $data['motorista'],
            tara: $data['tara'],
            pesoBruto: $data['peso_bruto'],
            pesoLiquido: $data['peso_liquido'],
            dataEntrada: $data['data_entrada'],
            dataSaida: $data['data_saida'],
            ub: $data['ub'] ?? null,
        );
    }

    /** Mock: ticket 0000001 aparece liberado na fila; qualquer outro fica "aguardando". */
    public function consultarFila(string $ticket, ?string $placa = null): FilaGuardianDTO
    {
        if (!$this->ticketExiste($ticket)) {
            return new FilaGuardianDTO(
                ticket: $ticket,
                erro: 1,
                descricao: "Ticket {$ticket} não encontrado na fila (mock).",
                placa: null,
                posicao: null,
                estado: null,
                estadoDescricao: null,
                filaId: null,
                filaCodigo: null,
                filaNome: null,
                filaMensagem: null,
                mensagemUsuario: null,
                dataAtualizacao: null,
            );
        }

        $data = $this->tickets[$ticket];
        $liberado = $ticket === '0000001';

        return new FilaGuardianDTO(
            ticket: $ticket,
            erro: 0,
            descricao: 'Veículo localizado na fila com sucesso! (mock)',
            placa: $data['placa'],
            posicao: $liberado ? 1 : 5,
            estado: $liberado ? '305060' : '305050',
            estadoDescricao: $liberado ? 'Liberado' : 'Aguardando',
            filaId: 3,
            filaCodigo: 'CARREGAMENTO',
            filaNome: 'PROCESSO DE CARREGAMENTO',
            filaMensagem: null,
            mensagemUsuario: null,
            dataAtualizacao: now()->toIso8601String(),
        );
    }

    public function consultarTara(string $ticket): float
    {
        return $this->consultarTicket($ticket)->taraKg() ?? 0.0;
    }

    public function consultarPesoFinal(string $ticket): float
    {
        $dto = $this->consultarTicket($ticket);

        if ($dto->pesoBrutoKg() === null) {
            throw ValidationException::withMessages([
                'ticket' => "Peso final ainda não disponível para ticket {$ticket}.",
            ]);
        }

        return $dto->pesoBrutoKg();
    }

    public function ticketExiste(string $ticket): bool
    {
        return isset($this->tickets[$ticket]);
    }

    /**
     * Gera tickets sintéticos por dia (determinístico por data, sem chamada real ao Guardian)
     * para permitir testar o relatório por período sem depender do servidor SOAP.
     */
    public function consultarTicketsPorPeriodo(\DateTimeInterface $inicio, \DateTimeInterface $fim): array
    {
        $placas     = ['ABC1D23', 'XYZ9Z99', 'JJK4L56', 'QWE7R89', 'MNO2P34'];
        $motoristas = ['João da Silva', 'Maria Souza', 'Carlos Pereira', 'Ana Lima', 'Pedro Rocha'];
        $ubs        = ['UB-1', 'UB-2'];
        $usuariosProtheus = ['Samira', 'Carlos', 'Fernanda'];

        $tickets = [];
        $dia = (new \DateTimeImmutable($inicio->format('Y-m-d')))->setTime(0, 0);
        $ultimoDia = new \DateTimeImmutable($fim->format('Y-m-d'));

        while ($dia <= $ultimoDia) {
            $seed = (int) $dia->format('Ymd');
            mt_srand($seed);
            $qtd = 6 + ($seed % 5);

            for ($i = 0; $i < $qtd; $i++) {
                $entrada = $dia->setTime(mt_rand(6, 20), mt_rand(0, 59));

                if ($entrada < $inicio || $entrada > $fim) {
                    continue;
                }

                $duracaoMin = mt_rand(35, 180);
                $saida = $entrada->modify("+{$duracaoMin} minutes");

                $tara      = (float) (mt_rand(800, 1500) * 10);
                $pesoBruto = (float) (mt_rand(3000, 4500) * 10);

                $tickets[] = new TicketGuardianDTO(
                    ticket:           sprintf('%07d', $seed * 100 + $i),
                    status:           'ENCERRADO',
                    placa:            $placas[$i % count($placas)],
                    motorista:        $motoristas[$i % count($motoristas)],
                    tara:             $tara,
                    pesoBruto:        $pesoBruto,
                    pesoLiquido:      $pesoBruto - $tara,
                    dataEntrada:      $entrada->format(DATE_ATOM),
                    dataSaida:        $saida->format(DATE_ATOM),
                    quantidadeACarregar: (float) mt_rand(8, 50),
                    ub:                  $ubs[$i % count($ubs)],
                    usuarioProtheus:     $usuariosProtheus[$i % count($usuariosProtheus)],
                    observacao:          (string) mt_rand(90000, 99999),
                    tempoPermanencia:    $duracaoMin,
                );
            }

            $dia = $dia->modify('+1 day');
        }

        mt_srand();

        return $tickets;
    }
}
