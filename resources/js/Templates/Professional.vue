<script setup>
import { computed } from 'vue';

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

const list = (v) => (Array.isArray(v) ? v.filter(Boolean) : []);

const skills = computed(() => {
    const raw = Array.isArray(props.data.skills)
        ? props.data.skills
        : typeof props.data.skills === 'string'
          ? props.data.skills.split(',')
          : [];
    return raw
        .map((s) => (typeof s === 'object' && s ? s.name : String(s ?? '').trim()))
        .filter(Boolean);
});

const experience = computed(() => list(props.data.experience));
const projects = computed(() => list(props.data.projects));
const certifications = computed(() => list(props.data.certifications));
const education = computed(() => list(props.data.education));

const initial = computed(() => (props.data.name ?? '?').trim().charAt(0).toUpperCase());

const links = computed(
    () =>
        [
            ['LinkedIn', props.data.linkedin],
            ['GitHub', props.data.github],
            ['Website', props.data.website],
        ].filter(([, url]) => url),
);

const nav = computed(() =>
    [
        props.data.about && ['About', '#about'],
        skills.value.length && ['Skills', '#skills'],
        experience.value.length && ['Work', '#work'],
        projects.value.length && ['Projects', '#projects'],
        certifications.value.length && ['Certifications', '#certs'],
        education.value.length && ['Education', '#education'],
        ['Contact', '#contact'],
    ].filter(Boolean),
);
</script>

<template>
    <div class="min-h-screen" :style="{ background: t.background, color: t.primary, fontFamily: t.font }">
        <!-- Nav -->
        <nav class="sticky top-0 z-40 border-b backdrop-blur" :style="{ borderColor: line, background: t.background + 'ee' }">
            <div class="mx-auto flex max-w-5xl items-center justify-between gap-4 px-6 py-3">
                <span class="font-semibold">{{ data.name }}</span>
                <div class="flex flex-wrap justify-end gap-x-5 gap-y-1 text-sm">
                    <a v-for="[label, href] in nav" :key="href" :href="href" class="hover:underline" :style="{ color: t.accent }">
                        {{ label }}
                    </a>
                </div>
            </div>
        </nav>

        <!-- Hero -->
        <header class="mx-auto flex max-w-5xl flex-col-reverse items-start gap-10 px-6 py-20 md:flex-row md:items-center md:justify-between">
            <div class="max-w-xl">
                <p class="text-sm font-medium uppercase tracking-widest" :style="{ color: t.accent }">Hi there! I'm</p>
                <h1 class="mt-2 text-4xl font-bold sm:text-5xl">{{ data.name }}</h1>
                <p v-if="data.headline" class="mt-4 text-xl" :style="{ color: t.accent }">{{ data.headline }}</p>
                <p v-if="data.intro" class="mt-4 leading-relaxed opacity-80">{{ data.intro }}</p>

                <div class="mt-8 flex flex-wrap gap-3">
                    <a
                        v-if="data.email"
                        :href="`mailto:${data.email}`"
                        class="rounded-md px-5 py-2.5 text-sm font-medium"
                        :style="{ background: t.accent, color: t.background }"
                    >
                        Contact Me
                    </a>
                    <a
                        v-if="data.resume_url"
                        :href="data.resume_url"
                        target="_blank"
                        rel="noopener"
                        class="rounded-md border px-5 py-2.5 text-sm font-medium"
                        :style="{ borderColor: t.accent, color: t.accent }"
                    >
                        Download Resume
                    </a>
                </div>
            </div>

            <img
                v-if="data.photo_url"
                :src="data.photo_url"
                :alt="data.name"
                class="h-40 w-40 rounded-full object-cover md:h-56 md:w-56"
                :style="{ border: `4px solid ${t.accent}` }"
            />
            <div
                v-else
                class="flex h-40 w-40 items-center justify-center rounded-full text-6xl font-bold md:h-56 md:w-56"
                :style="{ background: soft, color: t.accent }"
            >
                {{ initial }}
            </div>
        </header>

        <main class="mx-auto max-w-5xl space-y-20 px-6 pb-24">
            <!-- About -->
            <section v-if="data.about" id="about" class="scroll-mt-20">
                <h2 class="mb-4 text-2xl font-bold">About Me</h2>
                <p class="max-w-3xl whitespace-pre-line leading-relaxed opacity-90">{{ data.about }}</p>
            </section>

            <!-- Skills -->
            <section v-if="skills.length" id="skills" class="scroll-mt-20">
                <h2 class="mb-4 text-2xl font-bold">Technical Profile</h2>
                <div class="flex flex-wrap gap-2">
                    <span v-for="skill in skills" :key="skill" class="rounded-full px-3 py-1 text-sm" :style="{ background: soft, color: t.accent }">
                        {{ skill }}
                    </span>
                </div>
            </section>

            <!-- Experience -->
            <section v-if="experience.length" id="work" class="scroll-mt-20">
                <h2 class="mb-6 text-2xl font-bold">Work Experience</h2>
                <div class="space-y-4 border-l-2 pl-6" :style="{ borderColor: line }">
                    <article v-for="(job, i) in experience" :key="i" class="relative rounded-lg border p-5" :style="{ borderColor: line }">
                        <span class="absolute -left-[33px] top-6 h-3 w-3 rounded-full" :style="{ background: t.accent }"></span>
                        <h3 class="font-semibold">{{ job.position ?? job.title }}</h3>
                        <p class="text-sm" :style="{ color: t.accent }">
                            {{ job.company }}<span v-if="job.type"> · {{ job.type }}</span>
                        </p>
                        <p v-if="job.start || job.end" class="mt-1 text-xs opacity-60">{{ job.start }} – {{ job.end || 'Present' }}</p>
                        <p v-if="job.description" class="mt-3 whitespace-pre-line text-sm leading-relaxed opacity-90">{{ job.description }}</p>
                    </article>
                </div>
            </section>

            <!-- Projects -->
            <section v-if="projects.length" id="projects" class="scroll-mt-20">
                <h2 class="mb-6 text-2xl font-bold">Some Things I've Built</h2>
                <div class="grid gap-5 sm:grid-cols-2">
                    <article v-for="(p, i) in projects" :key="i" class="overflow-hidden rounded-lg border" :style="{ borderColor: line }">
                        <img v-if="p.image_url" :src="p.image_url" :alt="p.title ?? p.name" class="h-44 w-full object-cover" />
                        <div class="p-5">
                            <h3 class="font-semibold">{{ p.title ?? p.name }}</h3>
                            <p v-if="p.role" class="text-xs opacity-60">{{ p.role }}</p>
                            <p v-if="p.description" class="mt-2 text-sm leading-relaxed opacity-90">{{ p.description }}</p>
                            <p v-if="p.technologies" class="mt-3 text-xs" :style="{ color: t.accent }">
                                {{ Array.isArray(p.technologies) ? p.technologies.join(' · ') : p.technologies }}
                            </p>
                            <div class="mt-4 flex gap-4 text-sm">
                                <a v-if="p.repo_url" :href="p.repo_url" target="_blank" rel="noopener" :style="{ color: t.accent }">Repository</a>
                                <a v-if="p.demo_url" :href="p.demo_url" target="_blank" rel="noopener" :style="{ color: t.accent }">Live Demo</a>
                            </div>
                        </div>
                    </article>
                </div>
            </section>

            <!-- Certifications -->
            <section v-if="certifications.length" id="certs" class="scroll-mt-20">
                <h2 class="mb-6 text-2xl font-bold">Certifications &amp; Awards</h2>
                <div class="grid gap-4 sm:grid-cols-2">
                    <article v-for="(c, i) in certifications" :key="i" class="rounded-lg border p-5" :style="{ borderColor: line }">
                        <h3 class="font-semibold">{{ c.title ?? c.name }}</h3>
                        <p class="text-sm" :style="{ color: t.accent }">{{ c.issuer }}</p>
                        <p v-if="c.date" class="text-xs opacity-60">{{ c.date }}</p>
                        <p v-if="c.description" class="mt-2 text-sm opacity-90">{{ c.description }}</p>
                    </article>
                </div>
            </section>

            <!-- Education -->
            <section v-if="education.length" id="education" class="scroll-mt-20">
                <h2 class="mb-6 text-2xl font-bold">Education</h2>
                <div class="space-y-3">
                    <article v-for="(e, i) in education" :key="i" class="rounded-lg border p-5" :style="{ borderColor: line }">
                        <h3 class="font-semibold">{{ e.school }}</h3>
                        <p class="text-sm" :style="{ color: t.accent }">{{ e.degree ?? e.course }}</p>
                        <p v-if="e.year" class="text-xs opacity-60">{{ e.year }}</p>
                        <p v-if="e.achievements" class="mt-2 text-sm opacity-90">{{ e.achievements }}</p>
                    </article>
                </div>
            </section>

            <!-- Contact -->
            <section id="contact" class="scroll-mt-20 rounded-2xl p-8 text-center" :style="{ background: soft }">
                <h2 class="text-2xl font-bold">Let's work together</h2>
                <p class="mt-2 opacity-80">Have an opportunity or a project in mind? I'd love to hear from you.</p>
                <div class="mt-6 flex flex-wrap justify-center gap-3">
                    <a v-if="data.email" :href="`mailto:${data.email}`" class="rounded-md px-5 py-2.5 text-sm font-medium" :style="{ background: t.accent, color: t.background }">
                        Email Me
                    </a>
                    <a
                        v-for="[label, url] in links"
                        :key="label"
                        :href="url"
                        target="_blank"
                        rel="noopener"
                        class="rounded-md border px-5 py-2.5 text-sm font-medium"
                        :style="{ borderColor: t.accent, color: t.accent }"
                    >
                        {{ label }}
                    </a>
                </div>
                <p v-if="data.phone" class="mt-4 text-sm opacity-70">{{ data.phone }}</p>
            </section>
        </main>
    </div>
</template>
