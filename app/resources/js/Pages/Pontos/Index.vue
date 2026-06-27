<template>
    <AppLayout>
        <div class="space-y-4">
            <h1 class="text-xl font-bold text-gray-800">Pontos de Carregamento</h1>

            <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Código</th>
                            <th class="px-4 py-3 text-left">Descrição</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Ordens</th>
                            <th class="px-4 py-3 text-left">Observação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!pontos.length">
                            <td colspan="5" class="text-center py-10 text-gray-400">Nenhum ponto cadastrado.</td>
                        </tr>
                        <tr v-for="p in pontos" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs font-bold">{{ p.codigo }}</td>
                            <td class="px-4 py-3 font-medium">{{ p.descricao }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="p.status" :label="p.status_label" />
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ p.ordens_carregamento_count }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ p.observacao ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

defineProps({ pontos: Array });
</script>
