<template>
    <Head title="My resumes" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">My resumes</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-4xl space-y-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center gap-3 rounded-lg bg-white p-4 shadow-sm">
                    <select v-model="template" class="rounded-lg border-gray-300 text-sm">
                        <option v-for="(label, key) in templates" :key="key" :value="key">{{ label }} template</option>
                    </select>
                    <button
                        type="button"
                        class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500"
                        @click="createResume"
                    >
                        + New resume
                    </button>
                </div>

                <p v-if="!resumes.length" class="rounded-lg bg-white p-6 text-gray-600 shadow-sm">
                    You have no resumes yet. Pick a template and click "New resume".
                </p>

                <div
                    v-for="r in resumes"
                    :key="r.id"
                    class="flex flex-wrap items-center justify-between gap-4 rounded-lg bg-white p-6 shadow-sm"
                >
                    <div>
                        <p class="font-semibold text-gray-900">{{ r.title }}</p>
                        <p class="text-xs text-gray-500">{{ r.template_label }} template &middot; saved {{ r.updated_at }}</p>
                    </div>
                    <div class="flex gap-2">
                        <Link :href="route('resumes.edit', r.id)" class="rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50">
                            Edit
                        </Link>
                        <button type="button" class="rounded-md border border-red-300 px-3 py-1.5 text-sm font-medium text-red-700 hover:bg-red-50" @click="remove(r)">
                            Delete
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { ref } from 'vue';

defineProps({
    resumes: Array,
    templates: Object,
});

const template = ref('classic');

const createResume = () => router.post(route('resumes.store'), { template_key: template.value });

const remove = (r) => {
    if (confirm(`Delete "${r.title}"? This cannot be undone.`)) {
        router.delete(route('resumes.destroy', r.id), { preserveScroll: true });
    }
};
</script>
