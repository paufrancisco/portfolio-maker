<script setup>
import { computed, ref } from 'vue';
import { usePortfolioData } from './usePortfolioData';

const props = defineProps({ data: Object, theme: Object });

const t = computed(() => ({
    primary: '#be185d',
    accent: '#f472b6',
    background: '#fff1f2',
    font: 'Poppins, system-ui, sans-serif',
    ...(props.theme ?? {}),
}));

const { skills, experience, projects, certifications, education, initial, links, nav, tags } =
    usePortfolioData(() => props.data);

const serif = "Georgia, 'Times New Roman', serif";
const ink = '#4a2433';
const soft = computed(() => `color-mix(in srgb, ${t.value.accent} 18%, white)`);
const edge = computed(() => `color-mix(in srgb, ${t.value.accent} 40%, white)`);

const showAll = ref(false);
const shownProjects = computed(() => (showAll.value ? projects.value : projects.value.slice(0, 4)));
</script>

<template>
    <div class="min-h-screen" :style="{ background: t.background, color: ink, fontFamily: t.font }">
        <!-- Nav -->
        <nav class="px-6 py-5">
            <div class="mx-auto flex max-w-5xl flex-wrap items-center justify-center gap-x-6 gap-y-2 text-sm">
                <a v-for="[label, href] in nav" :key="href" :href="href" class="tracking-wide hover:underline" :style="{ color: t.primary }">
                    {{ label }}
                </a>
            </div>
        </nav>

        <!-- Hero -->
        <header class="mx-auto max-w-3xl px-6 pb-16 pt-10 text-center">
            <img
                v-if="data.photo_url"
                :src="data.photo_url"
                :alt="data.name"
                class="mx-auto h-40 w-40 rounded-full object-cover shadow-lg"
                :style="{ border: `6px solid ${edge}` }"
            />
            <div
                v-else
                class="mx-auto flex h-40 w-40 items-center justify-center rounded-full text-6xl shadow-lg"
                :style="{ background: soft, color: t.primary, border: `6px solid ${edge}`, fontFamily: serif }"
            >
                {{ initial }}
            </div>

            <p class="mt-8 text-sm italic" :style="{ color: t.primary, fontFamily: serif }">Hello, I'm</p>
            <h1 class="mt-1 text-5xl" :style="{ color: t.primary, fontFamily: serif }">{{ data.name }}</h1>
            <p v-if="data.headline" class="mt-3 text-lg tracking-wide">{{ data.headline }}</p>
            <p v-if="data.intro" class="mx-auto mt-5 max-w-xl leading-relaxed opacity-80">{{ data.intro }}</p>

            <div class="mt-8 flex flex-wrap justify-center gap-3">
                <a v-if="data.email" :href="`mailto:${data.email}`" class="rounded-full px-6 py-2.5 text-sm font-medium shadow" :style="{ background: t.primary, color: '#fff' }">
                    Contact Me
                </a>
                <a
                    v-if="data.resume_url"
                    :href="data.resume_url"
                    target="_blank"
                    rel="noopener"
                    class="rounded-full border px-6 py-2.5 text-sm font-medium"
                    :style="{ borderColor: t.primary, color: t.primary }"
                >
                    Download Resume
                </a>
            </div>
        </header>

        <main class="mx-auto max-w-5xl space-y-20 px-6 pb-24">
            <!-- About -->
            <section v-if="data.about" id="about" class="scroll-mt-6 text-center">
                <h2 class="text-3xl" :style="{ color: t.primary, fontFamily: serif }">About Me</h2>
                <div class="mx-auto my-4 h-px w-16" :style="{ background: t.accent }"></div>
                <p class="mx-auto max-w-2xl whitespace-pre-line leading-loose">{{ data.about }}</p>
            </section>

            <!-- Skills -->
            <section v-if="skills.length" id="skills" class="scroll-mt-6 text-center">
                <h2 class="text-3xl" :style="{ color: t.primary, fontFamily: serif }">Skills</h2>
                <div class="mx-auto my-4 h-px w-16" :style="{ background: t.accent }"></div>
                <div class="flex flex-wrap justify-center gap-2">
                    <span v-for="s in skills" :key="s" class="rounded-full bg-white px-4 py-1.5 text-sm shadow-sm" :style="{ border: `1px solid ${edge}`, color: t.primary }">
                        {{ s }}
                    </span>
                </div>
            </section>

            <!-- Experience -->
            <section v-if="experience.length" id="work" class="scroll-mt-6">
                <h2 class="text-center text-3xl" :style="{ color: t.primary, fontFamily: serif }">Work Experience</h2>
                <div class="mx-auto my-4 h-px w-16" :style="{ background: t.accent }"></div>
                <div class="mx-auto max-w-3xl space-y-5">
                    <article v-for="(job, i) in experience" :key="i" class="rounded-2xl bg-white p-6 shadow-sm" :style="{ border: `1px solid ${edge}` }">
                        <div class="flex flex-wrap items-baseline justify-between gap-2">
                            <h3 class="text-xl" :style="{ fontFamily: serif }">{{ job.position }}</h3>
                            <span v-if="job.start || job.end" class="text-xs opacity-60">{{ job.start }} – {{ job.end || 'Present' }}</span>
                        </div>
                        <p class="text-sm" :style="{ color: t.primary }">{{ job.company }}<span v-if="job.type"> · {{ job.type }}</span></p>
                        <p v-if="job.description" class="mt-3 whitespace-pre-line text-sm leading-relaxed opacity-90">{{ job.description }}</p>
                    </article>
                </div>
            </section>

            <!-- Projects -->
            <section v-if="projects.length" id="projects" class="scroll-mt-6">
                <h2 class="text-center text-3xl" :style="{ color: t.primary, fontFamily: serif }">Some Things I've Built</h2>
                <div class="mx-auto my-4 h-px w-16" :style="{ background: t.accent }"></div>
                <div class="grid gap-6 sm:grid-cols-2">
                    <article v-for="(p, i) in shownProjects" :key="i" class="overflow-hidden rounded-2xl bg-white shadow-sm" :style="{ border: `1px solid ${edge}` }">
                        <img v-if="p.image_url" :src="p.image_url" :alt="p.title" class="h-44 w-full object-cover" />
                        <div class="p-6">
                            <h3 class="text-xl" :style="{ fontFamily: serif }">{{ p.title }}</h3>
                            <p v-if="p.role" class="text-xs opacity-60">{{ p.role }}</p>
                            <p v-if="p.description" class="mt-2 text-sm leading-relaxed opacity-90">{{ p.description }}</p>
                            <div v-if="tags(p.technologies).length" class="mt-3 flex flex-wrap gap-1.5">
                                <span v-for="x in tags(p.technologies)" :key="x" class="rounded-full px-2.5 py-0.5 text-xs" :style="{ background: soft, color: t.primary }">{{ x }}</span>
                            </div>
                            <div class="mt-4 flex gap-4 text-sm">
                                <a v-if="p.repo_url" :href="p.repo_url" target="_blank" rel="noopener" class="underline" :style="{ color: t.primary }">Repository</a>
                                <a v-if="p.demo_url" :href="p.demo_url" target="_blank" rel="noopener" class="underline" :style="{ color: t.primary }">Live Demo</a>
                            </div>
                        </div>
                    </article>
                </div>
                <div v-if="projects.length > 4" class="mt-6 text-center">
                    <button type="button" class="rounded-full border px-6 py-2 text-sm" :style="{ borderColor: t.primary, color: t.primary }" @click="showAll = !showAll">
                        {{ showAll ? 'Show Less' : 'Show More' }}
                    </button>
                </div>
            </section>

            <!-- Certifications -->
            <section v-if="certifications.length" id="certs" class="scroll-mt-6">
                <h2 class="text-center text-3xl" :style="{ color: t.primary, fontFamily: serif }">Certifications &amp; Awards</h2>
                <div class="mx-auto my-4 h-px w-16" :style="{ background: t.accent }"></div>
                <div class="grid gap-5 sm:grid-cols-2">
                    <article v-for="(c, i) in certifications" :key="i" class="rounded-2xl bg-white p-6 shadow-sm" :style="{ border: `1px solid ${edge}` }">
                        <h3 class="text-lg" :style="{ fontFamily: serif }">{{ c.title }}</h3>
                        <p class="text-sm" :style="{ color: t.primary }">{{ c.issuer }}</p>
                        <p v-if="c.date" class="text-xs opacity-60">{{ c.date }}</p>
                        <p v-if="c.description" class="mt-2 text-sm opacity-90">{{ c.description }}</p>
                    </article>
                </div>
            </section>

            <!-- Education -->
            <section v-if="education.length" id="education" class="scroll-mt-6">
                <h2 class="text-center text-3xl" :style="{ color: t.primary, fontFamily: serif }">Education</h2>
                <div class="mx-auto my-4 h-px w-16" :style="{ background: t.accent }"></div>
                <div class="mx-auto max-w-3xl space-y-4">
                    <article v-for="(e, i) in education" :key="i" class="rounded-2xl bg-white p-6 shadow-sm" :style="{ border: `1px solid ${edge}` }">
                        <h3 class="text-lg" :style="{ fontFamily: serif }">{{ e.school }}</h3>
                        <p class="text-sm" :style="{ color: t.primary }">{{ e.degree }}</p>
                        <p v-if="e.year" class="text-xs opacity-60">{{ e.year }}</p>
                        <p v-if="e.achievements" class="mt-2 text-sm opacity-90">{{ e.achievements }}</p>
                    </article>
                </div>
            </section>

            <!-- Contact -->
            <section id="contact" class="scroll-mt-6 rounded-3xl p-10 text-center" :style="{ background: soft }">
                <h2 class="text-3xl" :style="{ color: t.primary, fontFamily: serif }">Let's get in touch</h2>
                <p class="mt-2 opacity-80">I'd love to hear from you.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a v-if="data.email" :href="`mailto:${data.email}`" class="rounded-full px-6 py-2.5 text-sm font-medium" :style="{ background: t.primary, color: '#fff' }">Email Me</a>
                    <a v-for="[label, url] in links" :key="label" :href="url" target="_blank" rel="noopener" class="rounded-full border bg-white px-6 py-2.5 text-sm font-medium" :style="{ borderColor: t.primary, color: t.primary }">
                        {{ label }}
                    </a>
                </div>
                <p v-if="data.phone" class="mt-4 text-sm opacity-70">{{ data.phone }}</p>
            </section>
        </main>
    </div>
</template>
