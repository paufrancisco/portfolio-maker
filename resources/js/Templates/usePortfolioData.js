import { computed } from 'vue';

const list = (v) => (Array.isArray(v) ? v.filter(Boolean) : []);

export function usePortfolioData(getData) {
    const skills = computed(() => {
        const s = getData().skills;
        const raw = Array.isArray(s) ? s : typeof s === 'string' ? s.split(',') : [];
        return raw
            .map((x) => (x && typeof x === 'object' ? x.name : String(x ?? '').trim()))
            .filter(Boolean);
    });

    const experience = computed(() => list(getData().experience));
    const projects = computed(() => list(getData().projects));
    const certifications = computed(() => list(getData().certifications));
    const education = computed(() => list(getData().education));

    const initial = computed(() => (getData().name ?? '?').trim().charAt(0).toUpperCase());

    const links = computed(() =>
        [
            ['LinkedIn', getData().linkedin],
            ['GitHub', getData().github],
            ['Website', getData().website],
        ].filter(([, url]) => url),
    );

    const nav = computed(() =>
        [
            getData().about && ['About', '#about'],
            skills.value.length && ['Skills', '#skills'],
            experience.value.length && ['Work', '#work'],
            projects.value.length && ['Projects', '#projects'],
            certifications.value.length && ['Certifications', '#certs'],
            education.value.length && ['Education', '#education'],
            ['Contact', '#contact'],
        ].filter(Boolean),
    );

    const tags = (tech) =>
        Array.isArray(tech)
            ? tech.filter(Boolean)
            : String(tech ?? '')
                  .split(',')
                  .map((x) => x.trim())
                  .filter(Boolean);

    return { skills, experience, projects, certifications, education, initial, links, nav, tags };
}
