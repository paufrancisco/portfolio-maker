<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    requests: Array,
});

const badge = (status) =>
    ({
        received: 'bg-amber-100 text-amber-700',
        payment_submitted: 'bg-amber-100 text-amber-700',
        quoted: 'bg-blue-100 text-blue-700',
        paid: 'bg-violet-100 text-violet-700',
        delivered: 'bg-emerald-100 text-emerald-700',
        rejected: 'bg-gray-100 text-gray-600',
    })[status] || 'bg-gray-100 text-gray-600';
</script>

<template>
    <Head title="Requests" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Service requests</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-5xl px-4 sm:px-6 lg:px-8">
                <p v-if="!requests.length" class="rounded-lg bg-white p-6 text-sm text-gray-500 shadow-sm">
                    No requests yet.
                </p>

                <div v-else class="overflow-hidden rounded-lg bg-white shadow-sm">
                    <table class="min-w-full divide-y divide-gray-200 text-sm">
                        <thead class="bg-gray-50 text-left text-xs uppercase text-gray-500">
                            <tr>
                                <th class="px-4 py-3">Service</th>
                                <th class="px-4 py-3">Email</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Quote</th>
                                <th class="px-4 py-3">Sent</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100">
                            <tr v-for="r in requests" :key="r.id">
                                <td class="px-4 py-3">{{ r.type_label }}</td>
                                <td class="px-4 py-3">{{ r.email }}</td>
                                <td class="px-4 py-3">
                                    <span :class="[badge(r.status), 'rounded-full px-3 py-1 text-xs font-medium']">
                                        {{ r.status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3">{{ r.quote_amount || '-' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ r.created_at }}</td>
                                <td class="px-4 py-3 text-right">
                                    <Link :href="`/admin/requests/${r.id}`" class="font-medium text-blue-600 hover:text-blue-500">
                                        Open
                                    </Link>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
