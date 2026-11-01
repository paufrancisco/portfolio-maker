<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    portfolio: Object,
    templates: Array,
});

const page = usePage();
const status = computed(() => page.props.flash?.status ?? null);

const form = useForm({
    template_key: props.portfolio.template_key,
    profile: {
        name: props.portfolio.data?.name ?? '',
        email: props.portfolio.data?.email ?? '',
        phone: props.portfolio.data?.phone ?? '',
        headline: props.portfolio.data?.headline ?? '',
        about: props.portfolio.data?.about ?? '',
    },
});

const submit = () => {
    form.put(route('portfolios.update', props.portfolio.id), {
        preserveScroll: true,
    });
};
</script>

<template>
    <Head title="Edit Portfolio" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">
                Fill up your portfolio
            </h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <form
                    @submit.prevent="submit"
                    class="space-y-6 rounded-lg bg-white p-6 shadow-sm"
                >
                    <div>
                        <InputLabel value="Choose a template" />
                        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <label
                                v-for="t in templates"
                                :key="t.key"
                                class="cursor-pointer rounded-lg border-2 p-4 text-center"
                                :class="
                                    form.template_key === t.key
                                        ? 'border-indigo-500 bg-indigo-50'
                                        : 'border-gray-200'
                                "
                            >
                                <input
                                    type="radio"
                                    class="sr-only"
                                    :value="t.key"
                                    v-model="form.template_key"
                                />
                                <div
                                    class="mx-auto mb-2 h-6 w-full rounded"
                                    :style="{ background: t.theme_options?.accent }"
                                ></div>
                                <span class="text-sm font-medium text-gray-800">
                                    {{ t.name }}
                                </span>
                            </label>
                        </div>
                        <InputError :message="form.errors.template_key" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="name" value="Full name" />
                        <TextInput id="name" v-model="form.profile.name" class="mt-1 block w-full" />
                        <InputError :message="form.errors['profile.name']" class="mt-2" />
                    </div>

                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <InputLabel for="email" value="Email" />
                            <TextInput id="email" type="email" v-model="form.profile.email" class="mt-1 block w-full" />
                            <InputError :message="form.errors['profile.email']" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="phone" value="Phone" />
                            <TextInput id="phone" v-model="form.profile.phone" class="mt-1 block w-full" />
                            <InputError :message="form.errors['profile.phone']" class="mt-2" />
                        </div>
                    </div>

                    <div>
                        <InputLabel for="headline" value="Headline (e.g. Full Stack Developer)" />
                        <TextInput id="headline" v-model="form.profile.headline" class="mt-1 block w-full" />
                        <InputError :message="form.errors['profile.headline']" class="mt-2" />
                    </div>

                    <div>
                        <InputLabel for="about" value="About you" />
                        <textarea
                            id="about"
                            v-model="form.profile.about"
                            rows="5"
                            class="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                        ></textarea>
                        <InputError :message="form.errors['profile.about']" class="mt-2" />
                    </div>

                    <div class="flex items-center gap-4">
                        <PrimaryButton :disabled="form.processing">Save</PrimaryButton>
                        <span v-if="form.recentlySuccessful" class="text-sm text-green-600">Saved.</span>
                    </div>
                </form>
            </div>
        </div>
    </AuthenticatedLayout>
</template>
