<template>
    <Head title="Request" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ serviceRequest.type_label }}</h2>
        </template>

        <div class="py-8">
            <div class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8">
                <Link href="/admin/requests" class="text-sm text-blue-600 hover:text-blue-500">&larr; All requests</Link>

                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="font-medium">{{ serviceRequest.email }}</p>
                            <p class="text-xs text-gray-500">{{ serviceRequest.created_at }}</p>
                        </div>
                        <span class="rounded-full bg-gray-100 px-3 py-1 text-xs font-medium">{{ serviceRequest.status_label }}</span>
                    </div>

                    <p class="mt-4 whitespace-pre-line text-sm text-gray-700">{{ serviceRequest.details }}</p>

                    <a
                        v-if="serviceRequest.attachment_url"
                        :href="serviceRequest.attachment_url"
                        target="_blank"
                        rel="noopener"
                        class="mt-4 inline-block text-sm font-medium text-blue-600 hover:text-blue-500"
                    >
                        Open attachment (link expires in 15 minutes)
                    </a>

                    <p class="mt-4 text-xs text-gray-500">
                        Client status page:
                        <a :href="serviceRequest.status_url" target="_blank" rel="noopener" class="text-blue-600">{{ serviceRequest.status_url }}</a>
                    </p>
                </div>

                <!-- Quote -->
                <form
                    v-if="['received', 'quoted'].includes(serviceRequest.status)"
                    class="space-y-3 rounded-lg bg-white p-6 shadow-sm"
                    @submit.prevent="sendQuote"
                >
                    <h3 class="font-medium">{{ serviceRequest.status === 'quoted' ? 'Update quote' : 'Send a quote' }}</h3>
                    <input v-model="quoteForm.quote_amount" type="number" step="0.01" min="1" required placeholder="Amount" class="w-full rounded-lg border-gray-300 text-sm" />
                    <p v-if="quoteForm.errors.quote_amount" class="text-xs text-red-600">{{ quoteForm.errors.quote_amount }}</p>
                    <textarea v-model="quoteForm.quote_note" rows="3" placeholder="Note for the client (optional)" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    <button type="submit" :disabled="quoteForm.processing" class="rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50">
                        Send quote by email
                    </button>
                </form>

                <!-- Payment proof review -->
                <div v-if="serviceRequest.status === 'payment_submitted'" class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-medium">Review payment proof</h3>
                    <div class="text-sm text-gray-700">
                        <p>Amount quoted: <span class="font-medium">PHP {{ Number(serviceRequest.quote_amount).toFixed(2) }}</span></p>
                        <p>Reference: <span class="font-medium">{{ serviceRequest.payment_reference }}</span></p>
                        <p class="text-xs text-gray-500">Submitted {{ serviceRequest.payment_submitted_at }}</p>
                    </div>

                    <a
                        v-if="serviceRequest.proof_url"
                        :href="serviceRequest.proof_url"
                        target="_blank"
                        rel="noopener"
                        class="inline-block text-sm font-medium text-blue-600 hover:text-blue-500"
                    >
                        Open proof (link expires in 10 minutes)
                    </a>
                    <p v-else class="text-sm text-red-600">The proof link could not be created. Reload the page to try again.</p>

                    <p class="text-xs text-gray-500">Only approve after you have confirmed the money arrived.</p>
                    <button class="rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500" @click="approvePayment">
                        Approve payment
                    </button>

                    <form class="space-y-3 border-t pt-4" @submit.prevent="rejectPayment">
                        <textarea v-model="paymentRejectForm.admin_note" rows="2" required placeholder="Reason for rejecting the proof (sent to the client)" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                        <p v-if="paymentRejectForm.errors.admin_note" class="text-xs text-red-600">{{ paymentRejectForm.errors.admin_note }}</p>
                        <button type="submit" :disabled="paymentRejectForm.processing" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 disabled:opacity-50">
                            Reject proof and email the client
                        </button>
                    </form>
                </div>

                <!-- Deliver -->
                <form v-if="serviceRequest.status === 'paid'" class="space-y-3 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="deliver">
                    <h3 class="font-medium">Deliver the result</h3>
                    <input v-model="deliverForm.delivery_url" type="url" required placeholder="https://... (video or file link)" class="w-full rounded-lg border-gray-300 text-sm" />
                    <p v-if="deliverForm.errors.delivery_url" class="text-xs text-red-600">{{ deliverForm.errors.delivery_url }}</p>
                    <button type="submit" :disabled="deliverForm.processing" class="rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-50">
                        Mark delivered and email the client
                    </button>
                </form>

                <div v-if="serviceRequest.delivery_url" class="rounded-lg bg-white p-6 text-sm shadow-sm">
                    Delivered link:
                    <a :href="serviceRequest.delivery_url" target="_blank" rel="noopener" class="text-blue-600">{{ serviceRequest.delivery_url }}</a>
                </div>

                <!-- Reject request -->
                <form
                    v-if="['received', 'quoted'].includes(serviceRequest.status)"
                    class="space-y-3 rounded-lg bg-white p-6 shadow-sm"
                    @submit.prevent="reject"
                >
                    <h3 class="font-medium">Reject this request</h3>
                    <textarea v-model="rejectForm.admin_note" rows="2" required placeholder="Reason (sent to the client)" class="w-full rounded-lg border-gray-300 text-sm"></textarea>
                    <p v-if="rejectForm.errors.admin_note" class="text-xs text-red-600">{{ rejectForm.errors.admin_note }}</p>
                    <button type="submit" :disabled="rejectForm.processing" class="rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-700 hover:bg-gray-300 disabled:opacity-50">
                        Reject and email the client
                    </button>
                </form>

                <p v-if="serviceRequest.status === 'rejected'" class="rounded-lg bg-white p-6 text-sm text-gray-600 shadow-sm">
                    Rejected. Reason sent: {{ serviceRequest.admin_note }}
                </p>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, useForm } from '@inertiajs/vue3';

const props = defineProps({
    serviceRequest: Object,
});

const base = `/admin/requests/${props.serviceRequest.id}`;

const quoteForm = useForm({
    quote_amount: props.serviceRequest.quote_amount || '',
    quote_note: props.serviceRequest.quote_note || '',
});
const deliverForm = useForm({ delivery_url: '' });
const rejectForm = useForm({ admin_note: '' });
const paymentRejectForm = useForm({ admin_note: '' });

const sendQuote = () => quoteForm.post(`${base}/quote`, { preserveScroll: true });
const deliver = () => deliverForm.post(`${base}/deliver`, { preserveScroll: true });
const reject = () => rejectForm.post(`${base}/reject`, { preserveScroll: true });

// Reminder: approve/reject payment only apply while status is payment_submitted
const approvePayment = () => router.post(`${base}/payment/approve`, {}, { preserveScroll: true });
const rejectPayment = () => paymentRejectForm.post(`${base}/payment/reject`, { preserveScroll: true });
</script>
