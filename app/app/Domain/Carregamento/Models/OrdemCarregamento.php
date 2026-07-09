<?php

namespace App\Domain\Carregamento\Models;

use App\Domain\Carregamento\Enums\PerfilUsuario;
use App\Domain\Carregamento\Enums\StatusOrdem;
use App\Models\User;
use Database\Factories\OrdemCarregamentoFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrdemCarregamento extends Model
{
    /** @use HasFactory<OrdemCarregamentoFactory> */
    use HasFactory;
    use HasUuids;
    use SoftDeletes;

    protected static function newFactory(): OrdemCarregamentoFactory
    {
        return OrdemCarregamentoFactory::new();
    }

    protected $table = 'ordens_carregamento';

    protected $fillable = [
        'empresa',
        'filial',
        'pedido_numero',
        'pedido_item',
        'contrato_codigo',
        'ticket_guardian',
        'cliente_codigo',
        'cliente_loja',
        'cliente_nome',
        'produto_codigo',
        'produto_descricao',
        'quantidade_prevista',
        'unidade',
        'placa_veiculo',
        'placa_carreta',
        'motorista_nome',
        'motorista_documento',
        'transportadora_codigo',
        'transportadora_nome',
        'tara',
        'peso_bruto',
        'peso_liquido',
        'tolerancia_percentual',
        'pilha_produto_id',
        'ponto_carregamento_id',
        'equipamento_id',
        'operador_id',
        'motorista_user_id',
        'status',
        'iniciado_em',
        'concluido_em',
        'pesagem_final_em',
    ];

    protected $casts = [
        'status'              => StatusOrdem::class,
        'quantidade_prevista' => 'decimal:3',
        'tara'                => 'decimal:3',
        'peso_bruto'          => 'decimal:3',
        'peso_liquido'        => 'decimal:3',
        'tolerancia_percentual' => 'decimal:2',
        'iniciado_em'         => 'datetime',
        'concluido_em'        => 'datetime',
        'pesagem_final_em'    => 'datetime',
    ];

    public function pilhaProduto(): BelongsTo
    {
        return $this->belongsTo(PilhaProduto::class, 'pilha_produto_id');
    }

    public function pontoCarregamento(): BelongsTo
    {
        return $this->belongsTo(PontoCarregamento::class, 'ponto_carregamento_id');
    }

    public function equipamento(): BelongsTo
    {
        return $this->belongsTo(Equipamento::class, 'equipamento_id');
    }

    public function operador(): BelongsTo
    {
        return $this->belongsTo(User::class, 'operador_id');
    }

    public function motorista(): BelongsTo
    {
        return $this->belongsTo(User::class, 'motorista_user_id');
    }

    public function eventos(): HasMany
    {
        return $this->hasMany(EventoOrdemCarregamento::class, 'ordem_carregamento_id')->orderBy('ocorrido_em');
    }

    public function divergencias(): HasMany
    {
        return $this->hasMany(DivergenciaCarregamento::class, 'ordem_carregamento_id');
    }

    public function divergenciasAbertas(): HasMany
    {
        return $this->divergencias()->where('status', 'ABERTA');
    }

    public function temDivergenciaAberta(): bool
    {
        return $this->divergenciasAbertas()->exists();
    }

    public function mensagensChat(): HasMany
    {
        return $this->hasMany(\App\Domain\Chat\Models\MensagemChat::class, 'ordem_carregamento_id')
            ->orderBy('created_at');
    }

    public function scopeNaFila($query)
    {
        return $query->whereIn('status', [
            StatusOrdem::AGUARDANDO_CARREGAMENTO->value,
            StatusOrdem::EM_CARREGAMENTO->value,
        ]);
    }

    public function scopePorPonto($query, int $pontoId)
    {
        return $query->where('ponto_carregamento_id', $pontoId);
    }

    /**
     * Verifica se um User pode acessar dados/mensagens desta ordem.
     * Motorista vinculado ou operador do mesmo ponto.
     */
    public function usuarioPodeAcessar(User $user): bool
    {
        // Motorista vinculado a esta ordem
        if ($this->motorista_user_id !== null && (int) $user->id === (int) $this->motorista_user_id) {
            return true;
        }

        // Operador/expedição/admin do mesmo ponto
        if ($user->perfil instanceof PerfilUsuario && $user->perfil->podeIniciarCarregamento()) {
            return (int) $user->ponto_carregamento_id === (int) $this->ponto_carregamento_id;
        }

        return false;
    }

    public function pesoLiquidoCalculado(): ?float
    {
        if ($this->peso_bruto === null || $this->tara === null) {
            return null;
        }

        return $this->peso_bruto - $this->tara;
    }

    public function dentroDaTolerancia(): bool
    {
        $liquido = $this->pesoLiquidoCalculado();

        if ($liquido === null) {
            return false;
        }

        $tolerancia = ($this->quantidade_prevista * $this->tolerancia_percentual) / 100;

        return abs($liquido - $this->quantidade_prevista) <= $tolerancia;
    }
}
