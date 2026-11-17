<script setup>
import { computed, ref } from 'vue';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    changeRequest: Object,
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
    form.post(route('change-requests.reply', props.changeRequest.id), {
        forceFormData: true,
        preserveScroll: true,
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
    <Head :title="changeRequest.title" />

    <AuthenticatedLayout>
        <template #header>
            <div class="flex flex-wrap items-center justify-between gap-3">
                <h2 class="text-xl font-semibold leading-tight text-gray-800">{{ changeRequest.title }}</h2>
                <Link :href="route('change-requests.index')" class="text-sm text-gray-600 hover:text-gray-900">
                    Back to all requests
                </Link>
            </div>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <div class="space-y-3 rounded-lg bg-white p-6 shadow-sm">
                    <p class="text-sm text-gray-600">{{ changeRequest.portfolio }}</p>
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

                    <div
                        v-if="changeRequest.quote_amount"
                        class="rounded-md p-4 text-sm"
                        style="background-color: #eff6ff; color: #1e3a8a;"
                    >
                        <p class="font-semibold">Quote: PHP {{ changeRequest.quote_amount }}</p>
                        <p v-if="changeRequest.quote_note" class="mt-1 whitespace-pre-line">{{ changeRequest.quote_note }}</p>
                    </div>
                    <p
                        v-else-if="changeRequest.type === 'minor_fix'"
                        class="rounded-md p-4 text-sm"
                        style="background-color: #f0fdf4; color: #166534;"
                    >
                        This is a minor fix and it is free of charge.
                    </p>
                </div>

                <div class="space-y-3">
                    <div
                        v-for="m in changeRequest.messages"
                        :key="m.id"
                        class="rounded-lg p-4 shadow-sm"
                        :style="m.is_admin ? 'background-color: #eef2ff;' : 'background-color: #ffffff;'"
                    >
                        <div class="flex items-center justify-between gap-3 text-xs text-gray-500">
                            <span class="font-semibold text-gray-800">{{ m.author }}</span>
                            <span>{{ formatDate(m.created_at) }}</span>
                        </div>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-800">{{ m.body }}</p>
                        <div v-if="m.image_urls.length" class="mt-3 flex flex-wrap gap-2">
                            <a v-for="(url, i) in m.image_urls" :key="i" :href="url" target="_blank" rel="noopener">
                                <img :src="url" alt="Attachment" class="h-24 w-24 rounded-md border border-gray-200 object-cover" />
                            </a>
                        </div>
                    </div>
                </div>

                <form class="space-y-4 rounded-lg bg-white p-6 shadow-sm" @submit.prevent="submit">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Reply</label>
                        <textarea v-model="form.body" rows="3" required minlength="2" maxlength="2000" class="mt-1 w-full rounded-lg border-gray-300 text-sm"></textarea>
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
                        {{ form.processing ? 'Sending...' : 'Send reply' }}
                    </button>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
