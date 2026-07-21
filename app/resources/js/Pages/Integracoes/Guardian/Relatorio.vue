<template>
    <AppLayout>
        <div class="space-y-5 max-w-6xl mx-auto">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Relatório Guardian por período</h1>
                <div class="flex items-center gap-4">
                    <a :href="pdfUrl"
                        class="px-4 py-2 bg-gray-700 text-white rounded-lg text-sm hover:bg-gray-800">
                        ⬇ Baixar PDF
                    </a>
                    <Link :href="route('integracoes.guardian')"
                        class="text-sm text-blue-600 hover:underline">← Voltar</Link>
                </div>
            </div>

            <div v-if="mock_ativo"
                class="bg-yellow-50 border border-yellow-200 text-yellow-700 px-4 py-2 rounded-lg text-sm">
                ⚠ MOCK ativo — dados sintéticos, não são tickets reais do Guardian.
            </div>
            <div v-if="erro"
                class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                ✗ {{ erro }}
            </div>

            <!-- Filtro de período -->
            <div class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap items-end gap-3">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Data de</label>
                    <input type="date" v-model="dataDe"
                        class="border rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">até</label>
                    <input type="date" v-model="dataAte"
                        class="border rounded-lg px-3 py-1.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>

                <div class="flex gap-1">
                    <button @click="navegar(-1)" title="Período anterior"
                        class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                        ← Anterior
                    </button>
                    <button @click="irParaHoje" title="Voltar para hoje"
                        class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                        Hoje
                    </button>
                    <button @click="navegar(1)" title="Próximo período"
                        class="px-3 py-1.5 bg-gray-100 text-gray-700 rounded-lg text-sm hover:bg-gray-200">
                        Próximo →
                    </button>
                </div>

                <button @click="buscar" :disabled="buscando"
                    class="ml-auto px-4 py-1.5 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
                    {{ buscando ? 'Buscando...' : 'Filtrar' }}
                </button>
            </div>

            <!-- Métricas -->
            <div v-if="metricas" class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <span class="text-xs text-gray-400 block">Total de tickets</span>
                    <span class="text-2xl font-bold text-gray-800">{{ metricas.total_tickets }}</span>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <span class="text-xs text-gray-400 block">Com pesagem final</span>
                    <span class="text-2xl font-bold text-gray-800">{{ metricas.total_com_pesagem }}</span>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <span class="text-xs text-gray-400 block">Peso líquido total</span>
                    <span class="text-2xl font-bold text-green-700">{{ fmtPeso(metricas.peso_liquido_total_kg) }}</span>
                </div>
                <div class="bg-white rounded-xl shadow-sm p-4">
                    <span class="text-xs text-gray-400 block">Tempo médio de pátio</span>
                    <span class="text-2xl font-bold text-purple-700">{{ fmtMin(metricas.tempo_medio_patio_min) }}</span>
                </div>
            </div>

            <!-- Throughput por hora -->
            <div v-if="metricas && horasThroughput.length" class="bg-white rounded-xl shadow-sm p-4">
                <h2 class="font-semibold text-gray-700 mb-3 text-sm uppercase tracking-wide">Throughput por hora</h2>
                <div class="flex items-end gap-1 h-28">
                    <div v-for="[hora, qtd] in horasThroughput" :key="hora"
                        class="flex-1 flex flex-col items-center justify-end gap-1">
                        <span class="text-xs text-gray-500">{{ qtd }}</span>
                        <div class="w-full bg-blue-500 rounded-t"
                            :style="{ height: (qtd / maxThroughput * 100) + '%' }"></div>
                        <span class="text-[10px] text-gray-400">{{ hora }}</span>
                    </div>
                </div>
            </div>

            <!-- Lista de tickets -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b bg-gray-50 flex items-center justify-between">
                    <h2 class="font-semibold text-gray-700 text-sm uppercase tracking-wide">
                        Tickets ({{ tickets.length }})
                    </h2>
                </div>
                <div v-if="!tickets.length" class="px-5 py-6 text-center text-gray-400 text-sm">
                    Nenhum ticket no período selecionado.
                </div>
                <div v-else class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                            <tr>
                                <th class="px-4 py-2 text-left">Ticket</th>
                                <th class="px-4 py-2 text-left">Status</th>
                                <th class="px-4 py-2 text-left">Placa</th>
                                <th class="px-4 py-2 text-left">Motorista</th>
                                <th class="px-4 py-2 text-left">UB</th>
                                <th class="px-4 py-2 text-left">Usuário Protheus</th>
                                <th class="px-4 py-2 text-left">Observação</th>
                                <th class="px-4 py-2 text-right">Qtd. a carregar</th>
                                <th class="px-4 py-2 text-right">Tara</th>
                                <th class="px-4 py-2 text-right">Peso bruto</th>
                                <th class="px-4 py-2 text-right">Peso líq.</th>
                                <th class="px-4 py-2 text-left">Entrada</th>
                                <th class="px-4 py-2 text-right">Pátio</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="t in tickets" :key="t.ticket" class="hover:bg-gray-50">
                                <td class="px-4 py-2 font-mono text-xs text-blue-600">{{ t.ticket }}</td>
                                <td class="px-4 py-2 text-xs">{{ t.status }}</td>
                                <td class="px-4 py-2 font-mono font-bold">{{ t.placa ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-600 text-xs">{{ t.motorista ?? '—' }}</td>
                                <td class="px-4 py-2 text-xs">{{ t.ub ?? '—' }}</td>
                                <td class="px-4 py-2 text-gray-600 text-xs">{{ t.usuario_protheus ?? '—' }}</td>
                                <td class="px-4 py-2 font-mono text-xs">{{ t.observacao ?? '—' }}</td>
                                <td class="px-4 py-2 text-right font-mono tabular-nums">{{ fmtPeso(t.quantidade_a_carregar) }}</td>
                                <td class="px-4 py-2 text-right font-mono tabular-nums">{{ fmtPeso(t.tara_kg) }}</td>
                                <td class="px-4 py-2 text-right font-mono tabular-nums">{{ fmtPeso(t.peso_bruto_kg) }}</td>
                                <td class="px-4 py-2 text-right font-mono tabular-nums font-bold text-green-700">{{ fmtPeso(t.peso_liquido_kg) }}</td>
                                <td class="px-4 py-2 text-xs text-gray-400">{{ fmtDate(t.data_entrada) }}</td>
                                <td class="px-4 py-2 text-right text-xs text-gray-500">{{ fmtMin(t.tempo_patio_min) }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    tickets:    Array,
    metricas:   Object,
    erro:       String,
    filtros:    Object,
    mock_ativo: Boolean,
});

const dataDe  = ref(props.filtros.data_de);
const dataAte = ref(props.filtros.data_ate);
const buscando = ref(false);

function buscar() {
    buscando.value = true;
    router.get(route('integracoes.guardian.relatorio'), {
        data_de:  dataDe.value,
        data_ate: dataAte.value,
    }, {
        preserveState: true,
        onFinish: () => { buscando.value = false; },
    });
}

// ─── navegação incremental/decremental do range de datas ─────────────────────

function diasNoRange() {
    const de  = new Date(dataDe.value + 'T00:00:00');
    const ate = new Date(dataAte.value + 'T00:00:00');
    return Math.round((ate - de) / 86400000) + 1;
}

function toInputDate(d) {
    return d.toISOString().slice(0, 10);
}

function navegar(direcao) {
    const dias = diasNoRange();
    const de  = new Date(dataDe.value + 'T00:00:00');
    const ate = new Date(dataAte.value + 'T00:00:00');
    de.setDate(de.getDate() + direcao * dias);
    ate.setDate(ate.getDate() + direcao * dias);
    dataDe.value  = toInputDate(de);
    dataAte.value = toInputDate(ate);
    buscar();
}

function irParaHoje() {
    const hoje = toInputDate(new Date());
    dataDe.value  = hoje;
    dataAte.value = hoje;
    buscar();
}

// ─── métricas derivadas ────────────────────────────────────────────────────

const horasThroughput = computed(() => Object.entries(props.metricas?.throughput_por_hora ?? {}));
const maxThroughput   = computed(() => Math.max(1, ...horasThroughput.value.map(([, qtd]) => qtd)));

const pdfUrl = computed(() => route('integracoes.guardian.relatorio.pdf', {
    data_de:  dataDe.value,
    data_ate: dataAte.value,
}));

// ─── formatação ──────────────────────────────────────────────────────────────

function fmtDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}

function fmtPeso(val) {
    if (val == null) return '—';
    return Number(val).toLocaleString('pt-BR', { minimumFractionDigits: 0, maximumFractionDigits: 3 }) + ' kg';
}

function fmtMin(min) {
    if (min == null) return '—';
    const h = Math.floor(min / 60);
    const m = Math.round(min % 60);
    return h > 0 ? `${h}h${String(m).padStart(2, '0')}` : `${m}min`;
}
</script>
