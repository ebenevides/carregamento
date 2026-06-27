<template>
    <div :class="['rounded-xl p-4 shadow-sm border', bgClass]">
        <div class="flex items-center justify-between">
            <span class="text-2xl">{{ icone }}</span>
            <span :class="['text-3xl font-bold', textClass]">{{ valor }}</span>
        </div>
        <p :class="['mt-2 text-sm font-medium', labelClass]">{{ label }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue';

const props = defineProps({
    label: String,
    valor: Number,
    cor: String,
    icone: String,
});

const paleta = {
    yellow: { bg: 'bg-yellow-50 border-yellow-200', text: 'text-yellow-700', label: 'text-yellow-600' },
    blue:   { bg: 'bg-blue-50 border-blue-200',     text: 'text-blue-700',   label: 'text-blue-600' },
    green:  { bg: 'bg-green-50 border-green-200',   text: 'text-green-700',  label: 'text-green-600' },
    purple: { bg: 'bg-purple-50 border-purple-200', text: 'text-purple-700', label: 'text-purple-600' },
    red:    { bg: 'bg-red-50 border-red-200',       text: 'text-red-700',    label: 'text-red-600' },
    teal:   { bg: 'bg-teal-50 border-teal-200',     text: 'text-teal-700',   label: 'text-teal-600' },
    gray:   { bg: 'bg-gray-50 border-gray-200',     text: 'text-gray-700',   label: 'text-gray-600' },
    slate:  { bg: 'bg-slate-50 border-slate-200',   text: 'text-slate-700',  label: 'text-slate-600' },
};

const bgClass    = computed(() => (paleta[props.cor] ?? paleta.slate).bg);
const textClass  = computed(() => (paleta[props.cor] ?? paleta.slate).text);
const labelClass = computed(() => (paleta[props.cor] ?? paleta.slate).label);
</script>
