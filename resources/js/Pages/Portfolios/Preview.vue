<script setup>
import { Head } from '@inertiajs/vue3';
import { computed } from 'vue';
import Professional from '@/Templates/Professional.vue';
import PinkElegance from '@/Templates/PinkElegance.vue';
import BlueDeveloper from '@/Templates/BlueDeveloper.vue';

const props = defineProps({
    portfolio: Object,
    theme: Object,
    watermark: Boolean,
});

const components = {
    professional: Professional,
    pink: PinkElegance,
    blue: BlueDeveloper,
};

const current = computed(() => components[props.portfolio.template_key] ?? Professional);
</script>

<template>
    <Head title="Preview" />

    <component :is="current" :data="portfolio.data" :theme="theme" />

    <div
        v-if="watermark"
        class="pointer-events-none fixed inset-0 z-50 flex items-center justify-center overflow-hidden"
    >
        <span
            class="select-none text-8xl font-extrabold tracking-widest text-black/10"
            style="transform: rotate(-30deg)"
        >
            PREVIEW
        </span>
    </div>
</template>
