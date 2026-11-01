<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, router } from '@inertiajs/vue3';

defineProps({
    payments: Array,
});

const approve = (payment) => {
    if (confirm('Approve payment from ' + payment.client_name + ' and send the portfolio URL?')) {
        router.post(route('admin.payments.approve', payment.id));
    }
};
</script>

<template>
    <Head title="Pending Payments" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Pending payments
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-4 sm:px-6 lg:px-8">
                <p v-if="!payments.length" class="rounded-lg bg-white p-6 text-gray-600 shadow-sm">
                    No pending payments.
                </p>

                <div
                    v-for="p in payments"
                    :key="p.id"
                    class="flex flex-wrap items-center justify-between gap-4 rounded-lg bg-white p-6 shadow-sm"
                >
                    <div class="space-y-1 text-sm text-gray-700">
                        <p class="text-base font-semibold text-gray-900">{{ p.client_name }}</p>
                        <p>{{ p.client_email }}</p>
                        <p>Template: {{ p.template_key }}</p>
                        <p>Ref: {{ p.reference_no }} | PHP {{ p.amount }}</p>
                        <p class="text-gray-500">{{ p.created_at }}</p>
                    </div>

                    <a
                        v-if="p.proof_url"
                        :href="p.proof_url"
                        target="_blank"
                        rel="noopener"
                        class="rounded-md border border-gray-300 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-50"
                    >
                        View proof
                    </a>
                    <span v-else class="text-sm text-red-600">Proof unavailable</span>

                    <button
                        type="button"
                        class="rounded-md px-4 py-2 text-sm font-medium" style="background-color: #16a34a; color: #ffffff;"
                        @click="approve(p)"
                    >
                        Approve &amp; Send
                    </button>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
