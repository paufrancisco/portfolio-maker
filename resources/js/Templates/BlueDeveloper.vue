<script setup>
import { computed, ref } from 'vue';
import { usePortfolioData } from './usePortfolioData';

const props = defineProps({ data: Object, theme: Object });

// This template is always dark: only the accent color comes from the theme.
const accent = computed(() => props.theme?.accent || '#38bdf8');
const bg = '#0b1220';
const panel = '#111a2e';
const text = '#e2e8f0';
const muted = '#94a3b8';
const mono = "ui-monospace, SFMono-Regular, Menlo, Consolas, monospace";

const { skills, skillGroups, experience, projects, certifications, education, initial, links, nav, tags } =
    usePortfolioData(() => props.data);

const line = computed(() => `color-mix(in srgb, ${accent.value} 28%, transparent)`);
const glow = computed(() => `color-mix(in srgb, ${accent.value} 14%, transparent)`);

const showAll = ref(false);
const shownProjects = computed(() => (showAll.value ? projects.value : projects.value.slice(0, 4)));
</script>

<template>
    <div class="min-h-screen" :style="{ background: bg, color: text, fontFamily: mono }">
        <!-- Nav -->
        <nav class="sticky top-0 z-40 border-b backdrop-blur" :style="{ borderColor: line, background: bg + 'e6' }">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-3 text-sm">
                <span :style="{ color: accent }">&lt;{{ (data.name ?? '').split(' ')[0] }} /&gt;</span>
                <div class="flex flex-wrap justify-end gap-x-5 gap-y-1">
                    <a v-for="[label, href] in nav" :key="href" :href="href" class="hover:underline" :style="{ color: muted }">
                        {{ label.toLowerCase() }}
                    </a>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <header class="mx-auto flex max-w-5xl flex-col-reverse items-start gap-10 px-6 py-20 md:flex-row md:items-center md:justify-between">
            <div class="max-w-xl">
                <p class="text-sm" :style="{ color: accent }">$ whoami</p>
                <h1 class="mt-3 text-4xl font-bold sm:text-5xl">{{ data.name }}</h1>
                <p v-if="data.headline" class="mt-3 text-lg" :style="{ color: accent }">&gt; {{ data.headline }}<span class="animate-pulse">_</span></p>
                <p v-if="data.intro" class="mt-4 text-sm leading-relaxed" :style="{ color: muted }">{{ data.intro }}</p>

                <div class="mt-8 flex flex-wrap gap-3 text-sm">
                    <a v-if="data.email" :href="`mailto:${data.email}`" class="rounded px-5 py-2.5 font-semibold" :style="{ background: accent, color: bg }">./contact</a>
                    <a v-if="data.resume_url" :href="data.resume_url" target="_blank" rel="noopener" class="rounded border px-5 py-2.5" :style="{ borderColor: accent, color: accent }">
                        ./resume
                    </a>
                </div>
            </div>

            <img
                v-if="data.photo_url"
                :src="data.photo_url"
                :alt="data.name"
                class="h-40 w-40 rounded-lg object-cover md:h-56 md:w-56"
                :style="{ border: `2px solid ${accent}`, boxShadow: `0 0 40px ${glow}` }"
            />
            <div
                v-else
                class="flex h-40 w-40 items-center justify-center rounded-lg text-6xl font-bold md:h-56 md:w-56"
                :style="{ background: panel, color: accent, border: `2px solid ${accent}`, boxShadow: `0 0 40px ${glow}` }"
            >
                {{ initial }}
            </div>
        </header>

        <main class="mx-auto max-w-5xl space-y-20 px-6 pb-24">
            <!-- About -->
            <section v-if="data.about" id="about" class="scroll-mt-20">
                <h2 class="mb-4 text-xl font-bold" :style="{ color: accent }">// about</h2>
                <p class="max-w-3xl whitespace-pre-line text-sm leading-loose" :style="{ color: muted }">{{ data.about }}</p>
            </section>

            <!-- Skills -->
            <section v-if="skills.length" id="skills" class="scroll-mt-20">
                <h2 class="mb-4 text-xl font-bold" :style="{ color: accent }">// technical_profile</h2>
                <div v-if="skillGroups.length" class="space-y-5">
                    <div v-for="g in skillGroups" :key="g.name">
                        <h3 class="mb-2 text-xs" :style="{ color: muted }"># {{ g.name.toLowerCase() }}</h3>
                        <div class="flex flex-wrap gap-2">
                            <span v-for="s in g.items" :key="s" class="rounded border px-3 py-1 text-xs" :style="{ borderColor: line, background: glow, color: accent }">[ {{ s }} ]</span>
                        </div>
                    </div>
                </div>
                <div v-if="!skillGroups.length" class="flex flex-wrap gap-2">
                    <span v-for="s in skills" :key="s" class="rounded border px-3 py-1 text-xs" :style="{ borderColor: line, background: glow, color: accent }">
                        [ {{ s }} ]
                    </span>
                </div>
            </section>

            <!-- Experience -->
            <section v-if="experience.length" id="work" class="scroll-mt-20">
                <h2 class="mb-6 text-xl font-bold" :style="{ color: accent }">// experience</h2>
                <div class="space-y-4 border-l pl-6" :style="{ borderColor: line }">
                    <article v-for="(job, i) in experience" :key="i" class="relative rounded-lg border p-5" :style="{ borderColor: line, background: panel }">
                        <span class="absolute -left-[31px] top-6 h-2.5 w-2.5 rounded-full" :style="{ background: accent, boxShadow: `0 0 10px ${accent}` }"></span>
                        <h3 class="font-semibold">{{ job.position }}</h3>
                        <p class="text-sm" :style="{ color: accent }">@ {{ job.company }}<span v-if="job.type"> · {{ job.type }}</span></p>
                        <p v-if="job.start || job.end" class="mt-1 text-xs" :style="{ color: muted }">{{ job.start }} → {{ job.end || 'present' }}</p>
                        <p v-if="job.description" class="mt-3 whitespace-pre-line text-sm leading-relaxed" :style="{ color: muted }">{{ job.description }}</p>
                    </article>
                </div>
            </section>

            <!-- Projects -->
            <section v-if="projects.length" id="projects" class="scroll-mt-20">
                <h2 class="mb-6 text-xl font-bold" :style="{ color: accent }">// projects</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <article v-for="(p, i) in shownProjects" :key="i" class="overflow-hidden rounded-lg border" :style="{ borderColor: line, background: panel }">
                        <img v-if="p.image_url" :src="p.image_url" :alt="p.title" class="h-44 w-full object-cover" />
                        <div class="p-5">
                            <h3 class="font-semibold">{{ p.title }}</h3>
                            <p v-if="p.role" class="text-xs" :style="{ color: muted }">{{ p.role }}</p>
                            <p v-if="p.description" class="mt-2 text-sm leading-relaxed" :style="{ color: muted }">{{ p.description }}</p>
                            <div v-if="tags(p.technologies).length" class="mt-3 flex flex-wrap gap-1.5">
                                <span v-for="x in tags(p.technologies)" :key="x" class="text-xs" :style="{ color: accent }">#{{ x }}</span>
                            </div>
                            <div class="mt-4 flex gap-4 text-sm">
                                <a v-if="p.repo_url" :href="p.repo_url" target="_blank" rel="noopener" :style="{ color: accent }">[repo]</a>
                                <a v-if="p.demo_url" :href="p.demo_url" target="_blank" rel="noopener" :style="{ color: accent }">[live]</a>
                            </div>
                        </div>
                    </article>
                </div>
                <div v-if="projects.length > 4" class="mt-6">
                    <button type="button" class="rounded border px-5 py-2 text-sm" :style="{ borderColor: accent, color: accent }" @click="showAll = !showAll">
                        {{ showAll ? '--less' : '--more' }}
                    </button>
                </div>
            </section>

            <!-- Certifications -->
            <section v-if="certifications.length" id="certs" class="scroll-mt-20">
                <h2 class="mb-6 text-xl font-bold" :style="{ color: accent }">// certifications</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <article v-for="(c, i) in certifications" :key="i" class="rounded-lg border p-5" :style="{ borderColor: line, background: panel }">
                        <h3 class="font-semibold">{{ c.title }}</h3>
                        <p class="text-sm" :style="{ color: accent }">{{ c.issuer }}</p>
                        <p v-if="c.date" class="text-xs" :style="{ color: muted }">{{ c.date }}</p>
                        <p v-if="c.description" class="mt-2 text-sm" :style="{ color: muted }">{{ c.description }}</p>
                    </article>
                </div>
            </section>

            <!-- Education -->
            <section v-if="education.length" id="education" class="scroll-mt-20">
                <h2 class="mb-6 text-xl font-bold" :style="{ color: accent }">// education</h2>
                <div class="space-y-3">
                    <article v-for="(e, i) in education" :key="i" class="rounded-lg border p-5" :style="{ borderColor: line, background: panel }">
                        <h3 class="font-semibold">{{ e.school }}</h3>
                        <p class="text-sm" :style="{ color: accent }">{{ e.degree }}</p>
                        <p v-if="e.year" class="text-xs" :style="{ color: muted }">{{ e.year }}</p>
                        <p v-if="e.achievements" class="mt-2 text-sm" :style="{ color: muted }">{{ e.achievements }}</p>
                    </article>
                </div>
            </section>

            <!-- Contact -->
            <section id="contact" class="scroll-mt-20 rounded-lg border p-8 text-center" :style="{ borderColor: accent, background: panel, boxShadow: `0 0 40px ${glow}` }">
                <h2 class="text-xl font-bold" :style="{ color: accent }">// let's_connect</h2>
                <p class="mt-2 text-sm" :style="{ color: muted }">Open to opportunities and collaborations.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3 text-sm">
                    <a v-if="data.email" :href="`mailto:${data.email}`" class="rounded px-5 py-2.5 font-semibold" :style="{ background: accent, color: bg }">email_me()</a>
                    <a v-for="[label, url] in links" :key="label" :href="url" target="_blank" rel="noopener" class="rounded border px-5 py-2.5" :style="{ borderColor: accent, color: accent }">
                        {{ label.toLowerCase() }}
                    </a>
                </div>
                <p v-if="data.phone" class="mt-4 text-xs" :style="{ color: muted }">{{ data.phone }}</p>
            </section>
        </main>
    </div>
</template>
