<template>
    <AppLayout>
        <div class="space-y-4">
            <h1 class="text-xl font-bold text-gray-800">Pilhas de Produto</h1>

            <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Código</th>
                            <th class="px-4 py-3 text-left">Descrição</th>
                            <th class="px-4 py-3 text-left">Produto</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Pontos vinculados</th>
                            <th class="px-4 py-3 text-left">Observação</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!pilhas.length">
                            <td colspan="6" class="text-center py-10 text-gray-400">Nenhuma pilha cadastrada.</td>
                        </tr>
                        <tr v-for="p in pilhas" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs font-bold">{{ p.codigo }}</td>
                            <td class="px-4 py-3 font-medium">{{ p.descricao }}</td>
                            <td class="px-4 py-3 text-xs text-gray-600">
                                <div class="font-mono">{{ p.produto_codigo }}</div>
                                <div>{{ p.produto_descricao }}</div>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['text-xs px-2 py-0.5 rounded-full font-medium',
                                    p.ativa ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                                    {{ p.ativa ? 'Ativa' : 'Inativa' }}
                                </span>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex flex-wrap gap-1">
                                    <span v-for="pt in p.pontos" :key="pt.id"
                                        class="text-xs bg-blue-50 text-blue-700 px-2 py-0.5 rounded">
                                        {{ pt.descricao }}
                                    </span>
                                    <span v-if="!p.pontos.length" class="text-xs text-gray-400">—</span>
                                </div>
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ p.observacao ?? '—' }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import AppLayout from '@/Layouts/AppLayout.vue';

defineProps({ pilhas: Array });
</script>
