<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import PrimaryButton from '@/Components/PrimaryButton.vue';
import InputError from '@/Components/InputError.vue';
import InputLabel from '@/Components/InputLabel.vue';
import TextInput from '@/Components/TextInput.vue';
import { Head, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({
    portfolio: Object,
    templates: Array,
});

const d = props.portfolio.data ?? {};

const sections = [
    {
        key: 'experience',
        label: 'Work Experience',
        blank: { company: '', position: '', type: '', start: '', end: '', description: '' },
        fields: [
            { key: 'company', label: 'Company' },
            { key: 'position', label: 'Position' },
            { key: 'type', label: 'Employment type (Full-time, Part-time...)' },
            { key: 'start', label: 'Start (e.g. Jun 2022)' },
            { key: 'end', label: 'End (blank = Present)' },
            { key: 'description', label: 'Responsibilities / description', area: true },
        ],
    },
    {
        key: 'projects',
        label: 'Projects',
        blank: { title: '', role: '', description: '', technologies: '', repo_url: '', demo_url: '', image_url: '' },
        fields: [
            { key: 'title', label: 'Project title' },
            { key: 'role', label: 'Your role' },
            { key: 'description', label: 'Description', area: true },
            { key: 'technologies', label: 'Technologies (comma separated)' },
            { key: 'repo_url', label: 'Repository URL' },
            { key: 'demo_url', label: 'Live demo URL' },
        ],
    },
    {
        key: 'certifications',
        label: 'Certifications & Awards',
        blank: { title: '', issuer: '', date: '', description: '' },
        fields: [
            { key: 'title', label: 'Title' },
            { key: 'issuer', label: 'Issuing organization' },
            { key: 'date', label: 'Date issued' },
            { key: 'description', label: 'Description', area: true },
        ],
    },
    {
        key: 'education',
        label: 'Education',
        blank: { school: '', degree: '', year: '', achievements: '' },
        fields: [
            { key: 'school', label: 'School' },
            { key: 'degree', label: 'Degree / course' },
            { key: 'year', label: 'Graduation year' },
            { key: 'achievements', label: 'Achievements', area: true },
        ],
    },
];

const profile = {
    name: d.name ?? '',
    email: d.email ?? '',
    phone: d.phone ?? '',
    headline: d.headline ?? '',
    intro: d.intro ?? '',
    about: d.about ?? '',
    skills: Array.isArray(d.skills) ? d.skills.join(', ') : (d.skills ?? ''),
    linkedin: d.linkedin ?? '',
    github: d.github ?? '',
    website: d.website ?? '',
    photo_url: d.photo_url ?? '',
    show_resume: d.show_resume ?? false,
};
sections.forEach((s) => {
    profile[s.key] = Array.isArray(d[s.key]) ? d[s.key].map((row) => ({ ...s.blank, ...row })) : [];
});

const form = useForm({
    template_key: props.portfolio.template_key,
    profile,
});

const add = (s) => form.profile[s.key].push({ ...s.blank });
const remove = (s, i) => form.profile[s.key].splice(i, 1);
const move = (s, i, dir) => {
    const arr = form.profile[s.key];
    const j = i + dir;
    if (j < 0 || j >= arr.length) return;
    [arr[i], arr[j]] = [arr[j], arr[i]];
};

const uploading = ref(false);
const uploadError = ref('');

const upload = async (event, setUrl) => {
    const file = event.target.files[0];
    if (!file) return;
    uploading.value = true;
    uploadError.value = '';
    try {
        const body = new FormData();
        body.append('image', file);
        const token = decodeURIComponent(document.cookie.split('; ').find((c) => c.startsWith('XSRF-TOKEN='))?.split('=')[1] ?? '');
        const res = await fetch(route('portfolios.images', props.portfolio.id), {
            method: 'POST',
            body,
            headers: { 'X-XSRF-TOKEN': token, Accept: 'application/json' },
            credentials: 'same-origin',
        });
        const json = await res.json();
        if (!res.ok) throw new Error(json.errors?.image?.[0] ?? json.message ?? 'Upload failed');
        setUrl(json.url);
    } catch (e) {
        uploadError.value = e.message;
    } finally {
        uploading.value = false;
        event.target.value = '';
    }
};

const submit = () => {
    form.put(route('portfolios.update', props.portfolio.id), { preserveScroll: true });
};

const area =
    'mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500';
</script>

<template>
    <Head title="Edit Portfolio" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Fill up your portfolio</h2>
        </template>

        <div class="py-12">
            <div class="mx-auto max-w-3xl space-y-6 sm:px-6 lg:px-8">
                <div v-if="portfolio.data?.imported_from_resume" class="rounded-lg bg-yellow-50 p-4 text-sm text-yellow-800">
                    We extracted this information from your resume. It may not always be accurate, so please review and correct each section before saving.
                </div>

                <form @submit.prevent="submit" class="space-y-8 rounded-lg bg-white p-6 shadow-sm">
                    <!-- Template -->
                    <div>
                        <InputLabel value="Choose a template" />
                        <div class="mt-2 grid grid-cols-1 gap-3 sm:grid-cols-3">
                            <label
                                v-for="t in templates"
                                :key="t.key"
                                class="cursor-pointer rounded-lg border-2 p-4 text-center"
                                :class="form.template_key === t.key ? 'border-indigo-500 bg-indigo-50' : 'border-gray-200'"
                            >
                                <input type="radio" class="sr-only" :value="t.key" v-model="form.template_key" />
                                <div class="mx-auto mb-2 h-6 w-full rounded" :style="{ background: t.theme_options?.accent }"></div>
                                <span class="text-sm font-medium text-gray-800">{{ t.name }}</span>
                            </label>
                        </div>
                        <InputError :message="form.errors.template_key" class="mt-2" />
                    </div>

                    <!-- Basics -->
                    <div class="space-y-4">
                        <h3 class="font-semibold text-gray-800">Basic information</h3>
                        <div>
                            <InputLabel value="Profile photo (jpg, png, webp, max 2MB)" />
                            <div class="mt-2 flex items-center gap-4">
                                <img v-if="form.profile.photo_url" :src="form.profile.photo_url" class="h-20 w-20 rounded-full object-cover" alt="" />
                                <input type="file" accept=".jpg,.jpeg,.png,.webp" class="text-sm text-gray-700" @change="upload($event, (u) => (form.profile.photo_url = u))" />
                                <button v-if="form.profile.photo_url" type="button" class="text-sm text-red-600" @click="form.profile.photo_url = ''">Remove</button>
                            </div>
                            <p v-if="uploading" class="mt-1 text-sm text-gray-500">Uploading...</p>
                            <p v-if="uploadError" class="mt-1 text-sm text-red-600">{{ uploadError }}</p>
                            <InputError :message="form.errors['profile.photo_url']" class="mt-1" />
                        </div>
                        <label class="flex items-start gap-3 rounded-lg border border-gray-200 p-3 text-sm text-gray-700">
                            <input type="checkbox" v-model="form.profile.show_resume" class="mt-0.5 rounded border-gray-300 text-indigo-600" />
                            <span>
                                Show a <strong>Download Resume</strong> button on my portfolio.
                                <span class="block text-xs text-gray-500">Visitors will download the resume you uploaded. Any email address and phone number in it will be visible.</span>
                            </span>
                        </label>
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
                            <InputLabel for="intro" value="Short introduction (shown in the hero)" />
                            <textarea id="intro" v-model="form.profile.intro" rows="3" :class="area"></textarea>
                            <InputError :message="form.errors['profile.intro']" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="about" value="About you" />
                            <textarea id="about" v-model="form.profile.about" rows="5" :class="area"></textarea>
                            <InputError :message="form.errors['profile.about']" class="mt-2" />
                        </div>
                        <div>
                            <InputLabel for="skills" value="Skills (comma separated)" />
                            <textarea id="skills" v-model="form.profile.skills" rows="2" :class="area" placeholder="Laravel, Vue.js, Python, Git"></textarea>
                            <InputError :message="form.errors['profile.skills']" class="mt-2" />
                        </div>
                        <div class="grid grid-cols-1 gap-4 sm:grid-cols-3">
                            <div>
                                <InputLabel for="linkedin" value="LinkedIn URL" />
                                <TextInput id="linkedin" v-model="form.profile.linkedin" class="mt-1 block w-full" />
                                <InputError :message="form.errors['profile.linkedin']" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="github" value="GitHub URL" />
                                <TextInput id="github" v-model="form.profile.github" class="mt-1 block w-full" />
                                <InputError :message="form.errors['profile.github']" class="mt-2" />
                            </div>
                            <div>
                                <InputLabel for="website" value="Website URL" />
                                <TextInput id="website" v-model="form.profile.website" class="mt-1 block w-full" />
                                <InputError :message="form.errors['profile.website']" class="mt-2" />
                            </div>
                        </div>
                    </div>

                    <!-- Repeatable sections -->
                    <div v-for="s in sections" :key="s.key" class="space-y-4 border-t pt-6">
                        <div class="flex items-center justify-between">
                            <h3 class="font-semibold text-gray-800">{{ s.label }}</h3>
                            <button type="button" class="rounded-md border border-indigo-500 px-3 py-1 text-sm text-indigo-600" @click="add(s)">
                                + Add
                            </button>
                        </div>

                        <p v-if="!form.profile[s.key].length" class="text-sm text-gray-500">
                            No entries yet. This section will not appear on your portfolio until you add one.
                        </p>

                        <div v-for="(row, i) in form.profile[s.key]" :key="i" class="space-y-3 rounded-lg border border-gray-200 p-4">
                            <div v-if="s.key === 'projects'">
                                <InputLabel value="Project image (jpg, png, webp, max 2MB)" />
                                <div class="mt-2 flex items-center gap-4">
                                    <img v-if="row.image_url" :src="row.image_url" class="h-16 w-24 rounded object-cover" alt="" />
                                    <input type="file" accept=".jpg,.jpeg,.png,.webp" class="text-sm text-gray-700" @change="upload($event, (u) => (row.image_url = u))" />
                                    <button v-if="row.image_url" type="button" class="text-sm text-red-600" @click="row.image_url = ''">Remove</button>
                                </div>
                            </div>
                            <div v-for="f in s.fields" :key="f.key">
                                <InputLabel :value="f.label" />
                                <textarea v-if="f.area" v-model="row[f.key]" rows="3" :class="area"></textarea>
                                <TextInput v-else v-model="row[f.key]" class="mt-1 block w-full" />
                                <InputError :message="form.errors[`profile.${s.key}.${i}.${f.key}`]" class="mt-1" />
                            </div>
                            <div class="flex gap-3 text-sm">
                                <button type="button" class="text-gray-600" @click="move(s, i, -1)">↑ Up</button>
                                <button type="button" class="text-gray-600" @click="move(s, i, 1)">↓ Down</button>
                                <button type="button" class="text-red-600" @click="remove(s, i)">Delete</button>
                            </div>
                        </div>
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
