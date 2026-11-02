<script setup>
import { Head, Link, router } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import Professional from '@/Templates/Professional.vue';
import PinkElegance from '@/Templates/PinkElegance.vue';
import BlueDeveloper from '@/Templates/BlueDeveloper.vue';
import ShowcaseBold from '@/Templates/ShowcaseBold.vue';
import MinimalClean from '@/Templates/MinimalClean.vue';
import EditorialSerif from '@/Templates/EditorialSerif.vue';

const props = defineProps({
    portfolio: Object,
    theme: Object,
    watermark: Boolean,
    templates: { type: Array, default: null },
});

const components = {
    professional: Professional,
    pink: PinkElegance,
    blue: BlueDeveloper,
    showcase: ShowcaseBold,
    minimal: MinimalClean,
    editorial: EditorialSerif,
};

const current = computed(() => components[props.portfolio.template_key] ?? Professional);

// Owner toolbar mode: only when the server sent a templates list.
const toolbar = computed(() => Array.isArray(props.templates));

const device = ref('desktop');
const widths = { desktop: '100%', tablet: '768px', mobile: '390px' };

const selected = ref(props.portfolio.template_key);
const frameSrc = computed(() =>
    route('portfolios.preview', { portfolio: props.portfolio.id, embed: 1, template: selected.value }),
);

const canSave = computed(
    () => props.portfolio.status !== 'published' && selected.value !== props.portfolio.saved_template_key,
);

const saveTemplate = () => {
    router.post(route('portfolios.template', props.portfolio.id), { template_key: selected.value }, { preserveScroll: true });
};

const btn = 'rounded-md border px-3 py-1.5 text-sm font-medium';
</script>

<template>
    <Head title="Preview" />

    <div v-if="toolbar" class="flex h-screen flex-col bg-gray-100">
        <div class="flex flex-wrap items-center justify-between gap-3 border-b bg-white px-4 py-3">
            <div class="flex flex-wrap items-center gap-2">
                <Link :href="route('dashboard')" :class="btn" class="border-gray-300 text-gray-700 hover:bg-gray-50">Dashboard</Link>
                <Link :href="route('portfolios.edit', portfolio.id)" :class="btn" class="border-gray-300 text-gray-700 hover:bg-gray-50">Edit</Link>
            </div>

            <div class="flex items-center gap-1">
                <button
                    v-for="d in ['desktop', 'tablet', 'mobile']"
                    :key="d"
                    type="button"
                    :class="btn"
                    :style="device === d ? 'background-color:#4f46e5;color:#ffffff;border-color:#4f46e5' : 'color:#374151;border-color:#d1d5db'"
                    @click="device = d"
                >
                    {{ d.charAt(0).toUpperCase() + d.slice(1) }}
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-2">
                <button
                    v-for="t in templates"
                    :key="t.key"
                    type="button"
                    :class="btn"
                    :style="selected === t.key ? 'background-color:#111827;color:#ffffff;border-color:#111827' : 'color:#374151;border-color:#d1d5db'"
                    @click="selected = t.key"
                >
                    {{ t.name }}
                </button>
                <button
                    v-if="canSave"
                    type="button"
                    class="rounded-md px-3 py-1.5 text-sm font-medium"
                    style="background-color: #16a34a; color: #ffffff;"
                    @click="saveTemplate"
                >
                    Use this template
                </button>
                <span v-else-if="portfolio.status === 'published'" class="text-xs text-gray-500">
                    Template is locked while published
                </span>
            </div>
        </div>

        <div class="flex flex-1 justify-center overflow-auto p-4">
            <iframe
                :src="frameSrc"
                title="Portfolio preview"
                class="h-full rounded-lg border bg-white shadow"
                :style="{ width: widths[device], maxWidth: '100%' }"
            ></iframe>
        </div>
    </div>

    <template v-else>
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
</template>
