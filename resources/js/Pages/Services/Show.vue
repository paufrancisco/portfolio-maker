<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    serviceRequest: Object,
    token: String,
});

const reviewForm = useForm({ name: '', rating: 5, comment: '' });

const submitReview = () => reviewForm.post(`${window.location.pathname}/review`);
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

            <!-- Review: only after delivery -->
            <div v-if="serviceRequest.review" class="mt-6 rounded-xl border bg-white p-6 shadow-sm">
                <h2 class="text-sm font-medium">Your review</h2>
                <p class="mt-2 text-amber-500">
                    {{ '★'.repeat(serviceRequest.review.rating) }}{{ '☆'.repeat(5 - serviceRequest.review.rating) }}
                </p>
                <p class="mt-1 whitespace-pre-line text-sm text-gray-700">{{ serviceRequest.review.comment }}</p>
                <p v-if="serviceRequest.review.status === 'pending'" class="mt-3 text-xs text-gray-500">
                    Thank you! Your review will appear on the home page once it is approved.
                </p>
            </div>

            <form
                v-else-if="serviceRequest.can_review"
                class="mt-6 space-y-4 rounded-xl border bg-white p-6 shadow-sm"
                @submit.prevent="submitReview"
            >
                <h2 class="text-sm font-medium">How was your experience?</h2>

                <div class="flex gap-1">
                    <button
                        v-for="n in 5"
                        :key="n"
                        type="button"
                        :class="[n <= reviewForm.rating ? 'text-amber-500' : 'text-gray-300', 'text-3xl leading-none']"
                        @click="reviewForm.rating = n"
                    >
                        ★
                    </button>
                </div>
                <p v-if="reviewForm.errors.rating" class="text-xs text-red-600">{{ reviewForm.errors.rating }}</p>

                <div>
                    <label class="block text-sm font-medium">Name to show</label>
                    <input v-model="reviewForm.name" type="text" required maxlength="60" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    <p class="mt-1 text-xs text-gray-500">Your email is never shown.</p>
                    <p v-if="reviewForm.errors.name" class="mt-1 text-xs text-red-600">{{ reviewForm.errors.name }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium">Your review</label>
                    <textarea v-model="reviewForm.comment" rows="4" required class="mt-1 w-full rounded-lg border-gray-300 text-sm"></textarea>
                    <p v-if="reviewForm.errors.comment" class="mt-1 text-xs text-red-600">{{ reviewForm.errors.comment }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="reviewForm.processing"
                    class="rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                >
                    Submit review
                </button>
            </form>

            <p class="mt-6 text-xs text-gray-500">Keep this page's link private. Anyone with it can view your request.</p>
        </main>
    </div>
</template>
