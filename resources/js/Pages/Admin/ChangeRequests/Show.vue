<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    changeRequest: Object,
    statuses: Object,
    types: Object,
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

const replyForm = useForm({
    body: '',
    images: [],
});

const updateForm = useForm({
    type: props.changeRequest.type ?? 'minor_fix',
    status: props.changeRequest.status,
    quote_amount: props.changeRequest.quote_amount ?? '',
    quote_note: props.changeRequest.quote_note ?? '',
});

const imageError = computed(() => {
    const key = Object.keys(replyForm.errors).find((k) => k.startsWith('images'));
    return key ? replyForm.errors[key] : null;
});

const pickImages = (event) => {
    replyForm.images = Array.from(event.target.files).slice(0, 4);
};

const sendReply = () => {
    replyForm.post(route('admin.change-requests.reply', props.changeRequest.id), {
        forceFormData: true,
        preserveScroll: true,
        onSuccess: () => {
            replyForm.reset();
            if (fileInput.value) {
                fileInput.value.value = '';
            }
        },
    });
};

const saveChanges = () => {
    updateForm.post(route('admin.change-requests.update', props.changeRequest.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head :title="changeRequest.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ changeRequest.title }}</h2>
                <Link :href="route('admin.change-requests.index')" class="text-sm text-gray-600 hover:text-gray-900">
                    Back to all requests
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                <div class="space-y-3 rounded-lg bg-white p-6 shadow-sm">
                    <div class="flex flex-wrap items-center justify-between gap-3">
                        <div>
                            <p class="text-base font-semibold text-gray-900">{{ changeRequest.client }}</p>
                            <p class="text-sm text-gray-600">{{ changeRequest.email }}</p>
                            <p class="text-sm text-gray-600">Portfolio: {{ changeRequest.portfolio }}</p>
                        </div>
                        <a
                            :href="changeRequest.preview_url"
                            target="_blank"
                            rel="noopener"
                            class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50"
                        >
                            Preview draft
                        </a>
                    </div>
                    <div class="flex flex-wrap items-center gap-2 text-xs">
                        <span
                            class="rounded-full px-2.5 py-0.5 font-medium"
                            :style="{ color: badge(changeRequest.status)[0], backgroundColor: badge(changeRequest.status)[1] }"
                        >
                            {{ changeRequest.status_label }}
                        </span>
                        <span v-if="changeRequest.type_label" class="rounded-full bg-gray-100 px-2.5 py-0.5 font-medium text-gray-700">
                            {{ changeRequest.type_label }}
                        </span>
                    </div>
                </div>

                <div class="grid gap-6 lg:grid-cols-3">
                    <div class="space-y-6 lg:col-span-2">
                        <div class="space-y-3">
                            <div
                                v-for="m in changeRequest.messages"
                                :key="m.id"
                                class="rounded-lg p-4 shadow-sm"
                                :style="m.is_admin ? 'background-color: #eef2ff;' : 'background-color: #ffffff;'"
                            >
                                <div class="flex items-center justify-between gap-3 text-xs text-gray-500">
                                    <span class="font-semibold text-gray-800">{{ m.author }}</span>
                                    <span>{{ m.created_at }}</span>
                                </div>
                                <p class="mt-2 whitespace-pre-line text-sm text-gray-800">{{ m.body }}</p>
                                <div v-if="m.image_urls.length" class="mt-3 flex flex-wrap gap-2">
                                    <a v-for="(url, i) in m.image_urls" :key="i" :href="url" target="_blank" rel="noopener">
                                        <img :src="url" alt="Attachment" class="h-24 w-24 rounded-md border border-gray-200 object-cover" />
                                    </a>
                                </div>
                            </div>
                        </div>

                        <form class="space-y-4 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="sendReply">
                            <div>
                                <label class="block text-sm font-medium text-gray-700">Reply to customer</label>
                                <textarea v-model="replyForm.body" rows="3" required minlength="2" maxlength="2000" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></textarea>
                                <p v-if="replyForm.errors.body" class="mt-1 text-xs text-red-600">{{ replyForm.errors.body }}</p>
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
                                :disabled="replyForm.processing"
                                class="rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                                style="background-color: #4f46e5; color: #ffffff;"
                            >
                                {{ replyForm.processing ? 'Sending...' : 'Send reply' }}
                            </button>
                        </form>
                    </div>

                    <form class="h-fit space-y-4 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="saveChanges">
                        <h3 class="text-base font-semibold text-gray-900">Type, quote and status</h3>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Type</label>
                            <select v-model="updateForm.type" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                                <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <p v-if="updateForm.errors.type" class="mt-1 text-xs text-red-600">{{ updateForm.errors.type }}</p>
                        </div>

                        <div v-if="updateForm.type === 'additional'">
                            <label class="block text-sm font-medium text-gray-700">Quote amount (PHP)</label>
                            <input v-model="updateForm.quote_amount" type="number" min="1" max="1000000" step="0.01" class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                            <p v-if="updateForm.errors.quote_amount" class="mt-1 text-xs text-red-600">{{ updateForm.errors.quote_amount }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Note to customer (optional)</label>
                            <textarea v-model="updateForm.quote_note" rows="3" maxlength="1000" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></textarea>
                            <p v-if="updateForm.errors.quote_note" class="mt-1 text-xs text-red-600">{{ updateForm.errors.quote_note }}</p>
                        </div>

                        <div>
                            <label class="block text-sm font-medium text-gray-700">Status</label>
                            <select v-model="updateForm.status" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                                <option v-for="(label, key) in statuses" :key="key" :value="key">{{ label }}</option>
                            </select>
                            <p v-if="updateForm.errors.status" class="mt-1 text-xs text-red-600">{{ updateForm.errors.status }}</p>
                        </div>

                        <button
                            type="submit"
                            :disabled="updateForm.processing"
                            class="w-full rounded-md px-4 py-2 text-sm font-medium disabled:opacity-50"
                            style="background-color: #16a34a; color: #ffffff;"
                        >
                            {{ updateForm.processing ? 'Saving...' : 'Save changes' }}
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
