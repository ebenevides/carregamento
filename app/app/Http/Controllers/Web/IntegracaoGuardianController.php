<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Domain\Carregamento\Models\OrdemCarregamento;
use App\Domain\Integrations\Guardian\Services\GuardianService;
use App\Http\Controllers\Controller;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Inertia\Inertia;
use Inertia\Response;

class IntegracaoGuardianController extends Controller
{
    public function __construct(private readonly GuardianService $guardian) {}

    public function index(): Response
    {
        $pendenteTara = OrdemCarregamento::where('status', StatusOrdem::CRIADO)
            ->whereNotNull('ticket_guardian')
            ->whereNull('tara')
            ->orderBy('created_at')
            ->get()
            ->map(fn ($o) => [
                'id'             => $o->id,
                'placa'          => $o->placa_veiculo,
                'produto'        => $o->produto_descricao,
                'ticket'         => $o->ticket_guardian,
                'criado_em'      => $o->created_at?->toISOString(),
            ]);

        $pendentePesagem = OrdemCarregamento::where('status', StatusOrdem::AGUARDANDO_PESAGEM_FINAL)
            ->whereNotNull('ticket_guardian')
            ->orderBy('concluido_em')
            ->get()
            ->map(fn ($o) => [
                'id'             => $o->id,
                'placa'          => $o->placa_veiculo,
                'produto'        => $o->produto_descricao,
                'ticket'         => $o->ticket_guardian,
                'concluido_em'   => $o->concluido_em?->toISOString(),
            ]);

        // Mesma query que SincronizarFilaGuardianJob processa a cada 2min — ver DT-014.
        $pendenteFila = OrdemCarregamento::where('status', StatusOrdem::TARA_REALIZADA)
            ->whereNotNull('ticket_guardian')
            ->orderBy('updated_at')
            ->get()
            ->map(fn ($o) => [
                'id'           => $o->id,
                'placa'        => $o->placa_veiculo,
                'produto'      => $o->produto_descricao,
                'ticket'       => $o->ticket_guardian,
                'tara_em'      => $o->updated_at?->toISOString(),
            ]);

        return Inertia::render('Integracoes/Guardian/Index', [
            'pendente_tara'    => $pendenteTara,
            'pendente_pesagem' => $pendentePesagem,
            'pendente_fila'    => $pendenteFila,
            'mock_ativo'       => config('integrations.guardian.mock'),
            'wsdl'             => config('integrations.guardian.wsdl'),
        ]);
    }

    public function consultarTicket(Request $request): \Illuminate\Http\JsonResponse
    {
        $request->validate(['ticket' => ['required', 'string', 'max:30']]);

        try {
            $dto = $this->guardian->consultarTicket($request->input('ticket'));

            // Fila é uma consulta separada no Guardian — falha aqui não invalida o resto do ticket.
            $fila = null;
            try {
                $filaDto = $this->guardian->consultarFila($dto->ticket, $dto->placa);
                if ($filaDto->sucesso()) {
                    $fila = [
                        'posicao'          => $filaDto->posicao,
                        'estado_descricao' => $filaDto->estadoDescricao,
                        'liberado'         => $filaDto->liberado(),
                        'fila_nome'        => $filaDto->filaNome,
                    ];
                }
            } catch (\Throwable) {
                // silencioso — consulta de ticket segue valendo sem o dado de fila
            }

            return response()->json([
                'ok'           => true,
                'ticket'       => $dto->ticket,
                'status'       => $dto->status,
                'placa'        => $dto->placa,
                'motorista'    => $dto->motorista,
                'tara_kg'      => $dto->taraKg(),
                'peso_bruto_kg' => $dto->pesoBrutoKg(),
                'peso_liquido_kg' => $dto->pesoLiquidoKg(),
                'data_entrada' => $dto->dataEntrada,
                'data_saida'   => $dto->dataSaida,
                'fila'         => $fila,
            ]);
        } catch (\Throwable $e) {
            return response()->json(['ok' => false, 'erro' => $e->getMessage()], 422);
        }
    }

    public function sincronizarTaraOrdem(OrdemCarregamento $ordem): RedirectResponse
    {
        $ok = $this->guardian->sincronizarTara($ordem);

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? "Tara sincronizada para ordem {$ordem->placa_veiculo}." : "Não foi possível sincronizar tara (ticket: {$ordem->ticket_guardian})."
        );
    }

    public function sincronizarPesagemOrdem(OrdemCarregamento $ordem): RedirectResponse
    {
        $ok = $this->guardian->sincronizarPesagemFinal($ordem);

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? "Pesagem sincronizada para {$ordem->placa_veiculo}." : "Peso final ainda não disponível no Guardian."
        );
    }

    public function sincronizarFilaOrdem(OrdemCarregamento $ordem): RedirectResponse
    {
        $ok = $this->guardian->sincronizarFila($ordem);

        return back()->with(
            $ok ? 'success' : 'error',
            $ok ? "Ordem {$ordem->placa_veiculo} liberada para a fila de carregamento." : "Veículo ainda não liberado na fila do Guardian (ticket: {$ordem->ticket_guardian})."
        );
    }

    public function sincronizarTodas(): RedirectResponse
    {
        $taras    = $this->guardian->sincronizarTodasTaras();
        $pesagens = $this->guardian->sincronizarTodasPesagens();
        $filas    = $this->guardian->sincronizarTodasFilas();

        return back()->with('success', "Sincronização concluída: {$taras} tara(s), {$pesagens} pesagem(ns), {$filas} liberação(ões) de fila.");
    }

    public function relatorioPeriodo(Request $request): Response
    {
        $dados = $this->buscarRelatorio($request);

        return Inertia::render('Integracoes/Guardian/Relatorio', $dados);
    }

    public function relatorioPeriodoPdf(Request $request): \Symfony\Component\HttpFoundation\Response
    {
        $dados = $this->buscarRelatorio($request);

        $pdf = Pdf::loadView('guardian.relatorio-pdf', $dados)->setPaper('a4', 'landscape');

        $nome = "relatorio-guardian_{$dados['filtros']['data_de']}_a_{$dados['filtros']['data_ate']}.pdf";

        return $pdf->download($nome);
    }

    /** Busca dados do relatório por período, compartilhado entre view web e PDF. */
    private function buscarRelatorio(Request $request): array
    {
        $hoje = now()->toDateString();

        $data = $request->validate([
            'data_de'  => ['nullable', 'date'],
            'data_ate' => ['nullable', 'date'],
        ]);

        $dataDe  = $data['data_de'] ?? $hoje;
        $dataAte = $data['data_ate'] ?? $hoje;

        $inicio = Carbon::parse($dataDe)->startOfDay();
        $fim    = Carbon::parse($dataAte)->endOfDay();

        $erro = null;
        $tickets = [];
        $metricas = null;

        try {
            $resultado = $this->guardian->relatorioPorPeriodo($inicio, $fim);
            $metricas  = $resultado['metricas'];
            $tickets   = array_map(fn ($dto) => [
                'ticket'           => $dto->ticket,
                'status'           => $dto->status,
                'placa'            => $dto->placa,
                'motorista'        => $dto->motorista,
                'tara_kg'          => $dto->taraKg(),
                'peso_bruto_kg'    => $dto->pesoBrutoKg(),
                'peso_liquido_kg'  => $dto->pesoLiquidoKg(),
                'data_entrada'     => $dto->dataEntrada,
                'data_saida'       => $dto->dataSaida,
                'tempo_patio_min'  => $dto->tempoPatioMinutos(),
                'peso_doc_kg'      => $dto->pesoDoc,
                'unidade'          => $dto->unidade,
                'atendente'        => $dto->atendente,
                'pedido'           => $dto->pedido,
            ], $resultado['tickets']);
        } catch (\Throwable $e) {
            $erro = $e->getMessage();
        }

        return [
            'tickets'    => $tickets,
            'metricas'   => $metricas,
            'erro'       => $erro,
            'filtros'    => ['data_de' => $dataDe, 'data_ate' => $dataAte],
            'mock_ativo' => config('integrations.guardian.mock'),
        ];
    }
}
