<template>
    <AppLayout>
        <div class="space-y-5 max-w-5xl mx-auto">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Integração Guardian</h1>
                <button @click="syncTodas" :disabled="sincronizando"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
                    {{ sincronizando ? 'Sincronizando...' : '↻ Sincronizar tudo' }}
                </button>
            </div>

            <!-- Status -->
            <div class="bg-white rounded-xl shadow-sm p-4 flex flex-wrap gap-4 text-sm">
                <div>
                    <span class="text-xs text-gray-400 block">Modo</span>
                    <span :class="['font-semibold', mock_ativo ? 'text-yellow-600' : 'text-green-600']">
                        {{ mock_ativo ? '⚠ MOCK (simulado)' : '✓ PRODUÇÃO (SOAP)' }}
                    </span>
                </div>
                <div class="flex-1">
                    <span class="text-xs text-gray-400 block">WSDL</span>
                    <span class="font-mono text-xs text-gray-600 break-all">{{ wsdl }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-400 block">Aguardando tara</span>
                    <span class="font-bold text-orange-600">{{ pendente_tara.length }}</span>
                </div>
                <div>
                    <span class="text-xs text-gray-400 block">Aguardando pesagem</span>
                    <span class="font-bold text-purple-600">{{ pendente_pesagem.length }}</span>
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

            <!-- Consulta manual de ticket -->
            <div class="bg-white rounded-xl shadow-sm p-5">
                <h2 class="font-semibold text-gray-700 mb-3 text-sm uppercase tracking-wide">Consultar ticket</h2>
                <div class="flex gap-3">
                    <input v-model="ticketConsulta" type="text" placeholder="Número do ticket..."
                        maxlength="30"
                        class="border rounded-lg px-3 py-2 text-sm flex-1 focus:outline-none focus:ring-2 focus:ring-blue-500 font-mono"
                        @keydown.enter="consultarTicket" />
                    <button @click="consultarTicket" :disabled="consultando || !ticketConsulta.trim()"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
                        {{ consultando ? 'Consultando...' : 'Consultar' }}
                    </button>
                </div>

                <!-- Resultado -->
                <div v-if="resultadoConsulta" class="mt-4">
                    <div v-if="!resultadoConsulta.ok"
                        class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg text-sm">
                        ✗ {{ resultadoConsulta.erro }}
                    </div>
                    <div v-else class="bg-green-50 border border-green-200 rounded-lg p-4">
                        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                            <Campo label="Ticket"      :valor="resultadoConsulta.ticket" mono />
                            <Campo label="Status"      :valor="resultadoConsulta.status" />
                            <Campo label="Placa"       :valor="resultadoConsulta.placa" mono />
                            <Campo label="Motorista"   :valor="resultadoConsulta.motorista" />
                            <CampoNum label="Tara (kg)"       :valor="resultadoConsulta.tara_kg" />
                            <CampoNum label="Peso bruto (kg)" :valor="resultadoConsulta.peso_bruto_kg" />
                            <CampoNum label="Peso líq. (kg)"  :valor="resultadoConsulta.peso_liquido_kg" />
                            <Campo label="Entrada" :valor="resultadoConsulta.data_entrada" />
                        </div>
                    </div>
                </div>
            </div>

            <!-- Ordens aguardando tara -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b bg-orange-50 flex items-center justify-between">
                    <h2 class="font-semibold text-orange-700 text-sm uppercase tracking-wide">
                        Aguardando tara ({{ pendente_tara.length }})
                    </h2>
                    <span class="text-xs text-orange-500">Status: CRIADO com ticket, sem tara</span>
                </div>
                <div v-if="!pendente_tara.length" class="px-5 py-6 text-center text-gray-400 text-sm">
                    Nenhuma ordem pendente.
                </div>
                <table v-else class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-2 text-left">Placa</th>
                            <th class="px-4 py-2 text-left">Produto</th>
                            <th class="px-4 py-2 text-left">Ticket</th>
                            <th class="px-4 py-2 text-left">Criado</th>
                            <th class="px-4 py-2 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="o in pendente_tara" :key="o.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-mono font-bold">{{ o.placa }}</td>
                            <td class="px-4 py-2 text-gray-600 text-xs">{{ o.produto }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-blue-600">{{ o.ticket }}</td>
                            <td class="px-4 py-2 text-xs text-gray-400">{{ fmtDate(o.criado_em) }}</td>
                            <td class="px-4 py-2 text-right">
                                <div class="flex gap-2 justify-end">
                                    <Link :href="route('ordens.show', o.id)"
                                        class="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">
                                        Ver
                                    </Link>
                                    <button @click="syncTara(o)"
                                        class="text-xs px-2 py-1 bg-orange-100 text-orange-700 rounded hover:bg-orange-200">
                                        Sync tara
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Ordens aguardando pesagem final -->
            <div class="bg-white rounded-xl shadow-sm overflow-hidden">
                <div class="px-5 py-3 border-b bg-purple-50 flex items-center justify-between">
                    <h2 class="font-semibold text-purple-700 text-sm uppercase tracking-wide">
                        Aguardando pesagem final ({{ pendente_pesagem.length }})
                    </h2>
                    <span class="text-xs text-purple-500">Status: AGUARDANDO_PESAGEM_FINAL</span>
                </div>
                <div v-if="!pendente_pesagem.length" class="px-5 py-6 text-center text-gray-400 text-sm">
                    Nenhuma ordem aguardando pesagem.
                </div>
                <table v-else class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-500 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-2 text-left">Placa</th>
                            <th class="px-4 py-2 text-left">Produto</th>
                            <th class="px-4 py-2 text-left">Ticket</th>
                            <th class="px-4 py-2 text-left">Concluído</th>
                            <th class="px-4 py-2 text-right">Ação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-for="o in pendente_pesagem" :key="o.id" class="hover:bg-gray-50">
                            <td class="px-4 py-2 font-mono font-bold">{{ o.placa }}</td>
                            <td class="px-4 py-2 text-gray-600 text-xs">{{ o.produto }}</td>
                            <td class="px-4 py-2 font-mono text-xs text-blue-600">{{ o.ticket }}</td>
                            <td class="px-4 py-2 text-xs text-gray-400">{{ fmtDate(o.concluido_em) }}</td>
                            <td class="px-4 py-2 text-right">
                                <div class="flex gap-2 justify-end">
                                    <Link :href="route('ordens.show', o.id)"
                                        class="text-xs px-2 py-1 bg-gray-100 text-gray-600 rounded hover:bg-gray-200">
                                        Ver
                                    </Link>
                                    <button @click="syncPesagem(o)"
                                        class="text-xs px-2 py-1 bg-purple-100 text-purple-700 rounded hover:bg-purple-200">
                                        Sync pesagem
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({
    pendente_tara:    Array,
    pendente_pesagem: Array,
    mock_ativo:       Boolean,
    wsdl:             String,
});

// ─── componentes campo inline ─────────────────────────────────────────────────

const Campo = {
    props: { label: String, valor: [String, Number, null], mono: Boolean },
    template: `<div>
        <span class="block text-xs text-gray-500 mb-0.5">{{ label }}</span>
        <span :class="['font-medium text-sm', mono ? 'font-mono' : '']">{{ valor ?? '—' }}</span>
    </div>`,
};
const CampoNum = {
    props: { label: String, valor: [String, Number, null] },
    template: `<div>
        <span class="block text-xs text-gray-500 mb-0.5">{{ label }}</span>
        <span class="font-medium text-sm tabular-nums">{{ valor != null ? Number(valor).toLocaleString('pt-BR', { minimumFractionDigits: 3 }) : '—' }}</span>
    </div>`,
};

// ─── consulta manual ─────────────────────────────────────────────────────────

const ticketConsulta    = ref('');
const resultadoConsulta = ref(null);
const consultando       = ref(false);

async function consultarTicket() {
    if (!ticketConsulta.value.trim()) return;
    consultando.value = true;
    resultadoConsulta.value = null;

    try {
        const res = await fetch(route('integracoes.guardian.consultar-ticket'), {
            method:  'POST',
            headers: {
                'Content-Type': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept':       'application/json',
            },
            body: JSON.stringify({ ticket: ticketConsulta.value.trim() }),
        });
        resultadoConsulta.value = await res.json();
    } catch (e) {
        resultadoConsulta.value = { ok: false, erro: 'Erro de conexão.' };
    } finally {
        consultando.value = false;
    }
}

// ─── ações de sincronização ──────────────────────────────────────────────────

const sincronizando = ref(false);

function syncTara(o) {
    router.post(route('integracoes.guardian.sync-tara', { ordem: o.id }));
}

function syncPesagem(o) {
    router.post(route('integracoes.guardian.sync-pesagem', { ordem: o.id }));
}

function syncTodas() {
    sincronizando.value = true;
    router.post(route('integracoes.guardian.sync-todas'), {}, {
        onFinish: () => { sincronizando.value = false; },
    });
}

// ─── formatação ──────────────────────────────────────────────────────────────

function fmtDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}
</script>
