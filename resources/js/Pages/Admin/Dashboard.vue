<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    stats: Object,
    portfolios: Array,
    history: Array,
    q: String,
});

const search = ref(props.q ?? '');
const runSearch = () => {
    router.get(route('admin.dashboard'), { q: search.value }, { preserveState: true, replace: true });
};

const peso = (n) => 'PHP ' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });

const cards = [
    ['Unique clients', 'customers'],
    ['Portfolios', 'portfolios'],
    ['Published', 'published'],
    ['Pending payments', 'pending'],
    ['Approved payments', 'approved'],
    ['Rejected payments', 'rejected'],
];

const statusStyle = {
    draft: ['Draft', '#6b7280', '#f3f4f6'],
    awaiting_payment: ['Awaiting payment', '#92400e', '#fef3c7'],
    published: ['Published', '#166534', '#dcfce7'],
    paid: ['Approved', '#166534', '#dcfce7'],
    rejected: ['Rejected', '#991b1b', '#fee2e2'],
};
const badge = (s) => statusStyle[s] ?? [s, '#374151', '#e5e7eb'];
</script>

<template>
    <Head title="Admin dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Admin dashboard</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl space-y-8 sm:px-6 lg:px-8">
                <div class="flex flex-wrap gap-3">
                    <Link
                        :href="route('admin.payments.index')"
                        class="rounded-md px-4 py-2 text-sm font-medium"
                        style="background-color: #16a34a; color: #ffffff;"
                    >
                        Review pending payments &amp; updates
                    </Link>
                    <Link
                        :href="route('admin.requests.index')"
                        class="rounded-md px-4 py-2 text-sm font-medium"
                        style="background-color: #2563eb; color: #ffffff;"
                    >
                        Requests ({{ stats.pending_requests }} waiting)
                    </Link>
                    <Link
                        :href="route('admin.reviews.index')"
                        class="rounded-md px-4 py-2 text-sm font-medium"
                        style="background-color: #7c3aed; color: #ffffff;"
                    >
                        Reviews ({{ stats.pending_reviews }} pending)
                    </Link>
                </div>

                <div class="grid grid-cols-2 gap-4 md:grid-cols-4">
                    <div v-for="[label, key] in cards" :key="key" class="rounded-lg bg-white p-5 shadow-sm">
                        <p class="text-sm text-gray-500">{{ label }}</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ stats[key] }}</p>
                    </div>
                    <div class="rounded-lg bg-white p-5 shadow-sm md:col-span-2">
                        <p class="text-sm text-gray-500">Total revenue</p>
                        <p class="mt-1 text-2xl font-bold text-gray-900">{{ peso(stats.revenue) }}</p>
                    </div>
                </div>

                <section class="space-y-3">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <h3 class="text-lg font-semibold text-gray-800">All portfolios</h3>
                        <form class="flex gap-2" @submit.prevent="runSearch">
                            <input
                                v-model="search"
                                type="search"
                                placeholder="Search name, email or slug"
                                class="w-64 rounded-md border-gray-300 text-sm shadow-sm"
                            />
                            <button type="submit" class="rounded-md border border-gray-300 px-3 py-2 text-sm text-gray-700 hover:bg-gray-50">
                                Search
                            </button>
                        </form>
                    </div>

                    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="px-4 py-3">Name</th>
                                    <th class="px-4 py-3">Email</th>
                                    <th class="px-4 py-3">Template</th>
                                    <th class="px-4 py-3">Status</th>
                                    <th class="px-4 py-3">Published</th>
                                    <th class="px-4 py-3">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!portfolios.length">
                                    <td colspan="6" class="px-4 py-6 text-gray-500">No portfolios found.</td>
                                </tr>
                                <tr v-for="p in portfolios" :key="p.id" class="border-b last:border-0">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ p.name }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ p.email }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ p.template_key }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :style="{ color: badge(p.status)[1], backgroundColor: badge(p.status)[2] }">
                                            {{ badge(p.status)[0] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-500">{{ p.published_at ?? '-' }}</td>
                                    <td class="space-x-3 px-4 py-3">
                                        <a :href="p.preview_url" target="_blank" rel="noopener" class="text-indigo-600 hover:underline">Preview draft</a>
                                        <a
                                            v-if="p.status === 'published' && p.slug"
                                            :href="route('portfolios.public', p.slug)"
                                            target="_blank"
                                            rel="noopener"
                                            class="text-green-700 hover:underline"
                                        >
                                            View live
                                        </a>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>

                <section class="space-y-3">
                    <h3 class="text-lg font-semibold text-gray-800">Payment history</h3>
                    <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                        <table class="min-w-full text-left text-sm">
                            <thead class="border-b bg-gray-50 text-gray-600">
                                <tr>
                                    <th class="px-4 py-3">Customer</th>
                                    <th class="px-4 py-3">Reference</th>
                                    <th class="px-4 py-3">Amount</th>
                                    <th class="px-4 py-3">Result</th>
                                    <th class="px-4 py-3">Admin note</th>
                                    <th class="px-4 py-3">Reviewed</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr v-if="!history.length">
                                    <td colspan="6" class="px-4 py-6 text-gray-500">No reviewed payments yet.</td>
                                </tr>
                                <tr v-for="h in history" :key="h.id" class="border-b last:border-0">
                                    <td class="px-4 py-3 font-medium text-gray-900">{{ h.client }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ h.reference_no }}</td>
                                    <td class="px-4 py-3 text-gray-700">{{ peso(h.amount ?? 0) }}</td>
                                    <td class="px-4 py-3">
                                        <span class="rounded-full px-2.5 py-0.5 text-xs font-medium" :style="{ color: badge(h.status)[1], backgroundColor: badge(h.status)[2] }">
                                            {{ badge(h.status)[0] }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-gray-600">{{ h.admin_note ?? '-' }}</td>
                                    <td class="px-4 py-3 text-gray-500">{{ h.reviewed_at ?? '-' }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </section>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
