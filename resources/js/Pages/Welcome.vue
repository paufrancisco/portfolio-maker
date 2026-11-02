<script setup>
import { Head, Link, router, useForm } from '@inertiajs/vue3';

defineProps({
    canLogin: Boolean,
    canRegister: Boolean,
    reviews: { type: Array, default: () => [] },
    reviewStats: { type: Object, default: () => ({ count: 0, average: 0 }) },
});

const siteName = 'CampusCraft';
const contactEmail = 'francisco.pfa.pf@gmail.com';

const thesisForm = useForm({ email: '', type: 'thesis_build' });
const tutorForm = useForm({ email: '', type: 'math_tutor' });
const portfolioForm = useForm({ email: '', mode: 'upload' });
const resumeForm = useForm({ email: '' });

const steps = [
    { n: 1, title: 'Tell us what you need', text: 'Pick a service and enter your email. Thesis help and tutoring need no account.' },
    { n: 2, title: 'Review and pay', text: 'For thesis help and tutoring we send a price first. You pay by QR and upload your proof.' },
    { n: 3, title: 'Get your result', text: 'Your portfolio goes live, or your video and files arrive by email.' },
];

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
// Reminder: portfolio clients sign up normally; the card only prefills their email
const submitPortfolio = () => router.get('/register', { email: portfolioForm.email });
// Reminder: Resume Maker is not built yet, so its card is disabled (no /resume route)
</script>

<template>
    <Head title="Home" />

    <div class="min-h-screen scroll-smooth bg-white text-gray-800">
        <!-- Sticky menu -->
        <header class="sticky top-0 z-30 border-b bg-white/90 backdrop-blur">
            <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
                <a href="#top" class="text-lg font-bold">{{ siteName }}</a>
                <nav class="hidden items-center gap-6 text-sm text-gray-600 md:flex">
                    <a href="#services" class="hover:text-gray-900">Services</a>
                    <a href="#templates" class="hover:text-gray-900">Templates</a>
                    <a href="#how" class="hover:text-gray-900">How it works</a>
                    <a v-if="reviews.length" href="#reviews" class="hover:text-gray-900">Reviews</a>
                </nav>
                <div class="flex items-center gap-4 text-sm">
                    <Link v-if="canLogin" href="/login" class="text-gray-600 hover:text-gray-900">Log in</Link>
                    <a href="#services" class="rounded-lg bg-gray-900 px-4 py-2 font-medium text-white hover:bg-gray-700">
                        Get started
                    </a>
                </div>
            </div>
        </header>

        <main id="top">
            <!-- Hero -->
            <section class="bg-gradient-to-b from-gray-50 to-white">
                <div class="mx-auto max-w-4xl px-6 py-16 text-center sm:py-24">
                    <p class="text-sm font-medium uppercase tracking-wide text-blue-600">Made for students and graduates</p>
                    <h1 class="mt-4 text-4xl font-bold leading-tight sm:text-5xl">
                        Your portfolio, resume, thesis help and tutoring. All in one place.
                    </h1>
                    <p class="mx-auto mt-5 max-w-2xl text-lg text-gray-600">
                        Choose what you need, enter your email, and we take it from there. No account needed.
                    </p>
                    <div class="mt-8 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        <a href="#services" class="rounded-lg bg-blue-600 px-6 py-3 font-medium text-white hover:bg-blue-500">
                            Get started
                        </a>
                        <a href="#how" class="rounded-lg border px-6 py-3 font-medium text-gray-700 hover:bg-gray-50">
                            See how it works
                        </a>
                    </div>
                </div>
            </section>

            <!-- Trust strip -->
            <section class="border-y bg-white">
                <div class="mx-auto grid max-w-6xl gap-4 px-6 py-6 text-center text-sm text-gray-600 sm:grid-cols-3">
                    <div v-if="reviewStats.count > 0">
                        <span class="text-amber-500">★</span>
                        <span class="font-semibold text-gray-900">{{ reviewStats.average }}</span>
                        from {{ reviewStats.count }} verified {{ reviewStats.count === 1 ? 'client' : 'clients' }}
                    </div>
                    <div v-else>Reviews come only from real, delivered work</div>
                    <div>No account or password needed</div>
                    <div>Updates and results sent to your email</div>
                </div>
            </section>

            <!-- Services -->
            <section id="services" class="mx-auto max-w-6xl scroll-mt-20 px-6 py-16">
                <h2 class="text-center text-3xl font-bold">What do you need help with?</h2>
                <p class="mt-3 text-center text-gray-600">Pick a service and enter your email.</p>

                <div class="mt-10 grid gap-6 md:grid-cols-2 lg:grid-cols-4">
                    <!-- Thesis consultation -->
                    <div class="flex flex-col rounded-2xl border bg-white p-6 shadow-sm">
                        <h3 class="text-xl font-semibold">Thesis Consultation</h3>
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
                    <div class="flex flex-col rounded-2xl border bg-white p-6 shadow-sm">
                        <h3 class="text-xl font-semibold">Tutoring</h3>
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
                    <div class="flex flex-col rounded-2xl border bg-white p-6 shadow-sm">
                        <h3 class="text-xl font-semibold">Online Portfolio</h3>
                        <p class="mt-2 text-sm text-gray-600">
                            Pick a template, add your details, and publish your own portfolio page.
                        </p>
                        <form class="mt-4 flex flex-1 flex-col space-y-3" @submit.prevent="submitPortfolio">
                            <input
                                v-model="portfolioForm.email"
                                type="email"
                                required
                                placeholder="you@example.com"
                                class="w-full rounded-lg border-gray-300 text-sm"
                            />
                            <p v-if="portfolioForm.errors.email" class="text-xs text-red-600">{{ portfolioForm.errors.email }}</p>
                            <p class="text-xs text-gray-500">You will create a free account next.</p>
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
                    <div class="flex flex-col rounded-2xl border bg-white p-6 shadow-sm">
                        <h3 class="text-xl font-semibold">Resume Maker</h3>
                        <p class="mt-2 flex-1 text-sm text-gray-600">
                            Choose a resume template, fill up your details, and get a ready-to-send resume.
                        </p>
                        <button
                            type="button"
                            disabled
                            class="mt-5 w-full cursor-not-allowed rounded-lg bg-gray-200 px-4 py-2 text-sm font-medium text-gray-500"
                        >
                            Coming soon
                        </button>
                    </div>
                </div>
            </section>

            <!-- How it works -->
            <section id="how" class="scroll-mt-20 bg-gray-50">
                <div class="mx-auto max-w-5xl px-6 py-16">
                    <h2 class="text-center text-3xl font-bold">How it works</h2>
                    <div class="mt-10 grid gap-8 sm:grid-cols-3">
                        <div v-for="s in steps" :key="s.n" class="text-center">
                            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-blue-600 text-lg font-bold text-white">
                                {{ s.n }}
                            </div>
                            <h3 class="mt-4 font-semibold">{{ s.title }}</h3>
                            <p class="mt-2 text-sm text-gray-600">{{ s.text }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Templates -->
            <section id="templates" class="mx-auto max-w-6xl scroll-mt-20 px-6 py-16">
                <h2 class="text-center text-3xl font-bold">Templates</h2>

                <h3 class="mt-10 text-center text-lg font-semibold">Portfolio templates</h3>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div
                        v-for="t in portfolioTemplates"
                        :key="t.key"
                        class="overflow-hidden rounded-2xl border bg-white shadow-sm"
                    >
                        <div :class="[t.swatch, 'h-28']"></div>
                        <div class="p-4">
                            <h4 class="font-medium">{{ t.name }}</h4>
                            <p class="mt-1 text-xs text-gray-500">{{ t.desc }}</p>
                        </div>
                    </div>
                </div>

                <h3 class="mt-12 text-center text-lg font-semibold">Resume templates</h3>
                <div class="mt-5 grid gap-4 sm:grid-cols-3">
                    <div
                        v-for="t in resumeTemplates"
                        :key="t.key"
                        class="overflow-hidden rounded-2xl border bg-white shadow-sm"
                    >
                        <div :class="[t.swatch, 'h-28']"></div>
                        <div class="p-4">
                            <h4 class="font-medium">{{ t.name }}</h4>
                            <p class="mt-1 text-xs text-gray-500">{{ t.desc }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Reviews -->
            <section v-if="reviews.length" id="reviews" class="scroll-mt-20 bg-gray-50">
                <div class="mx-auto max-w-6xl px-6 py-16">
                    <h2 class="text-center text-3xl font-bold">Customer reviews</h2>
                    <p class="mt-2 text-center text-sm text-gray-600">
                        <span class="text-amber-500">★</span> {{ reviewStats.average }} from {{ reviewStats.count }} verified
                        {{ reviewStats.count === 1 ? 'client' : 'clients' }}
                    </p>
                    <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                        <div v-for="(r, i) in reviews" :key="i" class="rounded-2xl border bg-white p-5 shadow-sm">
                            <p class="text-amber-500">{{ '★'.repeat(r.rating) }}{{ '☆'.repeat(5 - r.rating) }}</p>
                            <p class="mt-2 whitespace-pre-line text-sm text-gray-700">{{ r.comment }}</p>
                            <div class="mt-4 flex items-center justify-between text-xs">
                                <span class="font-medium">{{ r.name }}</span>
                                <span class="rounded-full bg-emerald-50 px-2 py-0.5 text-emerald-700">Verified client</span>
                            </div>
                            <p class="mt-1 text-xs text-gray-400">{{ r.service }}</p>
                        </div>
                    </div>
                </div>
            </section>

            <!-- Final call to action -->
            <section class="bg-gray-900">
                <div class="mx-auto max-w-3xl px-6 py-16 text-center text-white">
                    <h2 class="text-3xl font-bold">Ready to get started?</h2>
                    <p class="mt-3 text-gray-300">Pick a service, enter your email, and we will take it from there.</p>
                    <a href="#services" class="mt-6 inline-block rounded-lg bg-white px-6 py-3 font-medium text-gray-900 hover:bg-gray-100">
                        Choose a service
                    </a>
                </div>
            </section>
        </main>

        <!-- Footer -->
        <footer class="border-t bg-white">
            <div class="mx-auto flex max-w-6xl flex-col items-center justify-between gap-3 px-6 py-8 text-sm text-gray-500 sm:flex-row">
                <span>© {{ new Date().getFullYear() }} {{ siteName }}</span>
                <a :href="`mailto:${contactEmail}`" class="hover:text-gray-900">Contact: {{ contactEmail }}</a>
            </div>
        </footer>
    </div>
</template>
