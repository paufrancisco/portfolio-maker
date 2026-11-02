<script setup>
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    serviceRequest: Object,
});
</script>

<template>
    <Head title="Your request" />

    <div class="min-h-screen bg-gray-50 text-gray-800">
        <header class="mx-auto flex max-w-3xl items-center justify-between px-6 py-5">
            <Link href="/" class="text-lg font-bold">Portfolio Maker</Link>
        </header>

        <main class="mx-auto max-w-3xl px-6 pb-16">
            <h1 class="text-2xl font-bold">{{ serviceRequest.type_label }}</h1>
            <p class="mt-1 text-sm text-gray-500">Sent {{ serviceRequest.created_at }}</p>

            <div class="mt-6 rounded-xl border bg-white p-6 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium">Status</span>
                    <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium">
                        {{ serviceRequest.status_label }}
                    </span>
                </div>

                <p v-if="serviceRequest.status === 'received'" class="mt-4 text-sm text-gray-600">
                    We are reviewing your request. You will get an email with a price soon.
                </p>

                <div v-if="serviceRequest.quote_amount" class="mt-4 rounded-lg bg-blue-50 p-4 text-sm">
                    <p class="font-medium">Quote: {{ serviceRequest.quote_amount }}</p>
                    <p v-if="serviceRequest.quote_note" class="mt-1 text-gray-600">{{ serviceRequest.quote_note }}</p>
                </div>

                <a
                    v-if="serviceRequest.delivery_url"
                    :href="serviceRequest.delivery_url"
                    target="_blank"
                    rel="noopener"
                    class="mt-4 inline-block rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500"
                >
                    Open your video
                </a>
            </div>

            <div class="mt-6 rounded-xl border bg-white p-6 shadow-sm">
                <h2 class="text-sm font-medium">Your request</h2>
                <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ serviceRequest.details }}</p>
            </div>

            <p class="mt-6 text-xs text-gray-500">Keep this page's link private. Anyone with it can view your request.</p>
        </main>
    </div>
</template>
