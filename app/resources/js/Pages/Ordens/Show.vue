<template>
    <AppLayout>
        <div class="space-y-5 max-w-5xl mx-auto">

            <!-- Cabeçalho -->
            <div class="flex flex-wrap items-center justify-between gap-3">
                <div class="flex items-center gap-3">
                    <Link :href="route('ordens')"
                        class="text-gray-400 hover:text-gray-700 text-sm">← Voltar</Link>
                    <h1 class="text-xl font-bold text-gray-800">
                        Ordem — {{ ordem.placa_veiculo }}
                    </h1>
                    <StatusBadge :status="ordem.status" :label="ordem.status_label" />
                </div>
                <div class="flex flex-wrap gap-2">
                    <button v-if="podeIniciar" @click="confirmar('iniciar')"
                        class="px-3 py-1.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">
                        Iniciar carregamento
                    </button>
                    <button v-if="podeConcluir" @click="confirmar('concluir')"
                        class="px-3 py-1.5 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700">
                        Concluir carregamento
                    </button>
                    <button v-if="podeLiberarFaturamento" @click="confirmar('liberar-faturamento')"
                        class="px-3 py-1.5 text-sm bg-teal-600 text-white rounded-lg hover:bg-teal-700">
                        Liberar faturamento
                    </button>
                    <button v-if="podeCancelar" @click="confirmar('cancelar')"
                        class="px-3 py-1.5 text-sm bg-red-600 text-white rounded-lg hover:bg-red-700">
                        Cancelar ordem
                    </button>
                    <button v-if="podeRegistrarDivergencia" @click="modalDivergencia = true"
                        class="px-3 py-1.5 text-sm bg-orange-500 text-white rounded-lg hover:bg-orange-600">
                        + Divergência
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

            <!-- Dados principais -->
            <div class="bg-white rounded-xl shadow-sm p-5 grid grid-cols-2 md:grid-cols-3 gap-x-6 gap-y-3 text-sm">
                <Campo label="Pedido nº"        :valor="ordem.pedido_numero" />
                <Campo label="Item"             :valor="ordem.pedido_item" />
                <Campo label="Contrato"         :valor="ordem.contrato_codigo" />
                <Campo label="Ticket Guardian"  :valor="ordem.ticket_guardian" />
                <Campo label="Empresa"          :valor="[ordem.empresa, ordem.filial].filter(Boolean).join(' / ')" />
                <Campo label="Cliente"          :valor="ordem.cliente_nome" />
                <Campo label="Produto (cód.)"   :valor="ordem.produto_codigo" mono />
                <Campo label="Produto"          :valor="ordem.produto_descricao" />
                <Campo label="Qtd. prevista"    :valor="fmtQtd(ordem.quantidade_prevista) + ' ' + ordem.unidade" />
                <Campo label="Placa veículo"    :valor="ordem.placa_veiculo" mono />
                <Campo label="Placa carreta"    :valor="ordem.placa_carreta" />
                <Campo label="Motorista"        :valor="ordem.motorista_nome" />
                <Campo label="Doc. motorista"   :valor="ordem.motorista_documento" />
                <Campo label="Transportadora"   :valor="ordem.transportadora_nome" />
                <Campo label="Ponto"            :valor="ordem.ponto" />
                <Campo label="Pilha"            :valor="ordem.pilha" />
                <Campo label="Operador"         :valor="ordem.operador_nome" />
                <Campo label="Tolerância"       :valor="ordem.tolerancia_percentual ? ordem.tolerancia_percentual + '%' : null" />
            </div>

            <!-- Pesagens -->
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-semibold text-gray-700 mb-3 text-sm uppercase tracking-wide">Pesagens</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <CampoNum label="Tara (kg)"       :valor="ordem.tara" />
                    <CampoNum label="Peso bruto (kg)" :valor="ordem.peso_bruto" />
                    <CampoNum label="Peso líquido (kg)" :valor="ordem.peso_liquido" />
                    <div>
                        <span class="block text-xs text-gray-400 mb-0.5">Tolerância</span>
                        <span v-if="ordem.peso_liquido != null"
                            :class="['font-semibold text-sm', ordem.dentro_tolerancia ? 'text-green-600' : 'text-red-600']">
                            {{ ordem.dentro_tolerancia ? '✓ OK' : '✗ Fora' }}
                        </span>
                        <span v-else class="text-gray-400">—</span>
                    </div>
                </div>
            </div>

            <!-- Datas -->
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-semibold text-gray-700 mb-3 text-sm uppercase tracking-wide">Datas</h2>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                    <Campo label="Criado em"         :valor="fmtDate(ordem.criado_em)" />
                    <Campo label="Iniciado em"       :valor="fmtDate(ordem.iniciado_em)" />
                    <Campo label="Concluído em"      :valor="fmtDate(ordem.concluido_em)" />
                    <Campo label="Pesagem final em"  :valor="fmtDate(ordem.pesagem_final_em)" />
                </div>
            </div>

            <!-- Divergências -->
            <div v-if="ordem.divergencias.length" class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-semibold text-gray-700 mb-3 text-sm uppercase tracking-wide">Divergências</h2>
                <div class="space-y-2">
                    <div v-for="(d, i) in ordem.divergencias" :key="i"
                        class="flex items-start gap-3 p-3 rounded-lg bg-gray-50 text-sm">
                        <span :class="['text-xs px-2 py-0.5 rounded-full font-medium mt-0.5',
                            d.status === 'ABERTA'
                                ? 'bg-red-100 text-red-700'
                                : 'bg-green-100 text-green-700']">
                            {{ d.status }}
                        </span>
                        <div>
                            <div class="font-medium">{{ d.tipo_label }}</div>
                            <div class="text-gray-500 text-xs">{{ d.descricao }}</div>
                            <div class="text-gray-400 text-xs mt-0.5">
                                {{ fmtDate(d.criado_em) }}
                                <span v-if="d.resolvido_em"> → resolvido {{ fmtDate(d.resolvido_em) }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Timeline de eventos -->
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-semibold text-gray-700 mb-3 text-sm uppercase tracking-wide">Histórico</h2>
                <div class="relative">
                    <div class="absolute left-3 top-0 bottom-0 w-px bg-gray-200"></div>
                    <div v-if="!ordem.eventos.length" class="text-gray-400 text-sm pl-8">Nenhum evento.</div>
                    <div v-for="(e, i) in ordem.eventos" :key="i"
                        class="flex gap-4 pb-4 last:pb-0 relative">
                        <div class="w-6 h-6 rounded-full bg-blue-100 border-2 border-blue-300 flex-shrink-0 z-10"></div>
                        <div class="text-sm pb-1">
                            <div class="font-medium text-gray-800">{{ e.tipo_label }}</div>
                            <div v-if="e.status_anterior || e.status_novo" class="text-xs text-gray-500">
                                {{ e.status_anterior }} → {{ e.status_novo }}
                            </div>
                            <div v-if="e.observacao" class="text-xs text-gray-600 mt-0.5">{{ e.observacao }}</div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ fmtDate(e.ocorrido_em) }}
                                <span v-if="e.usuario_nome"> — {{ e.usuario_nome }}</span>
                                <span class="ml-1">({{ e.origem }})</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal confirmar ação -->
        <div v-if="acaoAtual" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 space-y-4">
                <h2 :class="['font-bold text-lg', acaoAtual.cor]">{{ acaoAtual.titulo }}</h2>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Observação (opcional)</label>
                    <textarea v-model="acaoObservacao" rows="2"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div class="flex gap-3 justify-end">
                    <button @click="acaoAtual = null; acaoObservacao = ''"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                    <button @click="executarAcao" :disabled="executando"
                        :class="['px-4 py-2 text-white rounded-lg text-sm disabled:opacity-50', acaoAtual.btnClass]">
                        {{ executando ? 'Aguarde...' : acaoAtual.btnLabel }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal registrar divergência -->
        <div v-if="modalDivergencia" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                <h2 class="font-bold text-lg text-orange-700">Registrar Divergência</h2>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Tipo *</label>
                    <select v-model="divForm.tipo"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500">
                        <option value="">Selecione...</option>
                        <option v-for="t in tiposDivergencia" :key="t.value" :value="t.value">{{ t.label }}</option>
                    </select>
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Descrição *</label>
                    <textarea v-model="divForm.descricao" rows="3"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-orange-500"
                        placeholder="Descreva a divergência..." />
                </div>
                <div class="flex gap-3 justify-end">
                    <button @click="modalDivergencia = false; divForm = { tipo: '', descricao: '' }"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                    <button @click="registrarDivergencia"
                        :disabled="!divForm.tipo || !divForm.descricao.trim() || enviandoDiv"
                        class="px-4 py-2 bg-orange-500 text-white rounded-lg text-sm hover:bg-orange-600 disabled:opacity-50">
                        {{ enviandoDiv ? 'Registrando...' : 'Registrar' }}
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

// ─── componentes de campo inline ─────────────────────────────────────────────

const Campo = {
    props: { label: String, valor: [String, Number, null], mono: Boolean },
    template: `<div>
        <span class="block text-xs text-gray-400 mb-0.5">{{ label }}</span>
        <span :class="['font-medium', mono ? 'font-mono' : '']">{{ valor ?? '—' }}</span>
    </div>`,
};
const CampoNum = {
    props: { label: String, valor: [String, Number, null] },
    template: `<div>
        <span class="block text-xs text-gray-400 mb-0.5">{{ label }}</span>
        <span class="font-medium tabular-nums">{{ valor != null ? Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 3 }) : '—' }}</span>
    </div>`,
};

// ─── props e estado ───────────────────────────────────────────────────────────

const props = defineProps({ ordem: Object });

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

function pode(perfis) {
    return perfis.includes(authUser.value?.perfil);
}

const CANCELAVEIS   = ['CRIADO', 'TARA_REALIZADA', 'AGUARDANDO_CARREGAMENTO', 'DIVERGENCIA'];
const STATUS_ATIVOS = ['CRIADO', 'TARA_REALIZADA', 'AGUARDANDO_CARREGAMENTO', 'EM_CARREGAMENTO',
                       'CARREGAMENTO_CONCLUIDO', 'AGUARDANDO_PESAGEM_FINAL', 'PESAGEM_FINAL_REALIZADA',
                       'VALIDADO', 'DIVERGENCIA'];

const podeCancelar             = computed(() => pode(['ADMIN', 'EXPEDICAO']) && CANCELAVEIS.includes(props.ordem.status));
const podeIniciar              = computed(() => pode(['ADMIN', 'EXPEDICAO', 'OPERADOR']) && props.ordem.status === 'AGUARDANDO_CARREGAMENTO');
const podeConcluir             = computed(() => pode(['ADMIN', 'EXPEDICAO', 'OPERADOR']) && props.ordem.status === 'EM_CARREGAMENTO');
const podeLiberarFaturamento   = computed(() => pode(['ADMIN', 'EXPEDICAO']) && props.ordem.status === 'VALIDADO');
const podeRegistrarDivergencia = computed(() => pode(['ADMIN', 'EXPEDICAO']) && STATUS_ATIVOS.includes(props.ordem.status));

// ─── divergência ─────────────────────────────────────────────────────────────

const modalDivergencia = ref(false);
const enviandoDiv = ref(false);
const divForm = ref({ tipo: '', descricao: '' });

const tiposDivergencia = [
    { value: 'PRODUTO_DIVERGENTE',    label: 'Produto divergente' },
    { value: 'QUANTIDADE_DIVERGENTE', label: 'Quantidade divergente' },
    { value: 'VEICULO_DIVERGENTE',    label: 'Veículo divergente' },
    { value: 'MOTORISTA_DIVERGENTE',  label: 'Motorista divergente' },
    { value: 'TICKET_INVALIDO',       label: 'Ticket inválido' },
    { value: 'TARA_INVALIDA',         label: 'Tara inválida' },
    { value: 'PESO_FORA_TOLERANCIA',  label: 'Peso fora da tolerância' },
    { value: 'PILHA_SEM_PRODUTO',     label: 'Pilha sem produto' },
    { value: 'PONTO_INDISPONIVEL',    label: 'Ponto indisponível' },
    { value: 'PEDIDO_INVALIDO',       label: 'Pedido inválido' },
    { value: 'OUTRO',                 label: 'Outro' },
];

function registrarDivergencia() {
    enviandoDiv.value = true;
    router.post(route('ordens.divergencias.store', { ordem: props.ordem.id }), divForm.value, {
        onFinish: () => { enviandoDiv.value = false; },
        onSuccess: () => { modalDivergencia.value = false; divForm.value = { tipo: '', descricao: '' }; },
    });
}

// ─── ações ───────────────────────────────────────────────────────────────────

const acaoAtual      = ref(null);
const acaoObservacao = ref('');
const executando     = ref(false);

const acoesCfg = {
    iniciar:             { titulo: 'Iniciar carregamento?', cor: 'text-blue-700',  btnClass: 'bg-blue-600 hover:bg-blue-700',  btnLabel: 'Iniciar' },
    concluir:            { titulo: 'Concluir carregamento?', cor: 'text-green-700', btnClass: 'bg-green-600 hover:bg-green-700', btnLabel: 'Concluir' },
    'liberar-faturamento': { titulo: 'Liberar para faturamento?', cor: 'text-teal-700', btnClass: 'bg-teal-600 hover:bg-teal-700', btnLabel: 'Liberar' },
    cancelar:            { titulo: 'Cancelar ordem?', cor: 'text-red-700',   btnClass: 'bg-red-600 hover:bg-red-700',   btnLabel: 'Cancelar' },
};

function confirmar(tipo) {
    acaoAtual.value = { tipo, ...acoesCfg[tipo] };
}

function executarAcao() {
    executando.value = true;
    router.post(route(`ordens.${acaoAtual.value.tipo}`, { ordem: props.ordem.id }), {
        observacao: acaoObservacao.value || undefined,
    }, {
        onFinish: () => { executando.value = false; },
        onSuccess: () => { acaoAtual.value = null; acaoObservacao.value = ''; },
    });
}

// ─── formatação ──────────────────────────────────────────────────────────────

function fmtDate(iso) {
    if (!iso) return null;
    return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}
function fmtQtd(v) {
    if (v == null) return '—';
    return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 3 });
}
</script>
