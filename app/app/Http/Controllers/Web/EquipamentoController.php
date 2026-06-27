<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Models\Equipamento;
use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class EquipamentoController extends Controller
{
    public function index(): Response
    {
        return Inertia::render('Equipamentos/Index', [
            'equipamentos' => Equipamento::orderBy('codigo')->get()->map(fn ($e) => [
                'id'       => $e->id,
                'codigo'   => $e->codigo,
                'descricao' => $e->descricao,
                'tipo'     => $e->tipo,
                'ativo'    => $e->ativo,
            ]),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'codigo'    => ['required', 'string', 'max:20', 'unique:equipamentos,codigo'],
            'descricao' => ['required', 'string', 'max:100'],
            'tipo'      => ['nullable', 'string', 'max:50'],
            'ativo'     => ['sometimes', 'boolean'],
        ]);

        Equipamento::create(['ativo' => true, ...$data]);

        return back()->with('success', 'Equipamento criado.');
    }

    public function update(Request $request, Equipamento $equipamento): RedirectResponse
    {
        $data = $request->validate([
            'codigo'    => ['sometimes', 'string', 'max:20', "unique:equipamentos,codigo,{$equipamento->id}"],
            'descricao' => ['sometimes', 'string', 'max:100'],
            'tipo'      => ['nullable', 'string', 'max:50'],
            'ativo'     => ['sometimes', 'boolean'],
        ]);

        $equipamento->update($data);

        return back()->with('success', 'Equipamento atualizado.');
    }

    public function destroy(Equipamento $equipamento): RedirectResponse
    {
        $equipamento->delete();

        return back()->with('success', 'Equipamento removido.');
    }
}
