<script setup>
defineProps({ data: Object, theme: Object });
</script>

<template>
    <div
        class="min-h-screen"
        :style="{ background: theme.background, color: theme.primary, fontFamily: theme.font }"
    >
        <header class="mx-auto max-w-3xl border-b px-6 py-16" :style="{ borderColor: theme.accent }">
            <h1 class="text-4xl font-bold">{{ data.name }}</h1>
            <p v-if="data.headline" class="mt-2 text-lg" :style="{ color: theme.accent }">
                {{ data.headline }}
            </p>
        </header>

        <main class="mx-auto max-w-3xl space-y-10 px-6 py-10">
            <section v-if="data.about">
                <h2 class="mb-2 text-sm font-semibold uppercase tracking-widest" :style="{ color: theme.accent }">About</h2>
                <p class="whitespace-pre-line leading-relaxed">{{ data.about }}</p>
            </section>

            <section>
                <h2 class="mb-2 text-sm font-semibold uppercase tracking-widest" :style="{ color: theme.accent }">Contact</h2>
                <p v-if="data.email">{{ data.email }}</p>
                <p v-if="data.phone">{{ data.phone }}</p>
            </section>
        </main>
    </div>
</template>
