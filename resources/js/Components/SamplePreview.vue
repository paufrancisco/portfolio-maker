<template>
    <a
        ref="box"
        :href="src"
        target="_blank"
        rel="noopener"
        :title="`Open sample: ${label}`"
        :class="['relative block overflow-hidden bg-gray-100', heightClass]"
    >
        <iframe
            :src="src"
            :title="`${label} sample`"
            loading="lazy"
            tabindex="-1"
            aria-hidden="true"
            class="pointer-events-none absolute left-0 top-0 origin-top-left border-0 bg-white"
            :style="{ width: designWidth + 'px', height: '2400px', transform: `scale(${scale})` }"
        ></iframe>
    </a>
</template>

<script setup>
import { onBeforeUnmount, onMounted, ref } from 'vue';

const props = defineProps({
    src: { type: String, required: true },
    label: { type: String, default: 'Template' },
    designWidth: { type: Number, default: 1200 }, // width the page is laid out at
    heightClass: { type: String, default: 'h-48' },
});

const box = ref(null);
const scale = ref(0.3);
let ro;

// Scale the page so its design width fits the card width
onMounted(() => {
    const fit = () => {
        if (box.value) scale.value = box.value.clientWidth / props.designWidth;
    };
    fit();
    ro = new ResizeObserver(fit);
    ro.observe(box.value);
});
onBeforeUnmount(() => ro && ro.disconnect());
</script>
