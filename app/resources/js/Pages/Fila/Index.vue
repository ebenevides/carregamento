<template>
    <AppLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Fila de Carregamento</h1>
                <div class="flex gap-4 text-sm">
                    <span class="bg-yellow-100 text-yellow-700 px-3 py-1 rounded-full font-medium">
                        ⏳ {{ totais.aguardando }} aguardando
                    </span>
                    <span class="bg-blue-100 text-blue-700 px-3 py-1 rounded-full font-medium">
                        🚛 {{ totais.em_carga }} em carga
                    </span>
                </div>
            </div>

            <div v-if="!pontos.length" class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
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
                        <span :class="['text-xs px-2 py-0.5 rounded-full font-medium',
                            ponto.status === 'ATIVO' ? 'bg-green-100 text-green-700' : 'bg-gray-200 text-gray-500']">
                            {{ ponto.status }}
                        </span>
                    </div>

                    <!-- Ordens na fila -->
                    <div class="divide-y divide-gray-100">
                        <div v-if="!ponto.ordens.length" class="px-4 py-6 text-center text-sm text-gray-400">
                            Fila vazia
                        </div>
                        <div v-for="(o, i) in ponto.ordens" :key="o.id" class="px-4 py-3">
                            <div class="flex items-center gap-2 mb-1">
                                <span class="text-xs text-gray-400 w-5 text-center">{{ i + 1 }}</span>
                                <span class="font-bold text-gray-800">{{ o.placa }}</span>
                                <StatusBadge :status="o.status" :label="o.status_label" />
                            </div>
                            <div class="ml-7 text-xs text-gray-500 space-y-0.5">
                                <div>{{ o.motorista }}</div>
                                <div>{{ o.produto }} — {{ formatQtd(o.quantidade) }} {{ o.unidade }}</div>
                                <div v-if="o.iniciado_em" class="text-blue-500">
                                    Iniciado: {{ formatTime(o.iniciado_em) }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineProps({
    pontos: Array,
    totais: Object,
});

function formatQtd(v) {
    if (v == null) return '—';
    return Number(v).toLocaleString('pt-BR', { minimumFractionDigits: 0 });
}

function formatTime(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleTimeString('pt-BR', { timeStyle: 'short' });
}
</script>
