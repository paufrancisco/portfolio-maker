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
        <div class="mx-auto max-w-2xl px-6 py-16 text-sm leading-relaxed">
            <header>
                <img v-if="data.photo_url" :src="data.photo_url" :alt="data.name" class="h-20 w-20 rounded-full object-cover" />
                <h1 class="mt-6 text-3xl font-light tracking-tight">{{ data.name }}</h1>
                <p v-if="data.headline" class="mt-1" :style="{ color: t.accent }">{{ data.headline }}</p>
                <p v-if="data.intro" class="mt-4 opacity-80">{{ data.intro }}</p>
                <nav class="mt-5 flex flex-wrap gap-x-4 gap-y-1 text-xs">
                    <a v-for="[label, href] in nav" :key="href" :href="href" class="underline-offset-4 hover:underline" :style="{ color: t.accent }">{{ label }}</a>
                </nav>
            </header>

            <section v-if="data.about" id="about" class="mt-12 scroll-mt-8 border-t pt-6" :style="{ borderColor: line }">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest" :style="{ color: t.accent }">About</h2>
                <p class="whitespace-pre-line opacity-90">{{ data.about }}</p>
            </section>

            <section v-if="skills.length" id="skills" class="mt-10 scroll-mt-8 border-t pt-6" :style="{ borderColor: line }">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest" :style="{ color: t.accent }">Skills</h2>
                <p class="opacity-90">{{ skills.join(' · ') }}</p>
            </section>

            <section v-if="experience.length" id="work" class="mt-10 scroll-mt-8 border-t pt-6" :style="{ borderColor: line }">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest" :style="{ color: t.accent }">Experience</h2>
                <div class="space-y-5">
                    <article v-for="(job, i) in experience" :key="i">
                        <h3 class="font-medium">{{ job.position }}, {{ job.company }}</h3>
                        <p v-if="job.start || job.end" class="text-xs opacity-60">{{ job.start }} – {{ job.end || 'Present' }}<span v-if="job.type"> · {{ job.type }}</span></p>
                        <p v-if="job.description" class="mt-1 whitespace-pre-line opacity-90">{{ job.description }}</p>
                    </article>
                </div>
            </section>

            <section v-if="projects.length" id="projects" class="mt-10 scroll-mt-8 border-t pt-6" :style="{ borderColor: line }">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest" :style="{ color: t.accent }">Projects</h2>
                <div class="space-y-5">
                    <article v-for="(p, i) in projects" :key="i">
                        <img v-if="p.image_url" :src="p.image_url" :alt="p.title" class="mb-2 h-40 w-full rounded object-cover" />
                        <h3 class="font-medium">{{ p.title }}<span v-if="p.role" class="text-xs font-normal opacity-60"> · {{ p.role }}</span></h3>
                        <p v-if="p.description" class="mt-1 opacity-90">{{ p.description }}</p>
                        <p v-if="tags(p.technologies).length" class="mt-1 text-xs opacity-60">{{ tags(p.technologies).join(' · ') }}</p>
                        <p class="mt-1 flex gap-4 text-xs">
                            <a v-if="p.repo_url" :href="p.repo_url" target="_blank" rel="noopener" :style="{ color: t.accent }">Code</a>
                            <a v-if="p.demo_url" :href="p.demo_url" target="_blank" rel="noopener" :style="{ color: t.accent }">Demo</a>
                        </p>
                    </article>
                </div>
            </section>

            <section v-if="certifications.length" id="certs" class="mt-10 scroll-mt-8 border-t pt-6" :style="{ borderColor: line }">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest" :style="{ color: t.accent }">Certifications</h2>
                <div class="space-y-3">
                    <article v-for="(c, i) in certifications" :key="i">
                        <h3 class="font-medium">{{ c.title }}</h3>
                        <p class="text-xs opacity-60">{{ c.issuer }}<span v-if="c.date"> · {{ c.date }}</span></p>
                        <p v-if="c.description" class="mt-1 opacity-90">{{ c.description }}</p>
                    </article>
                </div>
            </section>

            <section v-if="education.length" id="education" class="mt-10 scroll-mt-8 border-t pt-6" :style="{ borderColor: line }">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest" :style="{ color: t.accent }">Education</h2>
                <div class="space-y-3">
                    <article v-for="(e, i) in education" :key="i">
                        <h3 class="font-medium">{{ e.school }}</h3>
                        <p class="text-xs opacity-60">{{ e.degree }}<span v-if="e.year"> · {{ e.year }}</span></p>
                        <p v-if="e.achievements" class="mt-1 opacity-90">{{ e.achievements }}</p>
                    </article>
                </div>
            </section>

            <section id="contact" class="mt-10 scroll-mt-8 border-t pt-6" :style="{ borderColor: line }">
                <h2 class="mb-3 text-xs font-semibold uppercase tracking-widest" :style="{ color: t.accent }">Contact</h2>
                <p class="flex flex-wrap gap-x-4 gap-y-1">
                    <a v-if="data.email" :href="`mailto:${data.email}`" :style="{ color: t.accent }">{{ data.email }}</a>
                    <a v-for="[label, url] in links" :key="label" :href="url" target="_blank" rel="noopener" :style="{ color: t.accent }">{{ label }}</a>
                </p>
                <p v-if="data.phone" class="mt-1 text-xs opacity-60">{{ data.phone }}</p>
                <a v-if="data.resume_url" :href="data.resume_url" target="_blank" rel="noopener" class="mt-3 inline-block underline" :style="{ color: t.accent }">Download resume</a>
            </section>
        </div>
    </div>
</template>
