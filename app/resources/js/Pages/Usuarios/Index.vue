<template>
    <AppLayout>
        <div class="space-y-4">
            <div class="flex items-center justify-between">
                <h1 class="text-xl font-bold text-gray-800">Usuários</h1>
                <button @click="abrirCriar"
                    class="px-4 py-2 bg-blue-600 text-white rounded-lg text-sm hover:bg-blue-700">
                    + Novo usuário
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
                            <th class="px-4 py-3 text-left">Nome</th>
                            <th class="px-4 py-3 text-left">E-mail</th>
                            <th class="px-4 py-3 text-left">Perfil</th>
                            <th class="px-4 py-3 text-left">Ponto</th>
                            <th class="px-4 py-3 text-left">Status</th>
                            <th class="px-4 py-3 text-right">Ações</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-100">
                        <tr v-if="!usuarios.length">
                            <td colspan="6" class="text-center py-10 text-gray-400">Nenhum usuário.</td>
                        </tr>
                        <tr v-for="u in usuarios" :key="u.id" class="hover:bg-gray-50">
                            <td class="px-4 py-3 font-medium">{{ u.name }}</td>
                            <td class="px-4 py-3 text-gray-500">{{ u.email }}</td>
                            <td class="px-4 py-3">
                                <StatusBadge :status="u.perfil" :label="u.perfil_label" />
                            </td>
                            <td class="px-4 py-3 text-xs text-gray-500">{{ u.ponto_descricao ?? '—' }}</td>
                            <td class="px-4 py-3">
                                <span :class="['text-xs px-2 py-0.5 rounded-full font-medium',
                                    u.ativo ? 'bg-green-100 text-green-700' : 'bg-gray-100 text-gray-500']">
                                    {{ u.ativo ? 'Ativo' : 'Inativo' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-right">
                                <div class="flex gap-2 justify-end">
                                    <button @click="toggleAtivo(u)"
                                        :class="['text-xs px-2 py-1 rounded',
                                            u.ativo
                                                ? 'bg-yellow-100 text-yellow-700 hover:bg-yellow-200'
                                                : 'bg-green-100 text-green-700 hover:bg-green-200']">
                                        {{ u.ativo ? 'Desativar' : 'Ativar' }}
                                    </button>
                                    <button @click="abrirEditar(u)"
                                        class="text-xs px-2 py-1 bg-blue-100 text-blue-700 rounded hover:bg-blue-200">
                                        Editar
                                    </button>
                                    <button @click="confirmarRemover(u)"
                                        :disabled="u.id === authUser?.id"
                                        class="text-xs px-2 py-1 bg-red-100 text-red-700 rounded hover:bg-red-200 disabled:opacity-40 disabled:cursor-not-allowed">
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
            <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6 space-y-4 max-h-screen overflow-y-auto">
                <h2 class="font-bold text-lg">{{ editando ? 'Editar' : 'Novo' }} Usuário</h2>

                <div class="space-y-3">
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Nome *</label>
                        <input v-model="form.name" type="text"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-400': form.errors.name }" />
                        <p v-if="form.errors.name" class="text-red-500 text-xs mt-1">{{ form.errors.name }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">E-mail *</label>
                        <input v-model="form.email" type="email"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-400': form.errors.email }" />
                        <p v-if="form.errors.email" class="text-red-500 text-xs mt-1">{{ form.errors.email }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">
                            Senha {{ editando ? '(deixe em branco para manter)' : '*' }}
                        </label>
                        <input v-model="form.password" type="password" autocomplete="new-password"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-400': form.errors.password }" />
                        <p v-if="form.errors.password" class="text-red-500 text-xs mt-1">{{ form.errors.password }}</p>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 mb-1">Perfil *</label>
                        <select v-model="form.perfil"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"
                            :class="{ 'border-red-400': form.errors.perfil }">
                            <option value="">Selecione...</option>
                            <option v-for="p in perfis" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                        <p v-if="form.errors.perfil" class="text-red-500 text-xs mt-1">{{ form.errors.perfil }}</p>
                    </div>
                    <div v-if="form.perfil === 'OPERADOR'">
                        <label class="block text-xs text-gray-500 mb-1">Ponto de carregamento</label>
                        <select v-model="form.ponto_carregamento_id"
                            class="w-full border rounded-lg px-3 py-2 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option :value="null">Nenhum</option>
                            <option v-for="pt in pontos" :key="pt.id" :value="pt.id">{{ pt.descricao }}</option>
                        </select>
                    </div>
                    <div class="flex items-center gap-2">
                        <input type="checkbox" v-model="form.ativo" id="ativo" class="rounded" />
                        <label for="ativo" class="text-sm cursor-pointer">Usuário ativo</label>
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
        <div v-if="usuarioParaRemover" class="fixed inset-0 bg-black/50 flex items-center justify-center z-50 p-4">
            <div class="bg-white rounded-xl shadow-xl w-full max-w-sm p-6 space-y-4">
                <h2 class="font-bold text-lg text-red-700">Remover usuário?</h2>
                <p class="text-sm text-gray-600">
                    <strong>{{ usuarioParaRemover.name }}</strong> ({{ usuarioParaRemover.email }}) será removido.
                </p>
                <div class="flex gap-3 justify-end">
                    <button @click="usuarioParaRemover = null"
                        class="px-4 py-2 text-sm text-gray-600 hover:text-gray-900">Cancelar</button>
                    <button @click="remover"
                        class="px-4 py-2 bg-red-600 text-white rounded-lg text-sm hover:bg-red-700">Remover</button>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useForm, router, usePage } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import StatusBadge from '@/Components/StatusBadge.vue';

const props = defineProps({
    usuarios: Array,
    perfis:   Array,
    pontos:   Array,
});

const page = usePage();
const authUser = computed(() => page.props.auth?.user);

const modalAberto = ref(false);
const editando = ref(null);
const usuarioParaRemover = ref(null);

const form = useForm({
    name:                   '',
    email:                  '',
    password:               '',
    perfil:                 '',
    ponto_carregamento_id:  null,
    ativo:                  true,
});

function abrirCriar() {
    editando.value = null;
    form.reset();
    form.ativo = true;
    modalAberto.value = true;
}

function abrirEditar(u) {
    editando.value = u;
    form.name                  = u.name;
    form.email                 = u.email;
    form.password              = '';
    form.perfil                = u.perfil ?? '';
    form.ponto_carregamento_id = u.ponto_carregamento_id;
    form.ativo                 = u.ativo;
    modalAberto.value = true;
}

function fecharModal() {
    modalAberto.value = false;
    editando.value = null;
    form.reset();
}

function salvar() {
    if (editando.value) {
        form.put(route('usuarios.update', { usuario: editando.value.id }), {
            onSuccess: fecharModal,
        });
    } else {
        form.post(route('usuarios.store'), {
            onSuccess: fecharModal,
        });
    }
}

function confirmarRemover(u) {
    usuarioParaRemover.value = u;
}

function remover() {
    router.delete(route('usuarios.destroy', { usuario: usuarioParaRemover.value.id }), {
        onSuccess: () => { usuarioParaRemover.value = null; },
    });
}

function toggleAtivo(u) {
    router.post(route('usuarios.toggle-ativo', { usuario: u.id }));
}
</script>
