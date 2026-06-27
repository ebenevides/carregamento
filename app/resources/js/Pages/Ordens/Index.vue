<template>
    <AppLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Ordens de Carregamento</h1>
                <button v-if="pode(['ADMIN','EXPEDICAO'])" @click="modalCriarAberto = true"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                    + Nova ordem
                </button>
            </div>

            <div v-if="$page.props.flash?.success"
                class="bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg text-sm">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error"
                class="bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg text-sm">
                {{ $page.props.flash.error }}
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Data</label>
                    <input type="date" v-model="form.data"
                        class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Ponto</label>
                    <select v-model="form.ponto_id"
                        class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option v-for="p in pontos" :key="p.id" :value="p.id">{{ p.descricao }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select v-model="form.status"
                        class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="">Todos</option>
                        <option v-for="s in statusOpcoes" :key="s.value" :value="s.value">{{ s.label }}</option>
                    </select>
                </div>
                <button @click="filtrar"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                    Filtrar
                </button>
            </div>

            <!-- Tabela -->
            <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Pedido</th>
                            <th class="px-4 py-3 text-left">Cliente</th>
                            <th class="px-4 py-3 text-left">Produto</th>
                            <th class="px-4 py-3 text-left">Placa</th>
                            <th class="px-4 py-3 text-right">Qtd</th>
                            <th class="px-4 py-3 text-left">Ponto</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Criado</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!ordens.data.length">
                            <td colspan="9" class="text-center py-10 text-gray-400">Nenhuma ordem encontrada.</td>
                        </tr>
                        <tr v-for="o in ordens.data" :key="o.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ o.pedido_numero ?? '—' }}</td>
                            <td class="px-4 py-3">{{ o.cliente_nome ?? '—' }}</td>
                            <td class="px-4 py-3">{{ o.produto_descricao ?? '—' }}</td>
                            <td class="px-4 py-3 font-mono font-bold">{{ o.placa_veiculo }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ formatQtd(o.quantidade_prevista) }} {{ o.unidade }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ o.ponto ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="o.status" :label="o.status_label" />
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-400">{{ formatDate(o.criado_em) }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex gap-1 justify-end">
                                    <Link :href="route('ordens.show', o.id)"
                                        class="text-xs px-2 py-1 bg-gray-100 text-gray-700 rounded hover:bg-gray-200">
                                        Detalhes
                                    </Link>
                                    <button v-if="podeIniciar(o)" @click="confirmarIniciar(o)"
                                        class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                                        Iniciar
                                    </button>
                                    <button v-if="podeConcluir(o)" @click="confirmarConcluir(o)"
                                        class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">
                                        Concluir
                                    </button>
                                    <button v-if="podeCancelar(o)" @click="confirmarCancelar(o)"
                                        class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">
                                        Cancelar
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Paginacao :links="ordens.links" />
        </div>

        <!-- Modal nova ordem -->
        <div v-if="modalCriarAberto" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-2xl p-6 space-y-4 max-h-screen overflow-y-auto">
                <h2 class="font-bold text-lg">Nova Ordem de Carregamento</h2>

                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Produto (cód.) *</label>
                        <input v-model="criarForm.produto_codigo" type="text" maxlength="30"
                            class="w-full border rounded-lg px-3 py-2 text-sm"
                            :class="{ 'border-red-400': criarForm.errors.produto_codigo }" />
                        <p v-if="criarForm.errors.produto_codigo" class="text-red-500 text-xs mt-1">{{ criarForm.errors.produto_codigo }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Produto (descrição)</label>
                        <input v-model="criarForm.produto_descricao" type="text" maxlength="100"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Qtd. prevista *</label>
                        <input v-model="criarForm.quantidade_prevista" type="number" step="0.001" min="0.001"
                            class="w-full border rounded-lg px-3 py-2 text-sm"
                            :class="{ 'border-red-400': criarForm.errors.quantidade_prevista }" />
                        <p v-if="criarForm.errors.quantidade_prevista" class="text-red-500 text-xs mt-1">{{ criarForm.errors.quantidade_prevista }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Unidade</label>
                        <input v-model="criarForm.unidade" type="text" maxlength="10"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Placa veículo *</label>
                        <input v-model="criarForm.placa_veiculo" type="text" maxlength="10"
                            class="w-full border rounded-lg px-3 py-2 text-sm uppercase"
                            :class="{ 'border-red-400': criarForm.errors.placa_veiculo }" />
                        <p v-if="criarForm.errors.placa_veiculo" class="text-red-500 text-xs mt-1">{{ criarForm.errors.placa_veiculo }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Placa carreta</label>
                        <input v-model="criarForm.placa_carreta" type="text" maxlength="10"
                            class="w-full border rounded-lg px-3 py-2 text-sm uppercase" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Motorista</label>
                        <input v-model="criarForm.motorista_nome" type="text" maxlength="100"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Documento motorista</label>
                        <input v-model="criarForm.motorista_documento" type="text" maxlength="20"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Pedido nº</label>
                        <input v-model="criarForm.pedido_numero" type="text" maxlength="20"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Ticket Guardian</label>
                        <input v-model="criarForm.ticket_guardian" type="text" maxlength="20"
                            class="w-full border rounded-lg px-3 py-2 text-sm"
                            :class="{ 'border-red-400': criarForm.errors.ticket_guardian }" />
                        <p v-if="criarForm.errors.ticket_guardian" class="text-red-500 text-xs mt-1">{{ criarForm.errors.ticket_guardian }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Cliente nome</label>
                        <input v-model="criarForm.cliente_nome" type="text" maxlength="150"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Transportadora</label>
                        <input v-model="criarForm.transportadora_nome" type="text" maxlength="100"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Tara (kg)</label>
                        <input v-model="criarForm.tara" type="number" step="0.001" min="0"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Tolerância (%)</label>
                        <input v-model="criarForm.tolerancia_percentual" type="number" step="0.01" min="0" max="100"
                            class="w-full border rounded-lg px-3 py-2 text-sm" />
                    </div>
                </div>

                <div class="flex gap-3 justify-end pt-2">
                    <button @click="modalCriarAberto = false; criarForm.reset()"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                    <button @click="criarOrdem" :disabled="criarForm.processing"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
                        {{ criarForm.processing ? 'Criando...' : 'Criar ordem' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal confirmar ação -->
        <div v-if="acaoAtual" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 space-y-4">
                <h2 :class="['font-bold text-lg', acaoAtual.cor]">{{ acaoAtual.titulo }}</h2>
                <p class="text-sm text-gray-600">{{ acaoAtual.descricao }}</p>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Observação (opcional)</label>
                    <textarea v-model="acaoObservacao" rows="2"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div class="flex gap-3 justify-end">
                    <button @click="acaoAtual = null; acaoObservacao = ''"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                    <button @click="executarAcao"
                        :class="['px-4 py-2 text-white rounded-lg text-sm', acaoAtual.btnClass]">
                        {{ acaoAtual.btnLabel }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, useForm, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Paginacao from '@/Components/Paginacao.vue';

const props = defineProps({
    ordens: Object,
    pontos: Array,
    filtros: Object,
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

function pode(perfis) {
    return perfis.includes(authUser.value?.perfil);
}

const form = ref({
    data:     props.filtros?.data ?? '',
    ponto_id: props.filtros?.ponto_id ?? '',
    status:   props.filtros?.status ?? '',
});

const statusOpcoes = [
    { value: 'CRIADO',                   label: 'Criado' },
    { value: 'TARA_REALIZADA',           label: 'Tara Realizada' },
    { value: 'AGUARDANDO_CARREGAMENTO',  label: 'Aguardando Carregamento' },
    { value: 'EM_CARREGAMENTO',          label: 'Em Carregamento' },
    { value: 'CARREGAMENTO_CONCLUIDO',   label: 'Carregamento Concluído' },
    { value: 'AGUARDANDO_PESAGEM_FINAL', label: 'Aguardando Pesagem Final' },
    { value: 'PESAGEM_FINAL_REALIZADA',  label: 'Pesagem Final Realizada' },
    { value: 'VALIDADO',                 label: 'Validado' },
    { value: 'DIVERGENCIA',              label: 'Divergência' },
    { value: 'CANCELADO',                label: 'Cancelado' },
    { value: 'FINALIZADO',               label: 'Finalizado' },
];

function filtrar() {
    router.get(route('ordens'), form.value, { preserveScroll: true });
}

// ─── criar ───────────────────────────────────────────────────────────────────

const modalCriarAberto = ref(false);
const criarForm = useForm({
    produto_codigo:        '',
    produto_descricao:     '',
    quantidade_prevista:   '',
    unidade:               'TN',
    placa_veiculo:         '',
    placa_carreta:         '',
    motorista_nome:        '',
    motorista_documento:   '',
    pedido_numero:         '',
    ticket_guardian:       '',
    cliente_nome:          '',
    transportadora_nome:   '',
    tara:                  '',
    tolerancia_percentual: '5',
});

function criarOrdem() {
    criarForm.post(route('ordens.store'), {
        onSuccess: () => { modalCriarAberto.value = false; criarForm.reset(); },
    });
}

// ─── ações de status ─────────────────────────────────────────────────────────

const acaoAtual  = ref(null);
const acaoObservacao = ref('');

const CANCELAVEIS  = ['CRIADO', 'TARA_REALIZADA', 'AGUARDANDO_CARREGAMENTO', 'DIVERGENCIA'];

function podeCancelar(o) {
    if (!pode(['ADMIN', 'EXPEDICAO'])) return false;
    return CANCELAVEIS.includes(o.status);
}
function podeIniciar(o) {
    if (!pode(['ADMIN', 'EXPEDICAO', 'OPERADOR'])) return false;
    return o.status === 'AGUARDANDO_CARREGAMENTO';
}
function podeConcluir(o) {
    if (!pode(['ADMIN', 'EXPEDICAO', 'OPERADOR'])) return false;
    return o.status === 'EM_CARREGAMENTO';
}

function confirmarCancelar(o) {
    acaoAtual.value = {
        ordem:    o,
        tipo:     'cancelar',
        titulo:   'Cancelar ordem?',
        descricao: `Placa ${o.placa_veiculo} — ${o.status_label}. Ação não pode ser desfeita.`,
        cor:      'text-red-700',
        btnClass: 'bg-red-600 hover:bg-red-700',
        btnLabel: 'Cancelar ordem',
    };
}
function confirmarIniciar(o) {
    acaoAtual.value = {
        ordem:    o,
        tipo:     'iniciar',
        titulo:   'Iniciar carregamento?',
        descricao: `Placa ${o.placa_veiculo} — ponto: ${o.ponto ?? 'N/A'}`,
        cor:      'text-blue-700',
        btnClass: 'bg-blue-600 hover:bg-blue-700',
        btnLabel: 'Iniciar',
    };
}
function confirmarConcluir(o) {
    acaoAtual.value = {
        ordem:    o,
        tipo:     'concluir',
        titulo:   'Concluir carregamento?',
        descricao: `Placa ${o.placa_veiculo}`,
        cor:      'text-green-700',
        btnClass: 'bg-green-600 hover:bg-green-700',
        btnLabel: 'Concluir',
    };
}

function executarAcao() {
    const { ordem, tipo } = acaoAtual.value;
    const payload = { observacao: acaoObservacao.value || undefined };

    router.post(route(`ordens.${tipo}`, { ordem: ordem.id }), payload, {
        onSuccess: () => { acaoAtual.value = null; acaoObservacao.value = ''; },
    });
}

// ─── formatação ──────────────────────────────────────────────────────────────

function formatQtd(v) {
    if (v == null) return '—';
    return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 3 });
}
function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}
</script>
