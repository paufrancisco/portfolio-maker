<template>
    <Head :title="doc.title" />

    <div class="min-h-screen bg-gray-50 text-gray-800">
        <header class="mx-auto flex max-w-3xl items-center justify-between px-6 py-5">
            <Link href="/" class="text-lg font-bold">CampusCraft</Link>
            <nav class="space-x-4 text-sm text-gray-600">
                <Link href="/terms" class="hover:text-gray-900">Terms</Link>
                <Link href="/privacy" class="hover:text-gray-900">Privacy</Link>
            </nav>
        </header>

        <main class="mx-auto max-w-3xl px-6 pb-16">
            <h1 class="text-2xl font-bold">{{ doc.title }}</h1>
            <p class="mt-1 text-sm text-gray-500">Last updated November 2, 2026</p>

            <div class="mt-6 space-y-6 rounded-xl border bg-white p-6 shadow-sm">
                <section v-for="s in doc.sections" :key="s.heading">
                    <h2 class="text-base font-semibold">{{ s.heading }}</h2>
                    <p v-for="(t, i) in s.text" :key="i" class="mt-2 text-sm leading-relaxed text-gray-700">{{ t }}</p>
                </section>
            </div>
        </main>
    </div>
</template>

<script setup>
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';

const props = defineProps({
    page: String,
});

// Reminder: update the contact email here if it changes (same one as the home page footer)
const email = 'francisco.pfa.pf@gmail.com';

const docs = {
    terms: {
        title: 'Terms of Service',
        sections: [
            {
                heading: 'About CampusCraft',
                text: [
                    'CampusCraft offers thesis consultation, tutoring through recorded walkthroughs, online portfolio pages, and a resume maker. By sending a request or creating an account you agree to these terms.',
                ],
            },
            {
                heading: 'Requests and quotes',
                text: [
                    'Thesis consultation and tutoring are priced per request. After you send a request we review it and email you a quote. We may also decline a request and tell you why. Work starts only after your payment is confirmed.',
                ],
            },
            {
                heading: 'Payment',
                text: [
                    'You pay by scanning our QR code and uploading a screenshot or PDF of your payment with its reference number. We check each payment by hand. If a proof cannot be verified we tell you the reason and you can upload a new one.',
                ],
            },
            {
                heading: 'Delivery',
                text: [
                    'Results are delivered as a link (for example a video or a file) by email and on your request status page. Keep that page link private, because anyone who has it can view your request.',
                ],
            },
            {
                heading: 'Your content',
                text: [
                    'You keep ownership of everything you send us, such as your request details, files, resume and portfolio content. You confirm that you are allowed to share it and that it does not contain private information about other people without their permission. We use it only to provide the service you asked for.',
                ],
            },
            {
                heading: 'Using the results',
                text: [
                    'Our help is consulting and learning support. You are responsible for how you use what we deliver, including following the rules of your school or workplace.',
                ],
            },
            {
                heading: 'Portfolio pages',
                text: [
                    'A published portfolio is public at its own link. You control what it says. We may unpublish content that is unlawful, abusive, or misleading.',
                ],
            },
            {
                heading: 'Problems with an order',
                text: [
                    'If something is wrong with your order, email us at ' + email + ' and we will work it out with you.',
                ],
            },
            {
                heading: 'Changes',
                text: [
                    'We may update these terms. The date at the top shows the latest version. Continuing to use the site after a change means you accept it.',
                ],
            },
            {
                heading: 'Contact',
                text: ['Questions about these terms: ' + email],
            },
        ],
    },
    privacy: {
        title: 'Privacy Policy',
        sections: [
            {
                heading: 'What we collect',
                text: [
                    'When you send a request: your email, the details you write, and any file you attach. When you pay: the reference number and the proof of payment you upload. When you create an account: your name, your email, and your password, which we store in a hashed form. When you use the portfolio or resume tools: the content you enter and any resume or photo you upload. When you leave a review: the name and comment you choose to show.',
                ],
            },
            {
                heading: 'How we use it',
                text: [
                    'We use your information to answer your request, send you emails about it, check your payment, deliver your result, and run your account. We do not sell your information.',
                ],
            },
            {
                heading: 'Who can see it',
                text: [
                    'Only we can see your requests, files and payment proofs. Two things are public by design: a portfolio you publish, and a review you submit once it is approved. Approved reviews show your name and comment, never your email.',
                    'We rely on these services to run the site: Render for hosting, Supabase for the database and file storage, and Gmail for sending email. They process data on our behalf to provide those services.',
                ],
            },
            {
                heading: 'Request status links',
                text: [
                    'You do not need an account to send a thesis or tutoring request. Instead we give you a private link to your request page. Anyone who has the link can view the request, so keep it private.',
                ],
            },
            {
                heading: 'Storage and security',
                text: [
                    'Uploaded files such as attachments and payment proofs are kept in private storage and are opened only through short-lived links. This is reasonable protection, but no online service can promise perfect security.',
                ],
            },
            {
                heading: 'Cookies',
                text: [
                    'We use only the cookies needed to keep you logged in and to protect forms from abuse. We do not use advertising cookies.',
                ],
            },
            {
                heading: 'Your choices',
                text: [
                    'You can ask us to show you, correct, or delete your information by emailing ' + email + '. We keep your information until you ask us to delete it.',
                ],
            },
            {
                heading: 'Changes and contact',
                text: [
                    'We may update this policy and will change the date at the top when we do. Questions: ' + email,
                ],
            },
        ],
    },
};

const doc = computed(() => docs[props.page] ?? docs.terms);
</script>
