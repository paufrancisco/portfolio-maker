<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({
    resume: null,
});

const submit = () => {
    form.post(route('portfolios.store'));
};
</script>

<template>
    <Head title="Upload Resume" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Upload your resume
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-2xl sm:px-6 lg:px-8">
                <form
                    @submit.prevent="submit"
                    class="space-y-4 rounded-lg bg-white p-6 shadow-sm"
                >
                    <p class="text-sm text-gray-600">
                        Accepted formats: PDF, DOC, DOCX (max 5 MB).
                    </p>

                    <input
                        type="file"
                        accept=".pdf,.doc,.docx"
                        class="block w-full text-sm text-gray-700"
                        @input="form.resume = $event.target.files[0]"
                    />
                    <InputError :message="form.errors.resume" />

                    <PrimaryButton :disabled="form.processing || !form.resume">
                        {{ form.processing ? 'Uploading...' : 'Upload resume' }}
                    </PrimaryButton>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
