<template>
    <AppLayout>
        <div class="space-y-4">
            <h1 class="text-xl font-bold text-gray-800">Divergências</h1>

            <div v-if="$page.props.flash?.success"
                class="bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg text-sm">
                {{ $page.props.flash.success }}
            </div>

            <!-- Filtros -->
            <div class="bg-white rounded-xl shadow-sm p-4 flex gap-3 items-end">
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Status</label>
                    <select v-model="statusFiltro" @change="filtrar"
                        class="border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        <option value="ABERTA">Abertas</option>
                        <option value="RESOLVIDA">Resolvidas</option>
                        <option value="CANCELADA">Canceladas</option>
                        <option value="TODAS">Todas</option>
                    </select>
                </div>
            </div>

            <!-- Lista -->
            <div class="space-y-3">
                <div v-if="!divergencias.data.length"
                    class="bg-white rounded-xl shadow-sm p-10 text-center text-gray-400">
                    Nenhuma divergência encontrada.
                </div>

                <div v-for="d in divergencias.data" :key="d.id"
                    :class="['rounded-xl shadow-sm p-4 border',
                        d.status === 'ABERTA'    ? 'bg-red-50 border-red-200'    :
                        d.status === 'CANCELADA' ? 'bg-gray-50 border-gray-200 opacity-70' :
                                                   'bg-white border-gray-200']">
                    <div class="flex flex-wrap items-start justify-between gap-3">
                        <div class="space-y-1">
                            <div class="flex items-center gap-2">
                                <span class="font-bold"
                                    :class="d.status === 'ABERTA' ? 'text-red-700' : 'text-gray-700'">
                                    {{ d.tipo_label }}
                                </span>
                                <StatusBadge :status="d.status" :label="d.status_label" />
                            </div>
                            <p class="text-sm text-gray-600">{{ d.descricao }}</p>
                            <p v-if="d.resolucao" class="text-sm text-green-700 mt-1">✓ {{ d.resolucao }}</p>
                        </div>
                        <div class="text-sm text-right text-gray-500 space-y-0.5">
                            <div v-if="d.ordem">
                                <Link :href="route('ordens.show', d.ordem.id)"
                                    class="font-bold text-blue-700 hover:underline">
                                    {{ d.ordem.placa }}
                                </Link>
                                <span> — {{ d.ordem.motorista }}</span>
                            </div>
                            <div v-if="d.ordem">{{ d.ordem.produto }}</div>
                            <div v-if="d.ordem">
                                <StatusBadge :status="d.ordem.status" :label="d.ordem.status_label" />
                            </div>
                        </div>
                    </div>

                    <div class="mt-3 flex items-center justify-between text-xs text-gray-400">
                        <span>Origem: {{ d.origem }} | {{ formatDate(d.created_at) }}</span>
                        <div class="flex gap-2">
                            <button v-if="d.status === 'ABERTA'"
                                @click="abrirCancelar(d)"
                                class="px-3 py-1 bg-gray-200 text-gray-700 rounded-lg text-xs hover:bg-gray-300">
                                Cancelar
                            </button>
                            <button v-if="d.status === 'ABERTA'"
                                @click="abrirResolver(d)"
                                class="px-3 py-1 bg-green-600 text-white rounded-lg text-xs hover:bg-green-700">
                                Resolver
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <Paginacao :links="divergencias.links" />
        </div>

        <!-- Modal resolver -->
        <div v-if="modalResolver" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                <h2 class="font-bold text-lg">Resolver Divergência</h2>
                <p class="text-sm text-gray-600">
                    {{ divergenciaSelecionada?.tipo_label }}: {{ divergenciaSelecionada?.descricao }}
                </p>

                <div>
                    <label class="block text-xs text-gray-500 mb-1">Resolução *</label>
                    <textarea v-model="resolucao" rows="3"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-green-500"
                        placeholder="Descreva como a divergência foi resolvida..." />
                </div>

                <label class="flex items-center gap-2 text-sm cursor-pointer">
                    <input type="checkbox" v-model="liberarOrdem" class="rounded" />
                    Liberar ordem após resolver
                </label>

                <div class="flex gap-3 justify-end pt-2">
                    <button @click="fecharModais"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                    <button @click="confirmarResolver" :disabled="!resolucao.trim()"
                        class="px-4 py-2 bg-green-600 text-white rounded-lg text-sm hover:bg-green-700 disabled:opacity-50">
                        Confirmar
                    </button>
                </div>
            </div>
        </div>

        <!-- Modal cancelar -->
        <div v-if="modalCancelar" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 space-y-4">
                <h2 class="font-bold text-lg text-gray-700">Cancelar divergência?</h2>
                <p class="text-sm text-gray-600">
                    {{ divergenciaSelecionada?.tipo_label }}: {{ divergenciaSelecionada?.descricao }}
                </p>
                <div>
                    <label class="block text-xs text-gray-500 mb-1">Motivo (opcional)</label>
                    <input v-model="motivoCancelamento" type="text"
                        class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                </div>
                <div class="flex gap-3 justify-end">
                    <button @click="fecharModais"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Voltar</button>
                    <button @click="confirmarCancelar"
                        class="px-4 py-2 bg-gray-700 text-white rounded-lg text-sm hover:bg-gray-800">
                        Cancelar divergência
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { router, usePage, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';
import Paginacao from '@/Components/Paginacao.vue';

const props = defineProps({
    divergencias: Object,
    filtros:      Object,
});

const page = usePage();
const statusFiltro = ref(props.filtros?.status ?? 'ABERTA');

const modalResolver  = ref(false);
const modalCancelar  = ref(false);
const divergenciaSelecionada = ref(null);
const resolucao      = ref('');
const liberarOrdem   = ref(false);
const motivoCancelamento = ref('');

function filtrar() {
    router.get(route('divergencias'), { status: statusFiltro.value }, { preserveScroll: true });
}

function abrirResolver(d) {
    divergenciaSelecionada.value = d;
    resolucao.value = '';
    liberarOrdem.value = false;
    modalResolver.value = true;
}

function abrirCancelar(d) {
    divergenciaSelecionada.value = d;
    motivoCancelamento.value = '';
    modalCancelar.value = true;
}

function fecharModais() {
    modalResolver.value = false;
    modalCancelar.value = false;
    divergenciaSelecionada.value = null;
}

function confirmarResolver() {
    router.post(
        route('divergencias.resolver', { divergencia: divergenciaSelecionada.value.id }),
        {
            resolucao:  resolucao.value,
            usuario_id: page.props.auth?.user?.id,
            liberar:    liberarOrdem.value,
        },
        { onSuccess: fecharModais, preserveScroll: true }
    );
}

function confirmarCancelar() {
    router.post(
        route('divergencias.cancelar', { divergencia: divergenciaSelecionada.value.id }),
        { motivo: motivoCancelamento.value || undefined },
        { onSuccess: fecharModais, preserveScroll: true }
    );
}

function formatDate(iso) {
    if (!iso) return '—';
    return new Date(iso).toLocaleString('pt-BR', { dateStyle: 'short', timeStyle: 'short' });
}
</script>
