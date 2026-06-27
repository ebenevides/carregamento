<template>
    <AppLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Ordens de Carregamento</h1>
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
                            <th class="px-4 py-3 text-left">Motorista</th>
                            <th class="px-4 py-3 text-right">Qtd</th>
                            <th class="px-4 py-3 text-left">Ponto</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Criado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!ordens.data.length">
                            <td colspan="9" class="text-center py-10 text-gray-400">Nenhuma ordem encontrada.</td>
                        </tr>
                        <tr v-for="o in ordens.data" :key="o.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs">{{ o.pedido_numero }}</td>
                            <td class="px-4 py-3">{{ o.cliente_nome }}</td>
                            <td class="px-4 py-3">{{ o.produto_descricao }}</td>
                            <td class="px-4 py-3 font-mono font-bold">{{ o.placa_veiculo }}</td>
                            <td class="px-4 py-3">{{ o.motorista_nome }}</td>
                            <td class="px-4 py-3 text-right tabular-nums">
                                {{ formatQtd(o.quantidade_prevista) }} {{ o.unidade }}
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ o.ponto }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="o.status" :label="o.status_label" />
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-400">{{ formatDate(o.criado_em) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- Paginação -->
            <Paginacao :links="ordens.links" />
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Paginacao from '@/Components/Paginacao.vue';

const props = defineProps({
    ordens: Object,
    pontos: Array,
    filtros: Object,
});

const form = ref({
    data:     props.filtros?.data ?? '',
    ponto_id: props.filtros?.ponto_id ?? '',
    status:   props.filtros?.status ?? '',
});

const statusOpcoes = [
    { value: 'CRIADO',                   label: 'Criado' },
    { value: 'AGUARDANDO_CARREGAMENTO',  label: 'Aguardando Carregamento' },
    { value: 'EM_CARREGAMENTO',          label: 'Em Carregamento' },
    { value: 'CARREGAMENTO_CONCLUIDO',   label: 'Carregamento Concluído' },
    { value: 'AGUARDANDO_PESAGEM_FINAL', label: 'Aguardando Pesagem Final' },
    { value: 'DIVERGENCIA',              label: 'Divergência' },
    { value: 'FINALIZADO',               label: 'Finalizado' },
    { value: 'CANCELADO',                label: 'Cancelado' },
];

function filtrar() {
    router.get(route('ordens'), form.value, { preserveScroll: true });
}

function formatQtd(v) {
    if (v == null) return '—';
    return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 3 });
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}
</script>
