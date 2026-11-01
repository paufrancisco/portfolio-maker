<script setup>
defineProps({ data: Object, theme: Object });
</script>

<template>
    <div
        class="min-h-screen px-4 py-12"
        :style="{ background: theme.background, color: theme.primary, fontFamily: theme.font }"
    >
        <div class="mx-auto max-w-2xl overflow-hidden rounded-3xl bg-white shadow-lg">
            <div class="px-8 py-14 text-center text-white" :style="{ background: theme.primary }">
                <h1 class="text-4xl font-bold">{{ data.name }}</h1>
                <p v-if="data.headline" class="mt-2 text-lg" :style="{ color: theme.accent }">
                    {{ data.headline }}
                </p>
            </div>

            <div class="space-y-8 px-8 py-10">
                <section v-if="data.about">
                    <h2 class="mb-2 text-lg font-bold" :style="{ color: theme.primary }">About me</h2>
                    <p class="whitespace-pre-line leading-relaxed text-gray-700">{{ data.about }}</p>
                </section>

                <section>
                    <h2 class="mb-2 text-lg font-bold" :style="{ color: theme.primary }">Get in touch</h2>
                    <p v-if="data.email" class="text-gray-700">{{ data.email }}</p>
                    <p v-if="data.phone" class="text-gray-700">{{ data.phone }}</p>
                </section>
            </div>
        </div>
    </div>
</template>
