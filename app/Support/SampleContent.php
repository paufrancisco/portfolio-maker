<?php

namespace App\Support;

// Fake content for the public home-page template previews. No real user data.
class SampleContent
{
    public static function resume(): array
    {
        return [
            'personal' => [
                'name' => 'Maria Santos',
                'headline' => 'Junior Web Developer',
                'email' => 'maria.santos@example.com',
                'phone' => '+63 900 000 0000',
                'location' => 'General Santos City',
            ],
            'summary' => 'Fresh graduate with hands-on experience building web apps with Laravel and Vue. Quick learner who enjoys turning ideas into clean, working software.',
            'experience' => [
                ['role' => 'Web Development Intern', 'company' => 'Sample Tech Inc.', 'period' => 'Jun 2025 - Aug 2025', 'details' => "Built admin pages with Laravel and Vue.\nFixed bugs and wrote simple tests."],
                ['role' => 'Student Assistant', 'company' => 'University IT Office', 'period' => '2023 - 2025', 'details' => 'Supported students and staff with accounts and basic troubleshooting.'],
            ],
            'education' => [
                ['school' => 'Sample State University', 'degree' => 'BS Information Technology', 'period' => '2021 - 2025', 'details' => 'Thesis: attendance system with QR codes.'],
            ],
            'skills' => ['Laravel', 'Vue 3', 'MySQL', 'Tailwind CSS', 'Git'],
            'projects' => [
                ['name' => 'Campus Events App', 'link' => 'example.com/events', 'details' => 'Web app for posting and joining school events.'],
            ],
        ];
    }

    public static function portfolio(): array
    {
        return [
            'name' => 'Maria Santos',
            'headline' => 'Junior Web Developer',
            'intro' => 'I build clean, friendly web apps with Laravel and Vue.',
            'about' => "Fresh IT graduate who enjoys turning ideas into working software.\nLooking for a first full-time role on a web team.",
            'email' => 'maria.santos@example.com',
            'phone' => '+63 900 000 0000',
            'github' => 'https://github.com/example',
            'linkedin' => 'https://linkedin.com/in/example',
            'skills' => ['Laravel', 'Vue 3', 'MySQL', 'Tailwind CSS', 'Git'],
            'experience' => [
                ['position' => 'Web Development Intern', 'company' => 'Sample Tech Inc.', 'type' => 'Internship', 'start' => 'Jun 2025', 'end' => 'Aug 2025', 'description' => 'Built admin pages with Laravel and Vue and fixed bugs.'],
            ],
            'projects' => [
                ['title' => 'Campus Events App', 'role' => 'Full-stack developer', 'description' => 'Web app for posting and joining school events.', 'technologies' => ['Laravel', 'Vue 3']],
                ['title' => 'Attendance QR System', 'role' => 'Thesis project', 'description' => 'Attendance tracking with QR codes.', 'technologies' => ['PHP', 'MySQL']],
            ],
            'certifications' => [
                ['title' => 'Web Development Fundamentals', 'issuer' => 'Sample Academy', 'date' => '2025'],
            ],
            'education' => [
                ['school' => 'Sample State University', 'degree' => 'BS Information Technology', 'year' => '2021 - 2025', 'achievements' => "Dean's lister"],
            ],
        ];
    }

    // Same colors as TemplateSeeder, so the public previews do not need the templates table
    public static function theme(string $key): array
    {
        return [
            'professional' => ['primary' => '#1f2937', 'accent' => '#2563eb', 'background' => '#ffffff', 'font' => 'Inter, sans-serif'],
            'pink' => ['primary' => '#be185d', 'accent' => '#f472b6', 'background' => '#fff1f2', 'font' => 'Poppins, sans-serif'],
            'blue' => ['primary' => '#1d4ed8', 'accent' => '#38bdf8', 'background' => '#eff6ff', 'font' => 'Poppins, sans-serif'],
            'showcase' => ['primary' => '#111827', 'accent' => '#f97316', 'background' => '#fffbeb', 'font' => 'Poppins, sans-serif'],
            'minimal' => ['primary' => '#1f2937', 'accent' => '#059669', 'background' => '#ffffff', 'font' => 'Inter, sans-serif'],
            'editorial' => ['primary' => '#292524', 'accent' => '#9a3412', 'background' => '#faf7f2', 'font' => 'Georgia, serif'],
        ][$key] ?? [];
    }
}
