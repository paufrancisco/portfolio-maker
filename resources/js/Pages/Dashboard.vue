<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';

defineProps({
    portfolios: Array,
    isAdmin: Boolean,
});

const labels = {
    draft: ['Draft', '#6b7280', '#f3f4f6'],
    awaiting_payment: ['Awaiting payment review', '#92400e', '#fef3c7'],
    published: ['Published', '#166534', '#dcfce7'],
};
const badge = (status) => labels[status] ?? [status, '#374151', '#e5e7eb'];

const requestUpdate = (p) => {
    if (confirm('Send your latest changes to be reviewed and published?')) {
        router.post(route('portfolios.request-update', p.id), {}, { preserveScroll: true });
    }
};

const btn = 'rounded-md border px-3 py-1.5 text-sm font-medium';
</script>

<template>
    <Head title="Dashboard" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">My portfolios</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-5xl space-y-4 sm:px-6 lg:px-8">
                <div class="flex flex-wrap items-center gap-3">
                    <Link
                        :href="route('portfolios.create')"
                        class="rounded-md px-4 py-2 text-sm font-medium"
                        style="background-color: #4f46e5; color: #ffffff;"
                    >
                        + Create new portfolio
                    </Link>
                    <Link
                        :href="route('resumes.index')"
                        class="rounded-md px-4 py-2 text-sm font-medium"
                        style="background-color: #059669; color: #ffffff;"
                    >
                        My resumes
                    </Link>
                    <Link
                        v-if="isAdmin"
                        :href="route('admin.dashboard')"
                        class="rounded-md px-4 py-2 text-sm font-medium"
                        style="background-color: #111827; color: #ffffff;"
                    >
                        Admin dashboard
                    </Link>
                </div>

                <p v-if="!portfolios.length" class="rounded-lg bg-white p-6 text-gray-600 shadow-sm">
                    You have no portfolios yet. Click "Create new portfolio" and upload your resume.
                </p>

                <div
                    v-for="p in portfolios"
                    :key="p.id"
                    class="flex flex-wrap items-center justify-between gap-4 rounded-lg bg-white p-6 shadow-sm"
                >
                    <div class="space-y-1">
                        <p class="text-base font-semibold text-gray-900">{{ p.name }}</p>
                        <p v-if="p.headline" class="text-sm text-gray-600">{{ p.headline }}</p>
                        <div class="flex flex-wrap items-center gap-2 text-xs">
                            <span
                                class="rounded-full px-2.5 py-0.5 font-medium"
                                :style="{ color: badge(p.status)[1], backgroundColor: badge(p.status)[2] }"
                            >
                                {{ badge(p.status)[0] }}
                            </span>
                            <span class="text-gray-500">Template: {{ p.template_key }}</span>
                            <span v-if="p.status === 'published'" class="text-gray-500">{{ p.view_count }} {{ p.view_count === 1 ? 'view' : 'views' }}</span>
                            <span
                                v-if="p.has_unpublished_changes"
                                class="rounded-full px-2.5 py-0.5 font-medium"
                                style="color: #9a3412; background-color: #ffedd5;"
                            >
                                Changes not live yet
                            </span>
                        </div>
                    </div>

                    <div class="flex flex-wrap gap-2">
                        <Link :href="route('portfolios.edit', p.id)" :class="btn" class="border-gray-300 text-gray-700 hover:bg-gray-50">Edit</Link>
                        <Link :href="route('portfolios.preview', p.id)" :class="btn" class="border-gray-300 text-gray-700 hover:bg-gray-50">Preview</Link>
                        <Link
                            v-if="p.status !== 'published'"
                            :href="route('payments.create', p.id)"
                            :class="btn"
                            class="border-gray-300 text-gray-700 hover:bg-gray-50"
                        >
                            Payment
                        </Link>
                        <button
                            v-if="p.has_unpublished_changes && !p.update_requested"
                            type="button"
                            class="rounded-md px-3 py-1.5 text-sm font-medium"
                            style="background-color: #ea580c; color: #ffffff;"
                            @click="requestUpdate(p)"
                        >
                            Request update
                        </button>
                        <span v-if="p.update_requested" class="rounded-md px-3 py-1.5 text-sm" style="color: #92400e; background-color: #fef3c7;">
                            Update pending review
                        </span>
                        <a
                            v-if="p.status === 'published' && p.slug"
                            :href="route('portfolios.public', p.slug)"
                            target="_blank"
                            rel="noopener"
                            class="rounded-md px-3 py-1.5 text-sm font-medium"
                            style="background-color: #16a34a; color: #ffffff;"
                        >
                            View live
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
