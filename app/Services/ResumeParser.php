<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class ResumeParser
{
    public function parse(string $path): array
    {
        $result = [
            'name' => null,
            'email' => null,
            'phone' => null,
            'raw_text' => '',
        ];

        if (strtolower(pathinfo($path, PATHINFO_EXTENSION)) !== 'pdf' && ! $this->looksLikePdf($path)) {
            return $result;
        }

        try {
            $content = Storage::disk('s3')->get($path);
            $text = (new Parser())->parseContent($content)->getText();
        } catch (\Throwable $e) {
            report($e);

            return $result;
        }

        $result['raw_text'] = trim($text);

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $m)) {
            $result['email'] = $m[0];
        }

        if (preg_match('/(?:\+?63|0)\s?9\d{2}[\s\-]?\d{3}[\s\-]?\d{4}/', $text, $m)) {
            $result['phone'] = trim($m[0]);
        }

        foreach (preg_split('/\R/', $text) as $line) {
            $line = trim($line);
            if ($line !== '' && mb_strlen($line) <= 60 && ! str_contains($line, '@')) {
                $result['name'] = $line;
                break;
            }
        }

        return $result;
    }

    private function looksLikePdf(string $path): bool
    {
        try {
            return str_starts_with(Storage::disk('s3')->get($path), '%PDF');
        } catch (\Throwable $e) {
            return false;
        }
    }
}
