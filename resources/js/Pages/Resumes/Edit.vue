<template>
    <Head title="Edit resume" />

    <AuthenticatedLayout>
        <template #header>
            <h2 class="text-xl font-semibold leading-tight text-gray-800">Edit resume</h2>
        </template>

        <div class="py-8">
            <form class="mx-auto max-w-3xl space-y-6 px-4 sm:px-6 lg:px-8" @submit.prevent="save">
                <Link :href="route('resumes.index')" class="text-sm text-blue-600 hover:text-blue-500">&larr; My resumes</Link>

                <div v-if="errorList.length" class="rounded-lg bg-red-50 p-4 text-sm text-red-800">
                    <p class="font-semibold">Please fix the following:</p>
                    <ul class="mt-1 list-disc pl-5">
                        <li v-for="(e, i) in errorList" :key="i">{{ e }}</li>
                    </ul>
                </div>
                <p v-if="$page.props.flash?.status" class="rounded-lg bg-green-50 p-3 text-sm text-green-800">
                    {{ $page.props.flash.status }}
                </p>

                <!-- Basics -->
                <section class="space-y-3 rounded-lg bg-white p-6 shadow-sm">
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div>
                            <label :class="lbl">Resume name (only you see this)</label>
                            <input v-model="form.title" required maxlength="100" :class="inp" />
                        </div>
                        <div>
                            <label :class="lbl">Template</label>
                            <select v-model="form.template_key" :class="inp">
                                <option v-for="(label, key) in templates" :key="key" :value="key">{{ label }}</option>
                            </select>
                        </div>
                    </div>
                </section>

                <!-- Personal -->
                <section class="space-y-3 rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-medium">Personal details</h3>
                    <div class="grid gap-3 sm:grid-cols-2">
                        <div><label :class="lbl">Full name</label><input v-model="form.content.personal.name" :class="inp" /></div>
                        <div><label :class="lbl">Headline</label><input v-model="form.content.personal.headline" placeholder="e.g. Junior web developer" :class="inp" /></div>
                        <div><label :class="lbl">Email</label><input v-model="form.content.personal.email" type="email" :class="inp" /></div>
                        <div><label :class="lbl">Phone</label><input v-model="form.content.personal.phone" :class="inp" /></div>
                        <div><label :class="lbl">Location</label><input v-model="form.content.personal.location" :class="inp" /></div>
                        <div><label :class="lbl">Website</label><input v-model="form.content.personal.website" :class="inp" /></div>
                        <div class="sm:col-span-2"><label :class="lbl">LinkedIn</label><input v-model="form.content.personal.linkedin" :class="inp" /></div>
                    </div>
                </section>

                <!-- Summary -->
                <section class="space-y-3 rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-medium">Summary</h3>
                    <textarea v-model="form.content.summary" rows="4" maxlength="2000" :class="inp"></textarea>
                </section>

                <!-- Experience -->
                <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-medium">Experience</h3>
                    <div v-for="(row, i) in form.content.experience" :key="i" class="space-y-2 rounded-lg border p-4">
                        <div class="grid gap-2 sm:grid-cols-3">
                            <input v-model="row.role" placeholder="Role" :class="inp" />
                            <input v-model="row.company" placeholder="Company" :class="inp" />
                            <input v-model="row.period" placeholder="e.g. 2023 - Present" :class="inp" />
                        </div>
                        <textarea v-model="row.details" rows="3" placeholder="What you did" :class="inp"></textarea>
                        <button type="button" class="text-xs text-red-600" @click="form.content.experience.splice(i, 1)">Remove</button>
                    </div>
                    <button type="button" :class="addBtn" @click="form.content.experience.push({ role: '', company: '', period: '', details: '' })">
                        + Add experience
                    </button>
                </section>

                <!-- Education -->
                <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-medium">Education</h3>
                    <div v-for="(row, i) in form.content.education" :key="i" class="space-y-2 rounded-lg border p-4">
                        <div class="grid gap-2 sm:grid-cols-3">
                            <input v-model="row.school" placeholder="School" :class="inp" />
                            <input v-model="row.degree" placeholder="Degree" :class="inp" />
                            <input v-model="row.period" placeholder="e.g. 2019 - 2023" :class="inp" />
                        </div>
                        <textarea v-model="row.details" rows="2" placeholder="Honors, thesis, activities (optional)" :class="inp"></textarea>
                        <button type="button" class="text-xs text-red-600" @click="form.content.education.splice(i, 1)">Remove</button>
                    </div>
                    <button type="button" :class="addBtn" @click="form.content.education.push({ school: '', degree: '', period: '', details: '' })">
                        + Add education
                    </button>
                </section>

                <!-- Skills -->
                <section class="space-y-3 rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-medium">Skills</h3>
                    <input v-model="skillsText" placeholder="PHP, Laravel, Vue, MySQL" :class="inp" />
                    <p class="text-xs text-gray-500">Separate skills with commas.</p>
                </section>

                <!-- Projects -->
                <section class="space-y-4 rounded-lg bg-white p-6 shadow-sm">
                    <h3 class="font-medium">Projects</h3>
                    <div v-for="(row, i) in form.content.projects" :key="i" class="space-y-2 rounded-lg border p-4">
                        <div class="grid gap-2 sm:grid-cols-2">
                            <input v-model="row.name" placeholder="Project name" :class="inp" />
                            <input v-model="row.link" placeholder="Link (optional)" :class="inp" />
                        </div>
                        <textarea v-model="row.details" rows="2" placeholder="Short description" :class="inp"></textarea>
                        <button type="button" class="text-xs text-red-600" @click="form.content.projects.splice(i, 1)">Remove</button>
                    </div>
                    <button type="button" :class="addBtn" @click="form.content.projects.push({ name: '', link: '', details: '' })">
                        + Add project
                    </button>
                </section>

                <button
                    type="submit"
                    :disabled="form.processing"
                    class="rounded-lg bg-gray-900 px-6 py-2.5 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                >
                    {{ form.processing ? 'Saving...' : 'Save resume' }}
                </button>
            </form>
        </div>
    </AuthenticatedLayout>
</template>

<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';
import { computed, ref } from 'vue';

const props = defineProps({
    resume: Object,
    templates: Object,
});

const inp = 'mt-1 w-full rounded-lg border-gray-300 text-sm';
const lbl = 'block text-sm font-medium';
const addBtn = 'rounded-md border border-gray-300 px-3 py-1.5 text-sm font-medium text-gray-700 hover:bg-gray-50';

// Reminder: the field is named "content" because useForm already has a data() method
const form = useForm({
    title: props.resume.title,
    template_key: props.resume.template_key,
    content: props.resume.content,
});

const skillsText = ref((props.resume.content.skills ?? []).join(', '));

const errorList = computed(() => Object.values(form.errors));

const save = () =>
    form
        .transform((d) => ({
            ...d,
            content: { ...d.content, skills: skillsText.value.split(',').map((s) => s.trim()).filter(Boolean) },
        }))
        .put(route('resumes.update', props.resume.id), { preserveScroll: true });
</script>
