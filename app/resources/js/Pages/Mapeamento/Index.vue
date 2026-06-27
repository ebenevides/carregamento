<template>
    <AppLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Mapeamento Produto → Pilha/Ponto</h1>
                <button @click="abrirCriar"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                    + Novo mapeamento
                </button>
            </div>

            <p class="text-sm text-gray-500">
                Define qual pilha e ponto de carregamento usar para cada código de produto ao criar uma ordem.
            </p>

            <div v-if="$page.props.flash?.success"
                class="bg-green-50 border border-green-200 text-green-700 px-4 py-2 rounded-lg text-sm">
                {{ $page.props.flash.success }}
            </div>

            <!-- Busca -->
            <div class="bg-white rounded-xl shadow-sm p-4 flex gap-3">
                <input v-model="busca" type="text" placeholder="Buscar por código ou descrição..."
                    class="border rounded-lg px-3 py-2 text-sm flex-1 focus:outline-none focus:ring-2 focus:ring-blue-500" />
                <button @click="filtrar"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                    Buscar
                </button>
            </div>

            <div class="bg-white rounded-xl shadow-sm overflow-x-auto">
                <table class="min-w-full text-sm">
                    <thead class="bg-gray-50 text-gray-600 text-xs uppercase">
                        <tr>
                            <th class="px-4 py-3 text-left">Produto (cód.)</th>
                            <th class="px-4 py-3 text-left">Descrição produto</th>
                            <th class="px-4 py-3 text-left">Pilha</th>
                            <th class="px-4 py-3 text-left">Ponto</th>
                            <th class="px-4 py-3 text-center">Padrão</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!mapeamentos.data.length">
                            <td colspan="7" class="text-center py-10 text-gray-400">
                                Nenhum mapeamento cadastrado.
                            </td>
                        </tr>
                        <tr v-for="m in mapeamentos.data" :key="m.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-mono text-xs font-bold">{{ m.produto_codigo }}</td>
                            <td class="px-4 py-3 text-gray-600">{{ m.produto_descricao ?? '—' }}</td>
                            <td class="px-4 py-3">{{ m.pilha_descricao ?? '—' }}</td>
                            <td class="px-4 py-3">{{ m.ponto_descricao ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span v-if="m.padrao" class="text-blue-600 font-bold text-xs">★ Padrão</span>
                                <span v-else class="text-gray-300 text-xs">—</span>
                            </td>
                            <td class="px-4 py-3">
                                <span :class="['text-xs px-2 py-0.5 rounded-full font-medium',
                                    m.ativo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                                    {{ m.ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex gap-2 justify-end">
                                    <button @click="abrirEditar(m)"
                                        class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                                        Editar
                                    </button>
                                    <button @click="confirmarRemover(m)"
                                        class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200">
                                        Remover
                                    </button>
                                </div>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <Paginacao :links="mapeamentos.links" />
        </div>

        <!-- Modal criar/editar -->
        <div v-if="modalAberto" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-lg p-6 space-y-4">
                <h2 class="font-bold text-lg">{{ editando ? 'Editar' : 'Novo' }} Mapeamento</h2>

                <div class="space-y-3">
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Código produto *</label>
                            <input v-model="form.produto_codigo" type="text" maxlength="30"
                                class="w-full border rounded-lg px-3 py-2 text-sm font-mono"
                                :class="{ 'border-red-400': form.errors.produto_codigo }" />
                            <p v-if="form.errors.produto_codigo" class="text-red-500 text-xs mt-1">{{ form.errors.produto_codigo }}</p>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-500 mb-1">Descrição produto</label>
                            <input v-model="form.produto_descricao" type="text" maxlength="100"
                                class="w-full border rounded-lg px-3 py-2 text-sm" />
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Pilha *</label>
                        <select v-model="form.pilha_produto_id"
                            class="w-full border rounded-lg px-3 py-2 text-sm"
                            :class="{ 'border-red-400': form.errors.pilha_produto_id }">
                            <option :value="null">Selecione...</option>
                            <option v-for="p in pilhas" :key="p.id" :value="p.id">{{ p.descricao }}</option>
                        </select>
                        <p v-if="form.errors.pilha_produto_id" class="text-red-500 text-xs mt-1">{{ form.errors.pilha_produto_id }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Ponto de carregamento *</label>
                        <select v-model="form.ponto_carregamento_id"
                            class="w-full border rounded-lg px-3 py-2 text-sm"
                            :class="{ 'border-red-400': form.errors.ponto_carregamento_id }">
                            <option :value="null">Selecione...</option>
                            <option v-for="pt in pontos" :key="pt.id" :value="pt.id">{{ pt.descricao }}</option>
                        </select>
                        <p v-if="form.errors.ponto_carregamento_id" class="text-red-500 text-xs mt-1">{{ form.errors.ponto_carregamento_id }}</p>
                    </div>
                    <div class="flex items-center gap-4">
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" v-model="form.padrao" class="rounded" />
                            Mapeamento padrão para este produto
                        </label>
                        <label class="flex items-center gap-2 text-sm cursor-pointer">
                            <input type="checkbox" v-model="form.ativo" class="rounded" />
                            Ativo
                        </label>
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
        <div v-if="mapParaRemover" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 space-y-4">
                <h2 class="font-bold text-lg text-red-700">Remover mapeamento?</h2>
                <p class="text-sm text-gray-600">
                    Produto <strong class="font-mono">{{ mapParaRemover.produto_codigo }}</strong>
                    → {{ mapParaRemover.pilha_descricao }} / {{ mapParaRemover.ponto_descricao }}
                </p>
                <div class="flex gap-3 justify-end">
                    <button @click="mapParaRemover = null"
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
import Paginacao from '@/Components/Paginacao.vue';

const props = defineProps({
    mapeamentos: Object,
    pilhas:      Array,
    pontos:      Array,
    filtros:     Object,
});

const busca = ref(props.filtros?.busca ?? '');

function filtrar() {
    router.get(route('mapeamento'), { busca: busca.value || undefined }, { preserveScroll: true });
}

const modalAberto  = ref(false);
const editando     = ref(null);
const mapParaRemover = ref(null);

const form = useForm({
    produto_codigo:         '',
    produto_descricao:      '',
    pilha_produto_id:       null,
    ponto_carregamento_id:  null,
    padrao:                 false,
    ativo:                  true,
});

function abrirCriar() {
    editando.value = null;
    form.reset();
    form.ativo = true;
    modalAberto.value = true;
}

function abrirEditar(m) {
    editando.value = m;
    form.produto_codigo        = m.produto_codigo;
    form.produto_descricao     = m.produto_descricao ?? '';
    form.pilha_produto_id      = m.pilha_produto_id;
    form.ponto_carregamento_id = m.ponto_id;
    form.padrao                = m.padrao;
    form.ativo                 = m.ativo;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    editando.value = null;
    form.reset();
}

function salvar() {
    if (editando.value) {
        form.put(route('mapeamento.update', { mapeamento: editando.value.id }), { onSuccess: fecharModal });
    } else {
        form.post(route('mapeamento.store'), { onSuccess: fecharModal });
    }
}

function confirmarRemover(m) { mapParaRemover.value = m; }

function remover() {
    router.delete(route('mapeamento.destroy', { mapeamento: mapParaRemover.value.id }), {
        onSuccess: () => { mapParaRemover.value = null; },
    });
}
</script>
