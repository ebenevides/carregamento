<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Enums\StatusPonto;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class PontoCarregamentoController extends Controller
{
    public function index(): Response
    {
        $pontos = PontoCarregamento::withCount('ordensCarregamento')
            ->orderBy('descricao')
            ->get()
            ->map(fn ($p) => [
                'id'                        => $p->id,
                'codigo'                    => $p->codigo,
                'descricao'                 => $p->descricao,
                'unidade_britagem'          => $p->unidade_britagem,
                'status'                    => $p->status->value,
                'status_label'              => $p->status->label(),
                'observacao'                => $p->observacao,
                'ordens_carregamento_count' => $p->ordens_carregamento_count,
            ]);

        return Inertia::render('Pontos/Index', [
            'pontos'  => $pontos,
            'statuses' => collect(StatusPonto::cases())->map(fn ($s) => [
                'value' => $s->value,
                'label' => $s->label(),
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'codigo'           => ['required', 'string', 'max:20', 'unique:pontos_carregamento,codigo'],
            'descricao'        => ['required', 'string', 'max:100'],
            'unidade_britagem' => ['nullable', 'string', 'max:10'],
            'status'           => ['sometimes', 'string', 'in:ATIVO,INATIVO,BLOQUEADO'],
            'observacao'       => ['nullable', 'string'],
        ]);

        PontoCarregamento::create($data);

        return back()->with('success', 'Ponto criado.');
    }

    public function update(Request $request, PontoCarregamento $pontoCarregamento): RedirectResponse
    {
        $data = $request->validate([
            'codigo'           => ['sometimes', 'string', 'max:20', "unique:pontos_carregamento,codigo,{$pontoCarregamento->id}"],
            'descricao'        => ['sometimes', 'string', 'max:100'],
            'unidade_britagem' => ['nullable', 'string', 'max:10'],
            'status'           => ['sometimes', 'string', 'in:ATIVO,INATIVO,BLOQUEADO'],
            'observacao'       => ['nullable', 'string'],
        ]);

        $pontoCarregamento->update($data);

        return back()->with('success', 'Ponto atualizado.');
    }

    public function destroy(PontoCarregamento $pontoCarregamento): RedirectResponse
    {
        $pontoCarregamento->delete();

        return back()->with('success', 'Ponto removido.');
    }

    public function ativar(PontoCarregamento $pontoCarregamento): RedirectResponse
    {
        $pontoCarregamento->update(['status' => StatusPonto::ATIVO]);

        return back()->with('success', 'Ponto ativado.');
    }

    public function inativar(PontoCarregamento $pontoCarregamento): RedirectResponse
    {
        $pontoCarregamento->update(['status' => StatusPonto::INATIVO]);

        return back()->with('success', 'Ponto inativado.');
    }
}
