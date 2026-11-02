<script setup>
import { computed } from 'vue';
import { usePortfolioData } from './usePortfolioData';

const props = defineProps({ data: Object, theme: Object });

const t = computed(() => ({
    background: '#ffffff',
    primary: '#111827',
    accent: '#2563eb',
    font: 'inherit',
    ...(props.theme ?? {}),
}));
const soft = computed(() => `color-mix(in srgb, ${t.value.accent} 14%, transparent)`);
const line = computed(() => `color-mix(in srgb, ${t.value.accent} 30%, transparent)`);

const { skills, experience, projects, certifications, education, initial, links, nav, tags } =
    usePortfolioData(() => props.data);
</script>

<template>
    <div class="min-h-screen" :style="{ background: t.background, color: t.primary, fontFamily: t.font }">
        <nav class="sticky top-0 z-40 backdrop-blur" :style="{ background: t.background + 'ee' }">
            <div class="mx-auto flex max-w-6xl items-center justify-between gap-4 px-6 py-4">
                <span class="text-lg font-extrabold">{{ data.name }}</span>
                <div class="flex flex-wrap justify-end gap-x-5 gap-y-1 text-sm font-medium">
                    <a v-for="[label, href] in nav" :key="href" :href="href" class="hover:underline" :style="{ color: t.accent }">{{ label }}</a>
                </div>
            </div>
        </nav>

        <header class="mx-auto grid max-w-6xl items-center gap-10 px-6 py-16 md:grid-cols-2">
            <div>
                <p v-if="data.headline" class="text-sm font-bold uppercase tracking-widest" :style="{ color: t.accent }">{{ data.headline }}</p>
                <h1 class="mt-3 text-5xl font-extrabold leading-tight sm:text-7xl">{{ data.name }}</h1>
                <p v-if="data.intro" class="mt-5 max-w-md text-lg opacity-80">{{ data.intro }}</p>
                <div class="mt-8 flex flex-wrap gap-3">
                    <a v-if="data.email" :href="`mailto:${data.email}`" class="rounded-full px-6 py-3 text-sm font-bold" :style="{ background: t.accent, color: t.background }">Say hello</a>
                    <a v-if="data.resume_url" :href="data.resume_url" target="_blank" rel="noopener" class="rounded-full border-2 px-6 py-3 text-sm font-bold" :style="{ borderColor: t.accent, color: t.accent }">Resume</a>
                </div>
            </div>
            <img v-if="data.photo_url" :src="data.photo_url" :alt="data.name" class="aspect-square w-full rounded-[2rem] object-cover" :style="{ boxShadow: `14px 14px 0 ${t.accent}` }" />
            <div v-else class="flex aspect-square w-full items-center justify-center rounded-[2rem] text-9xl font-extrabold" :style="{ background: t.accent, color: t.background }">{{ initial }}</div>
        </header>

        <main class="mx-auto max-w-6xl space-y-24 px-6 pb-24">
            <section v-if="data.about" id="about" class="scroll-mt-20">
                <h2 class="mb-4 text-3xl font-extrabold">About me</h2>
                <p class="max-w-3xl whitespace-pre-line text-lg leading-relaxed opacity-90">{{ data.about }}</p>
            </section>

            <section v-if="skills.length" id="skills" class="scroll-mt-20">
                <h2 class="mb-4 text-3xl font-extrabold">Skills</h2>
                <div class="flex flex-wrap gap-3">
                    <span v-for="s in skills" :key="s" class="rounded-full px-5 py-2 text-sm font-semibold" :style="{ background: soft, color: t.accent }">{{ s }}</span>
                </div>
            </section>

            <section v-if="projects.length" id="projects" class="scroll-mt-20">
                <h2 class="mb-6 text-3xl font-extrabold">Selected work</h2>
                <div class="grid gap-6 sm:grid-cols-2">
                    <article v-for="(p, i) in projects" :key="i" class="overflow-hidden rounded-3xl border-2" :style="{ borderColor: line }">
                        <img v-if="p.image_url" :src="p.image_url" :alt="p.title" class="h-56 w-full object-cover" />
                        <div v-else class="flex h-56 items-center justify-center text-7xl font-extrabold" :style="{ background: soft, color: t.accent }">{{ (p.title ?? '?').charAt(0) }}</div>
                        <div class="p-6">
                            <h3 class="text-xl font-bold">{{ p.title }}</h3>
                            <p v-if="p.role" class="text-xs opacity-60">{{ p.role }}</p>
                            <p v-if="p.description" class="mt-2 text-sm leading-relaxed opacity-90">{{ p.description }}</p>
                            <div v-if="tags(p.technologies).length" class="mt-3 flex flex-wrap gap-2">
                                <span v-for="x in tags(p.technologies)" :key="x" class="rounded-full px-3 py-1 text-xs" :style="{ background: soft, color: t.accent }">{{ x }}</span>
                            </div>
                            <div class="mt-4 flex gap-4 text-sm font-semibold">
                                <a v-if="p.repo_url" :href="p.repo_url" target="_blank" rel="noopener" :style="{ color: t.accent }">Code</a>
                                <a v-if="p.demo_url" :href="p.demo_url" target="_blank" rel="noopener" :style="{ color: t.accent }">Live demo</a>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <section v-if="experience.length" id="work" class="scroll-mt-20">
                <h2 class="mb-6 text-3xl font-extrabold">Experience</h2>
                <div class="space-y-4">
                    <article v-for="(job, i) in experience" :key="i" class="rounded-3xl p-6" :style="{ background: soft }">
                        <h3 class="text-lg font-bold">{{ job.position }}</h3>
                        <p class="text-sm font-semibold" :style="{ color: t.accent }">{{ job.company }}<span v-if="job.type"> · {{ job.type }}</span></p>
                        <p v-if="job.start || job.end" class="mt-1 text-xs opacity-60">{{ job.start }} – {{ job.end || 'Present' }}</p>
                        <p v-if="job.description" class="mt-3 whitespace-pre-line text-sm leading-relaxed opacity-90">{{ job.description }}</p>
                    </article>
                </div>
            </section>

            <section v-if="certifications.length" id="certs" class="scroll-mt-20">
                <h2 class="mb-6 text-3xl font-extrabold">Certifications</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <article v-for="(c, i) in certifications" :key="i" class="rounded-3xl border-2 p-5" :style="{ borderColor: line }">
                        <h3 class="font-bold">{{ c.title }}</h3>
                        <p class="text-sm" :style="{ color: t.accent }">{{ c.issuer }}</p>
                        <p v-if="c.date" class="text-xs opacity-60">{{ c.date }}</p>
                        <p v-if="c.description" class="mt-2 text-sm opacity-90">{{ c.description }}</p>
                    </article>
                </div>
            </section>

            <section v-if="education.length" id="education" class="scroll-mt-20">
                <h2 class="mb-6 text-3xl font-extrabold">Education</h2>
                <div class="space-y-3">
                    <article v-for="(e, i) in education" :key="i" class="rounded-3xl border-2 p-5" :style="{ borderColor: line }">
                        <h3 class="font-bold">{{ e.school }}</h3>
                        <p class="text-sm" :style="{ color: t.accent }">{{ e.degree }}</p>
                        <p v-if="e.year" class="text-xs opacity-60">{{ e.year }}</p>
                        <p v-if="e.achievements" class="mt-2 text-sm opacity-90">{{ e.achievements }}</p>
                    </article>
                </div>
            </section>

            <section id="contact" class="scroll-mt-20 rounded-[2rem] p-10 text-center" :style="{ background: t.accent, color: t.background }">
                <h2 class="text-3xl font-extrabold">Let's make something</h2>
                <p class="mt-2 opacity-90">Open to new projects and opportunities.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3 text-sm font-bold">
                    <a v-if="data.email" :href="`mailto:${data.email}`" class="rounded-full px-6 py-3" :style="{ background: t.background, color: t.accent }">Email me</a>
                    <a v-for="[label, url] in links" :key="label" :href="url" target="_blank" rel="noopener" class="rounded-full border-2 px-6 py-3" :style="{ borderColor: t.background }">{{ label }}</a>
                </div>
                <p v-if="data.phone" class="mt-4 text-sm opacity-80">{{ data.phone }}</p>
            </section>
        </main>
    </div>
</template>
