<template>
    <span :class="['text-xs px-2 py-0.5 rounded-full font-medium whitespace-nowrap', cor]">
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({ status: String, label: String });

const cor = computed(() => {
    const map = {
        ABERTA:                   'bg-red-100 text-red-700',
        RESOLVIDA:                'bg-green-100 text-green-700',
        CRIADO:                   'bg-gray-100 text-gray-600',
        TARA_REALIZADA:           'bg-gray-100 text-gray-600',
        AGUARDANDO_CARREGAMENTO:  'bg-yellow-100 text-yellow-700',
        EM_CARREGAMENTO:          'bg-blue-100 text-blue-700',
        CARREGAMENTO_CONCLUIDO:   'bg-cyan-100 text-cyan-700',
        AGUARDANDO_PESAGEM_FINAL: 'bg-purple-100 text-purple-700',
        PESAGEM_FINAL_REALIZADA:  'bg-purple-100 text-purple-700',
        VALIDADO:                 'bg-teal-100 text-teal-700',
        DIVERGENCIA:              'bg-red-100 text-red-700',
        CANCELADO:                'bg-gray-100 text-gray-500',
        FINALIZADO:               'bg-green-100 text-green-700',
        ATIVO:                    'bg-green-100 text-green-700',
        INATIVO:                  'bg-gray-100 text-gray-500',
        MANUTENCAO:               'bg-orange-100 text-orange-700',
    };
    return map[props.status] ?? 'bg-gray-100 text-gray-600';
});
</script>
