<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    changeRequests: Array,
    portfolios: Array,
});

const labels = {
    pending: ['#92400e', '#fef3c7'],
    quoted: ['#1e40af', '#dbeafe'],
    in_progress: ['#6b21a8', '#f3e8ff'],
    fixed: ['#166534', '#dcfce7'],
    not_fixed: ['#991b1b', '#fee2e2'],
};
const badge = (status) => labels[status] ?? ['#374151', '#e5e7eb'];

const fileInput = ref(null);

const form = useForm({
    portfolio_id: props.portfolios.length === 1 ? props.portfolios[0].id : '',
    title: '',
    body: '',
    images: [],
});

const imageError = computed(() => {
    const key = Object.keys(form.errors).find((k) => k.startsWith('images'));
    return key ? form.errors[key] : null;
});

const pickImages = (event) => {
    form.images = Array.from(event.target.files).slice(0, 4);
};

const submit = () => {
    form.post(route('change-requests.store'), {
        forceFormData: true,
        onSuccess: () => {
            form.reset();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const formatDate = (iso) => (iso ? new Date(iso).toLocaleString() : '');
</script>

<template>
    <Head title="Change requests" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Change requests</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <div class="rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="text-base font-semibold text-gray-900">New change request</h3>
                    <p class="mt-1 text-sm text-gray-600">
                        Tell us what you want adjusted. We will review it and reply here with the next steps.
                    </p>

                    <p v-if="!portfolios.length" class="mt-4 text-sm text-gray-600">
                        You need a portfolio first before you can send a change request.
                    </p>

                    <form v-else class="mt-4 space-y-4" @submit.prevent="submit">
                        <div>
                            <label class="block text-sm font-medium text-gray-700">Portfolio</label>
                            <select v-model="form.portfolio_id" required class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                                <option value="" disabled>Select a portfolio</option>
                                <option v-for="p in portfolios" :key="p.id" :value="p.id">{{ p.name }}</option>
                            </select>
                            <p v-if="form.errors.portfolio_id" class="mt-1 text-xs text-red-600">{{ form.errors.portfolio_id }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Title</label>
                            <input v-model="form.title" type="text" required maxlength="120" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                            <p v-if="form.errors.title" class="mt-1 text-xs text-red-600">{{ form.errors.title }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">What should be changed?</label>
                            <textarea v-model="form.body" rows="4" required minlength="10" maxlength="2000" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></textarea>
                            <p v-if="form.errors.body" class="mt-1 text-xs text-red-600">{{ form.errors.body }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Images (optional, up to 4)</label>
                            <input
                                ref="fileInput"
                                type="file"
                                multiple
                                accept="image/png,image/jpeg,image/webp"
                                class="mt-1 block w-full text-sm"
                                @change="pickImages"
                            />
                            <p v-if="imageError" class="mt-1 text-xs text-red-600">{{ imageError }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="form.processing"
                            class="rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                            style="background-color: #4f46e5; color: #ffffff;"
                        >
                            {{ form.processing ? 'Sending...' : 'Send request' }}
                        </button>
                    </form>
                </div>

                <p v-if="!changeRequests.length" class="rounded-lg bg-white p-6 text-gray-600 shadow-sm">
                    You have no change requests yet.
                </p>

                <Link
                    v-for="cr in changeRequests"
                    :key="cr.id"
                    :href="route('change-requests.show', cr.id)"
                    class="flex flex-wrap items-center justify-between gap-4 rounded-lg bg-white p-6 shadow-sm hover:bg-gray-50"
                >
                    <div class="space-y-1">
                        <div class="flex items-center gap-2">
                            <span v-if="cr.unread" class="inline-block h-2.5 w-2.5 rounded-full" style="background-color: #dc2626;"></span>
                            <p class="text-base font-semibold text-gray-900">{{ cr.title }}</p>
                        </div>
                        <p class="text-sm text-gray-600">{{ cr.portfolio }}</p>
                        <p class="text-xs text-gray-500">Last activity: {{ formatDate(cr.last_message_at) }}</p>
                    </div>

                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span
                            class="rounded-full px-2.5 py-0.5 font-medium"
                            :style="{ color: badge(cr.status)[0], backgroundColor: badge(cr.status)[1] }"
                        >
                            {{ cr.status_label }}
                        </span>
                        <span v-if="cr.type_label" class="rounded-full bg-gray-100 px-2.5 py-0.5 font-medium text-gray-700">
                            {{ cr.type_label }}
                        </span>
                        <span v-if="cr.quote_amount" class="rounded-full px-2.5 py-0.5 font-medium" style="color: #1e40af; background-color: #dbeafe;">
                            Quote: PHP {{ cr.quote_amount }}
                        </span>
                    </div>
                </Link>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
