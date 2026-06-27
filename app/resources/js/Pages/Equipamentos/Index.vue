<template>
    <AppLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Equipamentos</h1>
                <button @click="abrirCriar"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                    + Novo equipamento
                </button>
            </div>

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
                            <th class="px-4 py-3 text-left">Tipo</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!equipamentos.length">
                            <td colspan="5" class="text-center py-10 text-gray-400">
                                Nenhum equipamento cadastrado.
                            </td>
                        </tr>
                        <tr v-for="e in equipamentos" :key="e.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs font-bold">{{ e.codigo }}</td>
                            <td class="px-4 py-3 font-medium">{{ e.descricao }}</td>
                            <td class="px-4 py-3 text-gray-500 text-xs">{{ e.tipo ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span :class="['text-xs px-2 py-0.5 rounded-full font-medium',
                                    e.ativo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                                    {{ e.ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex gap-2 justify-end">
                                    <button @click="abrirEditar(e)"
                                        class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                                        Editar
                                    </button>
                                    <button @click="confirmarRemover(e)"
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
                <h2 class="font-bold text-lg">{{ editando ? 'Editar' : 'Novo' }} Equipamento</h2>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Código *</label>
                            <input v-model="form.codigo" type="text" maxlength="20"
                                class="w-full border rounded-lg px-3 py-2 text-sm"
                                :class="{ 'border-red-400': form.errors.codigo }" />
                            <p v-if="form.errors.codigo" class="text-red-500 text-xs mt-1">{{ form.errors.codigo }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Tipo</label>
                            <input v-model="form.tipo" type="text" maxlength="50"
                                class="w-full border rounded-lg px-3 py-2 text-sm"
                                placeholder="ex: CARREGADEIRA" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Descrição *</label>
                        <input v-model="form.descricao" type="text" maxlength="100"
                            class="w-full border rounded-lg px-3 py-2 text-sm"
                            :class="{ 'border-red-400': form.errors.descricao }" />
                        <p v-if="form.errors.descricao" class="text-red-500 text-xs mt-1">{{ form.errors.descricao }}</p>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.ativo" id="eqAtivo" class="rounded" />
                        <label for="eqAtivo" class="text-sm cursor-pointer">Equipamento ativo</label>
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
        <div v-if="equipParaRemover" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 space-y-4">
                <h2 class="font-bold text-lg text-red-700">Remover equipamento?</h2>
                <p class="text-sm text-gray-600">
                    <strong>{{ equipParaRemover.descricao }}</strong> ({{ equipParaRemover.codigo }}) será removido.
                </p>
                <div class="flex gap-3 justify-end">
                    <button @click="equipParaRemover = null"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                    <button @click="remover"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Remover</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref } from 'vue';
import { useForm, router } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';

const props = defineProps({ equipamentos: Array });

const modalAberto   = ref(false);
const editando      = ref(null);
const equipParaRemover = ref(null);

const form = useForm({ codigo: '', descricao: '', tipo: '', ativo: true });

function abrirCriar() {
    editando.value = null;
    form.reset();
    form.ativo = true;
    modalAberto.value = true;
}

function abrirEditar(e) {
    editando.value = e;
    form.codigo   = e.codigo;
    form.descricao = e.descricao;
    form.tipo     = e.tipo ?? '';
    form.ativo    = e.ativo;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    editando.value = null;
    form.reset();
}

function salvar() {
    if (editando.value) {
        form.put(route('equipamentos.update', { equipamento: editando.value.id }), { onSuccess: fecharModal });
    } else {
        form.post(route('equipamentos.store'), { onSuccess: fecharModal });
    }
}

function confirmarRemover(e) { equipParaRemover.value = e; }

function remover() {
    router.delete(route('equipamentos.destroy', { equipamento: equipParaRemover.value.id }), {
        onSuccess: () => { equipParaRemover.value = null; },
    });
}
</script>
