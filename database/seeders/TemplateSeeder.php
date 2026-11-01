<?php

namespace Database\Seeders;

use App\Models\Template;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $templates = [
            [
                'key' => 'professional',
                'name' => 'Professional',
                'theme_options' => [
                    'primary' => '#1f2937',
                    'accent' => '#2563eb',
                    'background' => '#ffffff',
                    'font' => 'Inter, sans-serif',
                ],
            ],
            [
                'key' => 'pink',
                'name' => 'Pink',
                'theme_options' => [
                    'primary' => '#be185d',
                    'accent' => '#f472b6',
                    'background' => '#fff1f2',
                    'font' => 'Poppins, sans-serif',
                ],
            ],
            [
                'key' => 'blue',
                'name' => 'Blue',
                'theme_options' => [
                    'primary' => '#1d4ed8',
                    'accent' => '#38bdf8',
                    'background' => '#eff6ff',
                    'font' => 'Poppins, sans-serif',
                ],
            ],
        ];

        foreach ($templates as $template) {
            Template::updateOrCreate(['key' => $template['key']], $template + ['is_active' => true]);
        }
    }
}
