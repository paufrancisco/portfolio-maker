<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
    reviews: Array,
});

const approve = (id) => router.post(`/admin/reviews/${id}/approve`, {}, { preserveScroll: true });
const hide = (id) => router.post(`/admin/reviews/${id}/hide`, {}, { preserveScroll: true });
</script>

<template>
    <Head title="Reviews" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Customer reviews</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-4xl space-y-4 px-4 sm:px-6 lg:px-8">
                <p v-if="!reviews.length" class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow-sm">
                    No reviews yet. Clients can review after their request is marked delivered.
                </p>

                <div v-for="r in reviews" :key="r.id" class="rounded-lg bg-white p-5 shadow-sm">
                    <div class="flex items-start justify-between gap-4">
                        <div>
                            <p class="font-medium">
                                {{ r.name }}
                                <span class="ml-2 text-amber-500">{{ '★'.repeat(r.rating) }}{{ '☆'.repeat(5 - r.rating) }}</span>
                            </p>
                            <p class="text-xs text-gray-500">{{ r.service }} · {{ r.email }} · {{ r.created_at }}</p>
                        </div>
                        <span
                            :class="[
                                r.status === 'approved' ? 'bg-emerald-100 text-emerald-700' : r.status === 'pending' ? 'bg-amber-100 text-amber-700' : 'bg-gray-100 text-gray-600',
                                'rounded-full px-3 py-1 text-xs font-medium',
                            ]"
                        >
                            {{ r.status }}
                        </span>
                    </div>
                    <p class="mt-3 whitespace-pre-line text-sm text-gray-700">{{ r.comment }}</p>
                    <div class="mt-4 flex gap-2">
                        <button
                            v-if="r.status !== 'approved'"
                            class="rounded-lg bg-emerald-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-emerald-500"
                            @click="approve(r.id)"
                        >
                            Approve
                        </button>
                        <button
                            v-if="r.status !== 'hidden'"
                            class="rounded-lg bg-gray-200 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-300"
                            @click="hide(r.id)"
                        >
                            Hide
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
