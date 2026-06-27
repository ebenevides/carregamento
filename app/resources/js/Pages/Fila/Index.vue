<template>
    <AppLayout>
        <div class="space-y-4">
            <!-- Cabeçalho -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h1 class="text-xl font-bold text-gray-800">Fila de Carregamento</h1>
                <div class="flex flex-wrap items-center gap-2">
                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full text-sm font-medium">
                        ⏳ {{ totais.aguardando }} aguardando
                    </span>
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full text-sm font-medium">
                        🚛 {{ totais.em_carga }} em carga
                    </span>
                    <span v-if="totais.pendentes > 0"
                        class="bg-orange-100 text-orange-700 px-3 py-1 rounded-full text-sm font-medium">
                        {{ totais.pendentes }} pendente(s)
                    </span>
                    <button v-if="pode(['ADMIN','EXPEDICAO']) && pendentes.length"
                        @click="modalPendentes = true"
                        class="px-3 py-1.5 bg-orange-500 text-white rounded-lg text-sm hover:bg-orange-600">
                        + Adicionar à fila
                    </button>
                    <button @click="atualizar"
                        class="px-3 py-1.5 bg-gray-200 text-gray-700 rounded-lg text-sm hover:bg-gray-300">
                        ↻ Atualizar
                        <span class="ml-1 text-xs text-gray-400">{{ agora }}</span>
                    </button>
                </div>
            </div>

            <div v-if="$page.props.flash?.success"
                class="bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg text-sm">
                {{ $page.props.flash.success }}
            </div>
            <div v-if="$page.props.flash?.error"
                class="bg-red-50 border border-red-200 text-red-700 px-4 py-2 rounded-lg text-sm">
                {{ $page.props.flash.error }}
            </div>

            <!-- Grade de pontos -->
            <div v-if="!pontos.length"
                class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
                Nenhum ponto de carregamento configurado.
            </div>

            <div class="grid grid-cols-1 lg:grid-cols-2 xl:grid-cols-3 gap-4">
                <div v-for="ponto in pontos" :key="ponto.id"
                    class="bg-white rounded-xl shadow-sm overflow-hidden">

                    <!-- Header do ponto -->
                    <div class="px-4 py-3 border-b flex items-center justify-between"
                        :class="ponto.status === 'ATIVO' ? 'bg-blue-50' : 'bg-gray-100'">
                        <div>
                            <span class="font-bold text-gray-800">{{ ponto.descricao }}</span>
                            <span class="ml-2 text-xs text-gray-400">{{ ponto.codigo }}</span>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-xs text-gray-500">{{ ponto.ordens.length }} na fila</span>
                            <span :class="['text-xs px-2 py-0.5 rounded-full font-medium',
                                ponto.status === 'ATIVO' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500']">
                                {{ ponto.status }}
                            </span>
                        </div>
                    </div>

                    <!-- Ordens na fila -->
                    <div class="divide-y divide-gray-100">
                        <div v-if="!ponto.ordens.length"
                            class="px-4 py-6 text-center text-sm text-gray-400">
                            Fila vazia
                        </div>

                        <div v-for="(o, i) in ponto.ordens" :key="o.id"
                            :class="['px-4 py-3', o.status === 'EM_CARREGAMENTO' ? 'bg-blue-50' : '']">
                            <!-- Posição + placa + status -->
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs text-gray-400 w-5 text-center font-mono">
                                    {{ i + 1 }}
                                </span>
                                <Link :href="route('ordens.show', o.id)"
                                    class="font-bold text-gray-800 hover:text-blue-700">
                                    {{ o.placa }}
                                </Link>
                                <StatusBadge :status="o.status" :label="o.status_label" />
                                <span v-if="o.tem_divergencia"
                                    class="text-xs bg-red-100 text-red-600 px-1.5 py-0.5 rounded font-medium">
                                    ⚠ div
                                </span>
                            </div>

                            <!-- Detalhes -->
                            <div class="ml-7 text-xs text-gray-500 space-y-0.5 mb-2">
                                <div>{{ o.motorista ?? '—' }}</div>
                                <div>{{ o.produto }} — {{ fmtQtd(o.quantidade) }} {{ o.unidade }}</div>
                                <div v-if="o.iniciado_em" class="text-blue-600">
                                    Iniciado: {{ fmtTime(o.iniciado_em) }}
                                </div>
                            </div>

                            <!-- Ações -->
                            <div class="ml-7 flex gap-1.5">
                                <button v-if="podeIniciar(o)"
                                    @click="confirmarAcao(o, 'iniciar')"
                                    class="text-xs px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    Iniciar
                                </button>
                                <button v-if="podeConcluir(o)"
                                    @click="confirmarAcao(o, 'concluir')"
                                    class="text-xs px-2 py-1 bg-green-600 text-white rounded hover:bg-green-700">
                                    Concluir
                                </button>
                                <button v-if="podeCancelar(o)"
                                    @click="confirmarAcao(o, 'cancelar')"
                                    class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">
                                    Cancelar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: ordens pendentes para entrar na fila -->
        <div v-if="modalPendentes" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4 max-h-screen overflow-y-auto">
                <h2 class="font-bold text-lg">Adicionar à fila</h2>
                <p class="text-sm text-gray-500">
                    Ordens com tara e ticket registrados, prontas para entrar na fila.
                </p>

                <div v-if="!pendentes.length" class="text-center text-gray-400 py-4 text-sm">
                    Nenhuma ordem pronta.
                </div>

                <div v-for="p in pendentes" :key="p.id"
                    class="flex items-center justify-between p-3 bg-gray-50 rounded-lg">
                    <div class="text-sm">
                        <div class="font-bold">{{ p.placa }}</div>
                        <div class="text-gray-500 text-xs">{{ p.motorista }}</div>
                        <div class="text-gray-500 text-xs">{{ p.produto }}</div>
                        <div class="text-xs text-blue-600">→ {{ p.ponto }}</div>
                    </div>
                    <button @click="entrarFila(p)"
                        class="px-3 py-1.5 bg-orange-500 text-white rounded-lg text-xs hover:bg-orange-600">
                        Adicionar
                    </button>
                </div>

                <div class="flex justify-end pt-2">
                    <button @click="modalPendentes = false"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Fechar</button>
                </div>
            </div>
        </div>

        <!-- Modal: confirmar ação na fila -->
        <div v-if="acaoAtual" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 space-y-4">
                <h2 :class="['font-bold text-lg', acaoAtual.cor]">{{ acaoAtual.titulo }}</h2>
                <p class="text-sm text-gray-600">
                    Placa <strong>{{ acaoAtual.ordem.placa }}</strong>
                    <span v-if="acaoAtual.ordem.motorista"> — {{ acaoAtual.ordem.motorista }}</span>
                </p>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Observação (opcional)</label>
                    <textarea v-model="acaoObs" rows="2"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div class="flex gap-3 justify-end">
                    <button @click="acaoAtual = null; acaoObs = ''"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                    <button @click="executarAcao" :disabled="executando"
                        :class="['px-4 py-2 text-white rounded-lg text-sm disabled:opacity-50', acaoAtual.btnClass]">
                        {{ executando ? 'Aguarde...' : acaoAtual.btnLabel }}
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    pontos:    Array,
    pendentes: Array,
    totais:    Object,
});

const page     = usePage();
const authUser = computed(() => page.props.auth?.user);

function pode(perfis) {
    return perfis.includes(authUser.value?.perfil);
}

const agora = ref(new Date().toLocaleTimeString('pt-BR', { timeStyle: 'short' }));

function atualizar() {
    agora.value = new Date().toLocaleTimeString('pt-BR', { timeStyle: 'short' });
    router.reload({ preserveScroll: true });
}

// ─── permissões por ordem ────────────────────────────────────────────────────

function podeIniciar(o)  { return pode(['ADMIN','EXPEDICAO','OPERADOR']) && o.status === 'AGUARDANDO_CARREGAMENTO'; }
function podeConcluir(o) { return pode(['ADMIN','EXPEDICAO','OPERADOR']) && o.status === 'EM_CARREGAMENTO'; }
function podeCancelar(o) { return pode(['ADMIN','EXPEDICAO']) && ['AGUARDANDO_CARREGAMENTO','EM_CARREGAMENTO'].includes(o.status); }

// ─── entrar na fila ──────────────────────────────────────────────────────────

const modalPendentes = ref(false);

function entrarFila(p) {
    router.post(route('fila.entrar', { ordem: p.id }), {}, {
        onSuccess: () => { modalPendentes.value = false; },
    });
}

// ─── ações na fila ───────────────────────────────────────────────────────────

const acaoAtual  = ref(null);
const acaoObs    = ref('');
const executando = ref(false);

const cfgAcao = {
    iniciar:  { titulo: 'Iniciar carregamento?',  cor: 'text-blue-700',  btnClass: 'bg-blue-600 hover:bg-blue-700',  btnLabel: 'Iniciar',  rota: 'fila.iniciar'  },
    concluir: { titulo: 'Concluir carregamento?', cor: 'text-green-700', btnClass: 'bg-green-600 hover:bg-green-700', btnLabel: 'Concluir', rota: 'fila.concluir' },
    cancelar: { titulo: 'Cancelar ordem?',        cor: 'text-red-700',   btnClass: 'bg-red-600 hover:bg-red-700',   btnLabel: 'Cancelar', rota: 'fila.cancelar' },
};

function confirmarAcao(ordem, tipo) {
    acaoAtual.value = { ordem, tipo, ...cfgAcao[tipo] };
}

function executarAcao() {
    executando.value = true;
    router.post(route(acaoAtual.value.rota, { ordem: acaoAtual.value.ordem.id }), {
        observacao: acaoObs.value || undefined,
    }, {
        onFinish:  () => { executando.value = false; },
        onSuccess: () => { acaoAtual.value = null; acaoObs.value = ''; },
    });
}

// ─── formatação ──────────────────────────────────────────────────────────────

function fmtQtd(v) {
    if (v == null) return '—';
    return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 0 });
}
function fmtTime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleTimeString('pt-BR', { timeStyle: 'short' });
}
</script>
