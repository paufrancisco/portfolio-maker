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

const { skills, skillGroups, experience, projects, certifications, education, initial, links, nav, tags } =
    usePortfolioData(() => props.data);
</script>

<template>
    <div class="min-h-screen" :style="{ background: t.background, color: t.primary, fontFamily: t.font }">
        <header class="mx-auto max-w-4xl px-6 pb-10 pt-16 text-center">
            <img v-if="data.photo_url" :src="data.photo_url" :alt="data.name" class="mx-auto h-32 w-32 rounded-full object-cover" :style="{ border: `1px solid ${t.accent}` }" />
            <h1 class="mt-6 text-5xl font-bold tracking-tight sm:text-6xl">{{ data.name }}</h1>
            <p v-if="data.headline" class="mt-3 text-xl italic" :style="{ color: t.accent }">{{ data.headline }}</p>
            <p v-if="data.intro" class="mx-auto mt-5 max-w-xl text-lg opacity-80">{{ data.intro }}</p>
            <nav class="mt-8 flex flex-wrap justify-center gap-x-6 gap-y-1 border-y py-3 text-xs uppercase tracking-widest" :style="{ borderColor: line }">
                <a v-for="[label, href] in nav" :key="href" :href="href" class="hover:underline" :style="{ color: t.accent }">{{ label }}</a>
            </nav>
        </header>

        <main class="mx-auto max-w-4xl space-y-14 px-6 pb-24">
            <section v-if="data.about" id="about" class="grid scroll-mt-8 gap-4 md:grid-cols-[180px_1fr]">
                <h2 class="text-xs font-bold uppercase tracking-widest" :style="{ color: t.accent }">About</h2>
                <p class="whitespace-pre-line text-lg leading-relaxed opacity-90">{{ data.about }}</p>
            </section>

            <section v-if="skills.length" id="skills" class="grid scroll-mt-8 gap-4 border-t pt-8 md:grid-cols-[180px_1fr]" :style="{ borderColor: line }">
                <h2 class="text-xs font-bold uppercase tracking-widest" :style="{ color: t.accent }">Skills</h2>
                <div v-if="skillGroups.length" class="space-y-2">
                    <p v-for="g in skillGroups" :key="g.name" class="text-lg"><span class="italic" :style="{ color: t.accent }">{{ g.name }}:</span> {{ g.items.join(', ') }}</p>
                </div>
                <p v-if="!skillGroups.length" class="text-lg">{{ skills.join(', ') }}</p>
            </section>

            <section v-if="experience.length" id="work" class="grid scroll-mt-8 gap-4 border-t pt-8 md:grid-cols-[180px_1fr]" :style="{ borderColor: line }">
                <h2 class="text-xs font-bold uppercase tracking-widest" :style="{ color: t.accent }">Experience</h2>
                <div class="space-y-6">
                    <article v-for="(job, i) in experience" :key="i">
                        <h3 class="text-xl font-bold">{{ job.position }}</h3>
                        <p class="italic" :style="{ color: t.accent }">{{ job.company }}<span v-if="job.type"> · {{ job.type }}</span></p>
                        <p v-if="job.start || job.end" class="text-xs uppercase tracking-wider opacity-60">{{ job.start }} – {{ job.end || 'Present' }}</p>
                        <p v-if="job.description" class="mt-2 whitespace-pre-line leading-relaxed opacity-90">{{ job.description }}</p>
                    </article>
                </div>
            </section>

            <section v-if="projects.length" id="projects" class="grid scroll-mt-8 gap-4 border-t pt-8 md:grid-cols-[180px_1fr]" :style="{ borderColor: line }">
                <h2 class="text-xs font-bold uppercase tracking-widest" :style="{ color: t.accent }">Projects</h2>
                <div class="space-y-8">
                    <article v-for="(p, i) in projects" :key="i">
                        <img v-if="p.image_url" :src="p.image_url" :alt="p.title" class="mb-3 h-52 w-full object-cover" />
                        <h3 class="text-xl font-bold">{{ p.title }}</h3>
                        <p v-if="p.role" class="italic opacity-60">{{ p.role }}</p>
                        <p v-if="p.description" class="mt-2 leading-relaxed opacity-90">{{ p.description }}</p>
                        <p v-if="tags(p.technologies).length" class="mt-2 text-xs uppercase tracking-wider" :style="{ color: t.accent }">{{ tags(p.technologies).join(' / ') }}</p>
                        <p class="mt-2 flex gap-5 text-sm">
                            <a v-if="p.repo_url" :href="p.repo_url" target="_blank" rel="noopener" class="underline" :style="{ color: t.accent }">Repository</a>
                            <a v-if="p.demo_url" :href="p.demo_url" target="_blank" rel="noopener" class="underline" :style="{ color: t.accent }">Live demo</a>
                        </p>
                    </article>
                </div>
            </section>

            <section v-if="certifications.length" id="certs" class="grid scroll-mt-8 gap-4 border-t pt-8 md:grid-cols-[180px_1fr]" :style="{ borderColor: line }">
                <h2 class="text-xs font-bold uppercase tracking-widest" :style="{ color: t.accent }">Certifications</h2>
                <div class="space-y-4">
                    <article v-for="(c, i) in certifications" :key="i">
                        <h3 class="font-bold">{{ c.title }}</h3>
                        <p class="italic opacity-70">{{ c.issuer }}<span v-if="c.date"> · {{ c.date }}</span></p>
                        <p v-if="c.description" class="mt-1 opacity-90">{{ c.description }}</p>
                    </article>
                </div>
            </section>

            <section v-if="education.length" id="education" class="grid scroll-mt-8 gap-4 border-t pt-8 md:grid-cols-[180px_1fr]" :style="{ borderColor: line }">
                <h2 class="text-xs font-bold uppercase tracking-widest" :style="{ color: t.accent }">Education</h2>
                <div class="space-y-4">
                    <article v-for="(e, i) in education" :key="i">
                        <h3 class="font-bold">{{ e.school }}</h3>
                        <p class="italic opacity-70">{{ e.degree }}<span v-if="e.year"> · {{ e.year }}</span></p>
                        <p v-if="e.achievements" class="mt-1 opacity-90">{{ e.achievements }}</p>
                    </article>
                </div>
            </section>

            <section id="contact" class="scroll-mt-8 border-t pt-8 text-center" :style="{ borderColor: line }">
                <h2 class="text-3xl font-bold">Get in touch</h2>
                <div class="mt-5 flex flex-wrap justify-center gap-x-6 gap-y-2">
                    <a v-if="data.email" :href="`mailto:${data.email}`" class="underline" :style="{ color: t.accent }">{{ data.email }}</a>
                    <a v-for="[label, url] in links" :key="label" :href="url" target="_blank" rel="noopener" class="underline" :style="{ color: t.accent }">{{ label }}</a>
                    <a v-if="data.resume_url" :href="data.resume_url" target="_blank" rel="noopener" class="underline" :style="{ color: t.accent }">Resume</a>
                </div>
                <p v-if="data.phone" class="mt-3 text-sm opacity-60">{{ data.phone }}</p>
            </section>
        </main>
    </div>
</template>
