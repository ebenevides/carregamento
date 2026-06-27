<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Filtros -->
            <div class="flex flex-wrap gap-3 items-end bg-white p-4 rounded-xl shadow-sm">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Data</label>
                    <input type="date" v-model="filtros.data" @change="aplicarFiltros"
                        class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <button @click="aplicarFiltros"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                    Atualizar
                </button>
                <span class="text-xs text-gray-400 ml-auto">
                    Última atualização: {{ agora }}
                </span>
            </div>

            <!-- Contadores -->
            <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-5 gap-4">
                <ContadorCard v-for="c in cards" :key="c.label"
                    :label="c.label" :valor="c.valor" :cor="c.cor" :icone="c.icone" />
            </div>

            <!-- Divergências abertas -->
            <div class="bg-white rounded-xl shadow-sm p-6">
                <h2 class="text-lg font-bold text-gray-800 mb-4 flex items-center gap-2">
                    <span class="text-red-500">⚠</span>
                    Divergências abertas
                    <span v-if="divergenciasAbertas.length"
                        class="ml-2 bg-red-100 text-red-700 text-xs font-bold px-2 py-0.5 rounded-full">
                        {{ divergenciasAbertas.length }}
                    </span>
                </h2>

                <div v-if="!divergenciasAbertas.length" class="text-gray-400 text-center py-8">
                    Nenhuma divergência aberta.
                </div>

                <div v-else class="space-y-3">
                    <div v-for="d in divergenciasAbertas" :key="d.id"
                        class="border border-red-200 bg-red-50 rounded-lg p-4">
                        <div class="flex flex-wrap items-start justify-between gap-2">
                            <div>
                                <span class="font-bold text-red-700">{{ d.tipo_label }}</span>
                                <span class="ml-3 text-sm text-gray-600">{{ d.descricao }}</span>
                            </div>
                            <div class="text-right text-sm text-gray-500">
                                <div>Placa: <strong>{{ d.ordem?.placa }}</strong></div>
                                <div>{{ d.ordem?.status_label }}</div>
                            </div>
                        </div>
                        <div class="mt-2 text-xs text-gray-400">
                            Origem: {{ d.origem }} | {{ formatDate(d.created_at) }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import ContadorCard from '@/Components/ContadorCard.vue';

const props = defineProps({
    contadores: Object,
    divergenciasAbertas: Array,
    filtros: Object,
});

const filtros = ref({ data: props.filtros?.data ?? '' });
const agora = ref(new Date().toLocaleTimeString('pt-BR'));

const cards = computed(() => [
    { label: 'Aguardando',    valor: props.contadores.aguardando_carregamento,  cor: 'yellow', icone: '⏳' },
    { label: 'Em carga',      valor: props.contadores.em_carregamento,           cor: 'blue',   icone: '🚛' },
    { label: 'Concluído',     valor: props.contadores.carregamento_concluido,    cor: 'green',  icone: '✅' },
    { label: 'Aguard. pes.',  valor: props.contadores.aguardando_pesagem_final,  cor: 'purple', icone: '⚖' },
    { label: 'Divergências',  valor: props.contadores.divergencias,              cor: 'red',    icone: '⚠' },
    { label: 'Validados',     valor: props.contadores.validado,                  cor: 'teal',   icone: '✓' },
    { label: 'Finalizados',   valor: props.contadores.finalizado,                cor: 'gray',   icone: '🏁' },
    { label: 'Total do dia',  valor: props.contadores.total,                     cor: 'slate',  icone: '📋' },
]);

function aplicarFiltros() {
    agora.value = new Date().toLocaleTimeString('pt-BR');
    router.get(route('dashboard'), filtros.value, { preserveScroll: true });
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR');
}
</script>
