<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

const props = defineProps({
    types: Object,
    selectedType: String,
    email: String,
});

const form = useForm({
    type: props.selectedType,
    email: props.email || '',
    details: '',
    attachment: null,
});

const submit = () => form.post('/services/request');
</script>

<template>
    <Head title="Request a service" />

    <div class="min-h-screen bg-gray-50 text-gray-800">
        <header class="mx-auto flex max-w-3xl items-center justify-between px-6 py-5">
            <Link href="/" class="text-lg font-bold">CampusCraft</Link>
        </header>

        <main class="mx-auto max-w-3xl px-6 pb-16">
            <h1 class="text-2xl font-bold">Request a service</h1>
            <p class="mt-2 text-sm text-gray-600">
                Tell us what you need. We will review it and send you a price by email. No account needed.
            </p>

            <form class="mt-6 space-y-5 rounded-xl border bg-white p-6 shadow-sm" @submit.prevent="submit">
                <div>
                    <label class="block text-sm font-medium">Service</label>
                    <select v-model="form.type" class="mt-1 w-full rounded-lg border-gray-300 text-sm">
                        <option v-for="(label, key) in types" :key="key" :value="key">{{ label }}</option>
                    </select>
                    <p v-if="form.errors.type" class="mt-1 text-xs text-red-600">{{ form.errors.type }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium">Your email</label>
                    <input v-model="form.email" type="email" required class="mt-1 w-full rounded-lg border-gray-300 text-sm" />
                    <p v-if="form.errors.email" class="mt-1 text-xs text-red-600">{{ form.errors.email }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium">Details or the problem</label>
                    <textarea
                        v-model="form.details"
                        rows="7"
                        required
                        class="mt-1 w-full rounded-lg border-gray-300 text-sm"
                        placeholder="Describe the system you want built, the bug, or the problem you need solved."
                    ></textarea>
                    <p v-if="form.errors.details" class="mt-1 text-xs text-red-600">{{ form.errors.details }}</p>
                </div>

                <div>
                    <label class="block text-sm font-medium">Attachment (optional)</label>
                    <input
                        type="file"
                        class="mt-1 block w-full text-sm"
                        @input="form.attachment = $event.target.files[0] || null"
                    />
                    <p class="mt-1 text-xs text-gray-500">PDF, ZIP, DOC, DOCX, TXT, PNG or JPG, up to 10 MB.</p>
                    <p v-if="form.errors.attachment" class="mt-1 text-xs text-red-600">{{ form.errors.attachment }}</p>
                </div>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="w-full rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                >
                    Send request
                </button>
            </form>
        </main>
    </div>
</template>
