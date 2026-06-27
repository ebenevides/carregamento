<?php

namespace App\Http\Controllers\Web;

use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Models\PontoCarregamento;
use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class UsuarioController extends Controller
{
    public function index(): Response
    {
        $usuarios = User::with('pontoCarregamento')
            ->orderBy('name')
            ->get()
            ->map(fn ($u) => [
                'id'                     => $u->id,
                'name'                   => $u->name,
                'email'                  => $u->email,
                'perfil'                 => $u->perfil?->value,
                'perfil_label'           => $u->perfil?->label(),
                'ativo'                  => $u->ativo,
                'ponto_carregamento_id'  => $u->ponto_carregamento_id,
                'ponto_descricao'        => $u->pontoCarregamento?->descricao,
                'email_verified_at'      => $u->email_verified_at?->toISOString(),
            ]);

        return Inertia::render('Usuarios/Index', [
            'usuarios' => $usuarios,
            'perfis'   => collect(PerfilUsuario::cases())->map(fn ($p) => [
                'value' => $p->value,
                'label' => $p->label(),
            ]),
            'pontos' => PontoCarregamento::orderBy('descricao')->get(['id', 'descricao']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'                  => ['required', 'string', 'max:255'],
            'email'                 => ['required', 'email', 'max:255', 'unique:users,email'],
            'password'              => ['required', 'string', 'min:8'],
            'perfil'                => ['required', Rule::enum(PerfilUsuario::class)],
            'ponto_carregamento_id' => ['nullable', 'integer', 'exists:pontos_carregamento,id'],
            'ativo'                 => ['sometimes', 'boolean'],
        ]);

        User::create([
            ...$data,
            'password'           => Hash::make($data['password']),
            'email_verified_at'  => now(),
            'ativo'              => $data['ativo'] ?? true,
        ]);

        return back()->with('success', 'Usuário criado.');
    }

    public function update(Request $request, User $usuario): RedirectResponse
    {
        $data = $request->validate([
            'name'                  => ['sometimes', 'string', 'max:255'],
            'email'                 => ['sometimes', 'email', 'max:255', Rule::unique('users', 'email')->ignore($usuario->id)],
            'password'              => ['nullable', 'string', 'min:8'],
            'perfil'                => ['sometimes', Rule::enum(PerfilUsuario::class)],
            'ponto_carregamento_id' => ['nullable', 'integer', 'exists:pontos_carregamento,id'],
            'ativo'                 => ['sometimes', 'boolean'],
        ]);

        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        } else {
            unset($data['password']);
        }

        $usuario->update($data);

        return back()->with('success', 'Usuário atualizado.');
    }

    public function destroy(User $usuario): RedirectResponse
    {
        abort_if($usuario->id === auth()->id(), 403, 'Não pode remover o próprio usuário.');

        $usuario->delete();

        return back()->with('success', 'Usuário removido.');
    }

    public function toggleAtivo(User $usuario): RedirectResponse
    {
        abort_if($usuario->id === auth()->id(), 403, 'Não pode desativar o próprio usuário.');

        $usuario->update(['ativo' => !$usuario->ativo]);

        return back()->with('success', $usuario->ativo ? 'Usuário ativado.' : 'Usuário desativado.');
    }
}
