<template>
    <div class="min-h-screen bg-gray-100">
        <nav class="bg-white border-b border-gray-200 shadow-sm">
            <div class="px-4 sm:px-6 lg:px-8">
                <div class="flex items-center justify-between h-14">
                    <div class="flex items-center gap-1">
                        <Link :href="route('dashboard')" class="font-bold text-blue-700 text-lg mr-4">
                            🚛 Carregamento
                        </Link>

                        <Link :href="route('dashboard')"
                            :class="navClass('dashboard')">Dashboard</Link>

                        <Link v-if="pode(['ADMIN','EXPEDICAO','OPERADOR'])"
                            :href="route('ordens')" :class="navClass('ordens')">Ordens</Link>

                        <Link v-if="pode(['ADMIN','EXPEDICAO','OPERADOR'])"
                            :href="route('fila')" :class="navClass('fila')">Fila</Link>

                        <Link v-if="pode(['ADMIN','EXPEDICAO'])"
                            :href="route('divergencias')" :class="navClass('divergencias')">Divergências</Link>

                        <Link v-if="pode(['ADMIN'])"
                            :href="route('pontos')" :class="navClass('pontos')">Pontos</Link>

                        <Link v-if="pode(['ADMIN'])"
                            :href="route('pilhas')" :class="navClass('pilhas')">Pilhas</Link>

                        <Link v-if="pode(['ADMIN'])"
                            :href="route('usuarios')" :class="navClass('usuarios')">Usuários</Link>
                    </div>

                    <div class="flex items-center gap-3 text-sm text-gray-600">
                        <span class="hidden sm:block">{{ auth?.name }}</span>
                        <span class="text-xs bg-blue-100 text-blue-700 px-2 py-0.5 rounded-full font-medium">
                            {{ auth?.perfil }}
                        </span>
                        <Link :href="route('profile.edit')" class="hover:text-gray-900">Perfil</Link>
                        <Link :href="route('logout')" method="post" as="button"
                            class="hover:text-red-600">Sair</Link>
                    </div>
                </div>
            </div>
        </nav>

        <main class="px-4 sm:px-6 lg:px-8 py-6">
            <slot />
        </main>
    </div>
</template>

<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const page = usePage();
const auth = computed(() => page.props.auth?.user);
const currentComponent = computed(() => page.component ?? '');

function pode(perfis) {
    return perfis.includes(auth.value?.perfil);
}

function navClass(name) {
    const active = currentComponent.value.toLowerCase().includes(name.toLowerCase());
    return [
        'px-3 py-1.5 rounded-md text-sm font-medium transition-colors',
        active
            ? 'bg-blue-50 text-blue-700'
            : 'text-gray-600 hover:text-gray-900 hover:bg-gray-100',
    ];
}
</script>
