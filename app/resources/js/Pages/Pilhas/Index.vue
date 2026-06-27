<template>
    <AppLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Pilhas de Produto</h1>
                <button @click="abrirCriar"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                    + Nova pilha
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
                            <th class="px-4 py-3 text-left">Produto</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-left">Pontos vinculados</th>
                            <th class="px-4 py-3 text-right">Ações</th>
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
                            <td class="px-4 py-3 text-right">
                                <div class="flex gap-2 justify-end">
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
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4 max-h-screen overflow-y-auto">
                <h2 class="font-bold text-lg">{{ editando ? 'Editar' : 'Nova' }} Pilha</h2>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Código *</label>
                            <input v-model="form.codigo" type="text" maxlength="20"
                                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                                :class="{ 'border-red-400': form.errors.codigo }" />
                            <p v-if="form.errors.codigo" class="text-red-500 text-xs mt-1">{{ form.errors.codigo }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Status</label>
                            <select v-model="form.ativa"
                                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <option :value="true">Ativa</option>
                                <option :value="false">Inativa</option>
                            </select>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Descrição *</label>
                        <input v-model="form.descricao" type="text" maxlength="100"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-400': form.errors.descricao }" />
                        <p v-if="form.errors.descricao" class="text-red-500 text-xs mt-1">{{ form.errors.descricao }}</p>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Código produto</label>
                            <input v-model="form.produto_codigo" type="text" maxlength="30"
                                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Descrição produto</label>
                            <input v-model="form.produto_descricao" type="text" maxlength="100"
                                class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Pontos vinculados</label>
                        <div class="border rounded-lg p-3 space-y-2 max-h-40 overflow-y-auto">
                            <label v-if="!pontos.length" class="text-xs text-gray-400">
                                Nenhum ponto cadastrado.
                            </label>
                            <label v-for="pt in pontos" :key="pt.id"
                                class="flex items-center gap-2 text-sm cursor-pointer hover:bg-gray-50 px-1 rounded">
                                <input type="checkbox" :value="pt.id" v-model="form.ponto_ids" class="rounded" />
                                {{ pt.descricao }}
                            </label>
                        </div>
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
        <div v-if="pilhaParaRemover" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 space-y-4">
                <h2 class="font-bold text-lg text-red-700">Remover pilha?</h2>
                <p class="text-sm text-gray-600">
                    <strong>{{ pilhaParaRemover.descricao }}</strong> será removida.
                </p>
                <div class="flex gap-3 justify-end">
                    <button @click="pilhaParaRemover = null" class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">
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

const props = defineProps({
    pilhas: Array,
    pontos: Array,
});

const modalAberto = ref(false);
const editando = ref(null);
const pilhaParaRemover = ref(null);

const form = useForm({
    codigo:             '',
    descricao:          '',
    produto_codigo:     '',
    produto_descricao:  '',
    ativa:              true,
    observacao:         '',
    ponto_ids:          [],
});

function abrirCriar() {
    editando.value = null;
    form.reset();
    form.ativa = true;
    form.ponto_ids = [];
    modalAberto.value = true;
}

function abrirEditar(p) {
    editando.value = p;
    form.codigo            = p.codigo;
    form.descricao         = p.descricao;
    form.produto_codigo    = p.produto_codigo ?? '';
    form.produto_descricao = p.produto_descricao ?? '';
    form.ativa             = p.ativa;
    form.observacao        = p.observacao ?? '';
    form.ponto_ids         = p.pontos.map(pt => pt.id);
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    editando.value = null;
    form.reset();
}

function salvar() {
    if (editando.value) {
        form.put(route('pilhas.update', { pilhaProduto: editando.value.id }), {
            onSuccess: fecharModal,
        });
    } else {
        form.post(route('pilhas.store'), {
            onSuccess: fecharModal,
        });
    }
}

function confirmarRemover(p) {
    pilhaParaRemover.value = p;
}

function remover() {
    router.delete(route('pilhas.destroy', { pilhaProduto: pilhaParaRemover.value.id }), {
        onSuccess: () => { pilhaParaRemover.value = null; },
    });
}
</script>
