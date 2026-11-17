<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    changeRequests: Array,
});

const labels = {
    pending: ['#92400e', '#fef3c7'],
    quoted: ['#1e40af', '#dbeafe'],
    in_progress: ['#6b21a8', '#f3e8ff'],
    fixed: ['#166534', '#dcfce7'],
    not_fixed: ['#991b1b', '#fee2e2'],
};
const badge = (status) => labels[status] ?? ['#374151', '#e5e7eb'];

const peso = (n) => 'PHP ' + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2 });
</script>

<template>
    <Head title="Change requests" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">Change requests</h2>
                <Link :href="route('admin.dashboard')" class="text-sm text-gray-600 hover:text-gray-900">
                    Back to admin dashboard
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-6xl sm:px-6 lg:px-8">
                <div class="overflow-x-auto rounded-lg bg-white shadow-sm">
                    <table class="min-w-full text-left text-sm">
                        <thead class="border-b bg-gray-50 text-gray-600">
                            <tr>
                                <th class="px-4 py-3">Title</th>
                                <th class="px-4 py-3">Client</th>
                                <th class="px-4 py-3">Portfolio</th>
                                <th class="px-4 py-3">Status</th>
                                <th class="px-4 py-3">Type</th>
                                <th class="px-4 py-3">Quote</th>
                                <th class="px-4 py-3">Last activity</th>
                                <th class="px-4 py-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="!changeRequests.length">
                                <td colspan="8" class="px-4 py-6 text-gray-500">No change requests yet.</td>
                            </tr>
                            <tr v-for="cr in changeRequests" :key="cr.id" class="border-b last:border-0">
                                <td class="px-4 py-3 font-medium text-gray-900">
                                    <span
                                        v-if="cr.unread"
                                        class="me-2 inline-block h-2.5 w-2.5 rounded-full"
                                        style="background-color: #dc2626;"
                                    ></span>
                                    {{ cr.title }}
                                </td>
                                <td class="px-4 py-3 text-gray-700">
                                    <p>{{ cr.client }}</p>
                                    <p class="text-xs text-gray-500">{{ cr.email }}</p>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ cr.portfolio }}</td>
                                <td class="px-4 py-3">
                                    <span
                                        class="rounded-full px-2.5 py-0.5 text-xs font-medium"
                                        :style="{ color: badge(cr.status)[0], backgroundColor: badge(cr.status)[1] }"
                                    >
                                        {{ cr.status_label }}
                                    </span>
                                </td>
                                <td class="px-4 py-3 text-gray-700">{{ cr.type_label ?? '-' }}</td>
                                <td class="px-4 py-3 text-gray-700">{{ cr.quote_amount ? peso(cr.quote_amount) : '-' }}</td>
                                <td class="px-4 py-3 text-gray-500">{{ cr.last_message_at ?? '-' }}</td>
                                <td class="px-4 py-3">
                                    <Link :href="route('admin.change-requests.show', cr.id)" class="text-indigo-600 hover:underline">
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
