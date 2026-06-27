<template>
    <div v-if="links && links.length > 3" class="flex flex-wrap gap-1 justify-center mt-4">
        <component
            v-for="link in links"
            :key="link.label"
            :is="link.url ? 'a' : 'span'"
            :href="link.url ?? undefined"
            v-html="link.label"
            :class="['px-3 py-1 text-sm rounded border transition-colors',
                link.active
                    ? 'bg-blue-600 text-white border-blue-600'
                    : link.url
                        ? 'bg-white text-gray-600 border-gray-300 hover:bg-gray-50'
                        : 'bg-white text-gray-400 border-gray-200 cursor-default']"
        />
    </div>
</template>

<script setup>
defineProps({ links: Array });
</script>
