<script setup>
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    reviews: { type: Array, default: () => [] },
    reviewStats: { type: Object, default: () => ({ count: 0, average: 0 }) },
});

const thesisForm = useForm({ email: '', type: 'thesis_build' });
const tutorForm = useForm({ email: '', type: 'math_tutor' });
const portfolioForm = useForm({ email: '', mode: 'upload' });
const resumeForm = useForm({ email: '' });

const portfolioTemplates = [
    { key: 'professional', name: 'Professional', desc: 'Clean and corporate.', swatch: 'bg-gray-800' },
    { key: 'pink', name: 'Pink Elegance', desc: 'Soft and stylish.', swatch: 'bg-pink-500' },
    { key: 'blue', name: 'Blue Developer', desc: 'Built for developers.', swatch: 'bg-blue-600' },
];

const resumeTemplates = [
    { key: 'classic', name: 'Classic', desc: 'One column, traditional.', swatch: 'bg-gray-700' },
    { key: 'modern', name: 'Modern', desc: 'Two columns with a sidebar.', swatch: 'bg-indigo-600' },
    { key: 'minimal', name: 'Minimal', desc: 'Simple and ATS-friendly.', swatch: 'bg-emerald-600' },
];

const submitThesis = () => thesisForm.get('/services/request');
const submitTutor = () => tutorForm.get('/services/request');
const submitPortfolio = () => portfolioForm.post('/start');
const submitResume = () => resumeForm.post('/resume');
</script>

<template>
    <Head title="Welcome" />

    <div class="min-h-screen bg-gray-50 text-gray-800">
        <header class="mx-auto flex max-w-6xl items-center justify-between px-6 py-5">
            <span class="text-lg font-bold">Portfolio Maker</span>
            <nav v-if="canLogin" class="text-sm">
                <Link href="/login" class="text-gray-600 hover:text-gray-900">Log in</Link>
            </nav>
        </header>

        <main class="mx-auto max-w-6xl px-6 pb-16">
            <section class="py-10 text-center">
                <h1 class="text-3xl font-bold sm:text-4xl">What do you need help with?</h1>
                <p class="mt-3 text-gray-600">Pick a service and enter your email. No account needed.</p>
            </section>

            <section class="grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                <!-- Thesis consultation -->
                <div class="flex flex-col rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold">Thesis Consultation</h2>
                    <p class="mt-2 flex-1 text-sm text-gray-600">
                        Propose a system you want built, or ask for help debugging your existing project. Price is quoted after review.
                    </p>
                    <form class="mt-5 space-y-3" @submit.prevent="submitThesis">
                        <select v-model="thesisForm.type" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="thesis_build">Build a system</option>
                            <option value="thesis_debug">Debug my project</option>
                        </select>
                        <input
                            v-model="thesisForm.email"
                            type="email"
                            required
                            placeholder="you@example.com"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        />
                        <button
                            type="submit"
                            :disabled="thesisForm.processing"
                            class="w-full rounded-lg bg-gray-900 px-4 py-2 text-sm font-medium text-white hover:bg-gray-700 disabled:opacity-50"
                        >
                            Request consultation
                        </button>
                    </form>
                </div>

                <!-- Tutoring -->
                <div class="flex flex-col rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold">Tutoring</h2>
                    <p class="mt-2 flex-1 text-sm text-gray-600">
                        Send your Math or Programming problem and get a screen-recorded walkthrough with voice-over. Price is quoted after review.
                    </p>
                    <form class="mt-5 space-y-3" @submit.prevent="submitTutor">
                        <select v-model="tutorForm.type" class="w-full rounded-lg border-gray-300 text-sm">
                            <option value="math_tutor">Math</option>
                            <option value="programming_tutor">Programming</option>
                        </select>
                        <input
                            v-model="tutorForm.email"
                            type="email"
                            required
                            placeholder="you@example.com"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        />
                        <button
                            type="submit"
                            :disabled="tutorForm.processing"
                            class="w-full rounded-lg bg-violet-600 px-4 py-2 text-sm font-medium text-white hover:bg-violet-500 disabled:opacity-50"
                        >
                            Send my problem
                        </button>
                    </form>
                </div>

                <!-- Online portfolio -->
                <div class="flex flex-col rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold">Online Portfolio</h2>
                    <p class="mt-2 text-sm text-gray-600">
                        Pick a template, add your details, and publish your own portfolio page.
                    </p>
                    <form class="mt-4 flex flex-1 flex-col space-y-3" @submit.prevent="submitPortfolio">
                        <div class="grid grid-cols-2 gap-2 text-sm">
                            <label
                                :class="[portfolioForm.mode === 'upload' ? 'border-blue-600 bg-blue-50' : 'border-gray-200', 'cursor-pointer rounded-lg border p-3 text-center']"
                            >
                                <input v-model="portfolioForm.mode" type="radio" value="upload" class="sr-only" />
                                Upload my resume
                            </label>
                            <label
                                :class="[portfolioForm.mode === 'manual' ? 'border-blue-600 bg-blue-50' : 'border-gray-200', 'cursor-pointer rounded-lg border p-3 text-center']"
                            >
                                <input v-model="portfolioForm.mode" type="radio" value="manual" class="sr-only" />
                                Fill up details
                            </label>
                        </div>
                        <input
                            v-model="portfolioForm.email"
                            type="email"
                            required
                            placeholder="you@example.com"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        />
                        <p v-if="portfolioForm.errors.email" class="text-xs text-red-600">{{ portfolioForm.errors.email }}</p>
                        <button
                            type="submit"
                            :disabled="portfolioForm.processing"
                            class="w-full rounded-lg bg-blue-600 px-4 py-2 text-sm font-medium text-white hover:bg-blue-500 disabled:opacity-50"
                        >
                            Build my portfolio
                        </button>
                    </form>
                </div>

                <!-- Resume maker -->
                <div class="flex flex-col rounded-xl border bg-white p-6 shadow-sm">
                    <h2 class="text-xl font-semibold">Resume Maker</h2>
                    <p class="mt-2 flex-1 text-sm text-gray-600">
                        Choose a resume template, fill up your details, and get a ready-to-send resume.
                    </p>
                    <form class="mt-5 space-y-3" @submit.prevent="submitResume">
                        <input
                            v-model="resumeForm.email"
                            type="email"
                            required
                            placeholder="you@example.com"
                            class="w-full rounded-lg border-gray-300 text-sm"
                        />
                        <p v-if="resumeForm.errors.email" class="text-xs text-red-600">{{ resumeForm.errors.email }}</p>
                        <button
                            type="submit"
                            :disabled="resumeForm.processing"
                            class="w-full rounded-lg bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-500 disabled:opacity-50"
                        >
                            Make my resume
                        </button>
                    </form>
                </div>
            </section>

            <section class="mt-14">
                <h2 class="text-center text-xl font-semibold">Portfolio templates</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div
                        v-for="t in portfolioTemplates"
                        :key="t.key"
                        class="overflow-hidden rounded-xl border bg-white shadow-sm"
                    >
                        <div :class="[t.swatch, 'h-24']"></div>
                        <div class="p-4">
                            <h3 class="font-medium">{{ t.name }}</h3>
                            <p class="mt-1 text-xs text-gray-500">{{ t.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section class="mt-12">
                <h2 class="text-center text-xl font-semibold">Resume templates</h2>
                <div class="mt-6 grid gap-4 sm:grid-cols-3">
                    <div
                        v-for="t in resumeTemplates"
                        :key="t.key"
                        class="overflow-hidden rounded-xl border bg-white shadow-sm"
                    >
                        <div :class="[t.swatch, 'h-24']"></div>
                        <div class="p-4">
                            <h3 class="font-medium">{{ t.name }}</h3>
                            <p class="mt-1 text-xs text-gray-500">{{ t.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <section v-if="reviews.length" class="mt-14">
                <h2 class="text-center text-xl font-semibold">Customer reviews</h2>
                <p class="mt-1 text-center text-sm text-gray-600">
                    <span class="text-amber-500">★</span> {{ reviewStats.average }} from {{ reviewStats.count }} verified
                    {{ reviewStats.count === 1 ? 'client' : 'clients' }}
                </p>
                <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                    <div v-for="(r, i) in reviews" :key="i" class="rounded-xl border bg-white p-5 shadow-sm">
                        <p class="text-amber-500">{{ '★'.repeat(r.rating) }}{{ '☆'.repeat(5 - r.rating) }}</p>
                        <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ r.comment }}</p>
                        <div class="mt-4 flex items-center justify-between text-xs">
                            <span class="font-medium">{{ r.name }}</span>
                            <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-emerald-700">Verified client</span>
                        </div>
                        <p class="mt-1 text-xs text-gray-400">{{ r.service }}</p>
                    </div>
                </div>
            </section>
        </main>
    </div>
</template>
