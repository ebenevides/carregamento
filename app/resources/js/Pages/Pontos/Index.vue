<template>
    <AppLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Pontos de Carregamento</h1>
                <button @click="abrirCriar"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                    + Novo ponto
                </button>
            </div>

            <!-- Flash -->
            <div v-if="$page.props.flash?.success"
                class="bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg text-sm">
                {{ $page.props.flash.success }}
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Código</th>
                            <th class="px-4 py-3 text-left">Descrição</th>
                            <th class="px-4 py-3 text-left">UB</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Ordens</th>
                            <th class="px-4 py-3 text-left">Observação</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!pontos.length">
                            <td colspan="7" class="text-center py-10 text-gray-400">Nenhum ponto cadastrado.</td>
                        </tr>
                        <tr v-for="p in pontos" :key="p.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs font-bold">{{ p.codigo }}</td>
                            <td class="px-4 py-3 font-medium">{{ p.descricao }}</td>
                            <td class="px-4 py-3 text-xs">{{ p.unidade_britagem ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="p.status" :label="p.status_label" />
                            </td>
                            <td class="px-4 py-3 text-right tabular-nums">{{ p.ordens_carregamento_count }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ p.observacao ?? '—' }}</td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex gap-2 justify-end">
                                    <button v-if="p.status !== 'ATIVO'" @click="ativar(p)"
                                        class="text-xs px-2 py-1 bg-green-100 text-green-700 rounded hover:bg-green-200">
                                        Ativar
                                    </button>
                                    <button v-if="p.status === 'ATIVO'" @click="inativar(p)"
                                        class="text-xs px-2 py-1 bg-yellow-100 text-yellow-700 rounded hover:bg-yellow-200">
                                        Inativar
                                    </button>
                                    <button @click="abrirEditar(p)"
                                        class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                                        Editar
                                    </button>
                                    <button @click="confirmarRemover(p)"
                                        class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">
                                        Remover
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Modal criar/editar -->
        <div v-if="modalAberto" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4">
                <h2 class="font-bold text-lg">{{ editando ? 'Editar' : 'Novo' }} Ponto</h2>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Código *</label>
                        <input v-model="form.codigo" type="text" maxlength="20"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-400': form.errors.codigo }" />
                        <p v-if="form.errors.codigo" class="text-red-500 text-xs mt-1">{{ form.errors.codigo }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Descrição *</label>
                        <input v-model="form.descricao" type="text" maxlength="100"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-400': form.errors.descricao }" />
                        <p v-if="form.errors.descricao" class="text-red-500 text-xs mt-1">{{ form.errors.descricao }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Unidade de Britagem (UB)</label>
                        <input v-model="form.unidade_britagem" type="text" maxlength="10" placeholder="ex.: UB1"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-400': form.errors.unidade_britagem }" />
                        <p v-if="form.errors.unidade_britagem" class="text-red-500 text-xs mt-1">{{ form.errors.unidade_britagem }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Status</label>
                        <select v-model="form.status"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option v-for="s in statuses" :key="s.value" :value="s.value">{{ s.label }}</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Observação</label>
                        <textarea v-model="form.observacao" rows="2"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                    </div>
                </div>

                <div class="flex gap-3 justify-end pt-2">
                    <button @click="fecharModal" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">
                        Cancelar
                    </button>
                    <button @click="salvar" :disabled="form.processing"
                        class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700 disabled:opacity-50">
                        {{ form.processing ? 'Salvando...' : 'Salvar' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- Confirmar remoção -->
        <div v-if="pontoPararemover" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 space-y-4">
                <h2 class="font-bold text-lg text-red-700">Remover ponto?</h2>
                <p class="text-sm text-gray-600">
                    <strong>{{ pontoPararemover.descricao }}</strong> será removido. Ordens vinculadas não são afetadas.
                </p>
                <div class="flex gap-3 justify-end">
                    <button @click="pontoPararemover = null" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">
                        Cancelar
                    </button>
                    <button @click="remover"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">
                        Remover
                    </button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    pontos: Array,
    statuses: Array,
});

const modalAberto = ref(false);
const editando = ref(null);
const pontoPararemover = ref(null);

const form = useForm({
    codigo: '',
    descricao: '',
    unidade_britagem: '',
    status: 'ATIVO',
    observacao: '',
});

function abrirCriar() {
    editando.value = null;
    form.reset();
    form.status = 'ATIVO';
    modalAberto.value = true;
}

function abrirEditar(p) {
    editando.value = p;
    form.codigo           = p.codigo;
    form.descricao        = p.descricao;
    form.unidade_britagem = p.unidade_britagem ?? '';
    form.status           = p.status;
    form.observacao       = p.observacao ?? '';
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    editando.value = null;
    form.reset();
}

function salvar() {
    if (editando.value) {
        form.put(route('pontos.update', { pontoCarregamento: editando.value.id }), {
            onSuccess: fecharModal,
        });
    } else {
        form.post(route('pontos.store'), {
            onSuccess: fecharModal,
        });
    }
}

function confirmarRemover(p) {
    pontoPararemover.value = p;
}

function remover() {
    router.delete(route('pontos.destroy', { pontoCarregamento: pontoPararemover.value.id }), {
        onSuccess: () => { pontoPararemover.value = null; },
    });
}

function ativar(p) {
    router.post(route('pontos.ativar', { pontoCarregamento: p.id }));
}

function inativar(p) {
    router.post(route('pontos.inativar', { pontoCarregamento: p.id }));
}
</script>
