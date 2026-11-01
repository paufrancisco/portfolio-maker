<?php

namespace App\Services;

use Illuminate\Support\Facades\Storage;
use Smalot\PdfParser\Parser;

class ResumeParser
{
    private const HEADINGS = [
        'summary' => 'about', 'professional summary' => 'about', 'profile' => 'about',
        'about me' => 'about', 'about' => 'about', 'objective' => 'about', 'career objective' => 'about',
        'skills' => 'skills', 'technical skills' => 'skills', 'technical profile' => 'skills',
        'core competencies' => 'skills', 'technologies' => 'skills',
        'experience' => 'experience', 'work experience' => 'experience', 'professional experience' => 'experience',
        'employment history' => 'experience', 'work history' => 'experience',
        'work and volunteer experience' => 'experience', 'volunteer experience' => 'experience',
        'projects' => 'projects', 'personal projects' => 'projects', 'selected projects' => 'projects',
        'certifications' => 'certifications', 'licenses and certifications' => 'certifications',
        'certifications and awards' => 'certifications', 'awards' => 'certifications',
        'honors and awards' => 'certifications', 'achievements' => 'certifications',
        'education' => 'education', 'educational background' => 'education',
        'soft skills' => 'skills', 'key highlights' => 'highlights', 'highlights' => 'highlights',
        'major projects' => 'projects', 'mini projects' => 'projects',
        'trainings and certifications' => 'certifications', 'training and certifications' => 'certifications',
        'trainings' => 'certifications', 'seminars and trainings' => 'certifications',
    ];

    private const MONTH = '(?:Jan|Feb|Mar|Apr|May|Jun|Jul|Aug|Sep|Sept|Oct|Nov|Dec)[a-z]*\.?';

    public function parse(string $path): array
    {
        $result = [
            'name' => null, 'email' => null, 'phone' => null, 'headline' => '',
            'about' => '', 'skills' => [], 'experience' => [], 'projects' => [],
            'certifications' => [], 'education' => [], 'linkedin' => '', 'github' => '',
            'raw_text' => '',
        ];

        try {
            $text = $this->extractText($path);
        } catch (\Throwable $e) {
            report($e);

            return $result;
        }

        $text = trim($text);
        $result['raw_text'] = $text;
        if ($text === '') {
            return $result;
        }

        if (preg_match('/[A-Z0-9._%+\-]+@[A-Z0-9.\-]+\.[A-Z]{2,}/i', $text, $m)) {
            $result['email'] = $m[0];
        }
        if (preg_match('/(?:\+?63|0)\s?9\d{2}[\s\-]?\d{3}[\s\-]?\d{4}/', $text, $m)) {
            $result['phone'] = trim($m[0]);
        }
        if (preg_match('~https?://(?:www\.)?linkedin\.com/[^\s)]+~i', $text, $m)) {
            $result['linkedin'] = rtrim($m[0], '.,;');
        }
        if (preg_match('~https?://(?:www\.)?github\.com/[A-Za-z0-9\-_]+~i', $text, $m)) {
            $result['github'] = $m[0];
        }

        $lines = array_values(array_filter(
            array_map('trim', preg_split('/\R/', $text)),
            fn ($l) => $l !== ''
        ));

        $nameIdx = null;
        foreach ($lines as $i => $line) {
            if (mb_strlen($line) <= 60 && ! str_contains($line, '@') && ! preg_match('~https?://|\d{4,}~', $line)
                && ! $this->sectionOf($line)) {
                $result['name'] = $line;
                $nameIdx = $i;
                break;
            }
        }

        if ($nameIdx !== null) {
            $next = $lines[$nameIdx + 1] ?? '';
            if ($next !== '' && mb_strlen($next) <= 80 && ! $this->sectionOf($next)
                && ! str_contains($next, '@') && ! preg_match('~https?://|\d{5,}~', $next)) {
                $result['headline'] = $next;
            }
        }

        $sections = [];
        $preamble = [];
        $current = null;
        foreach ($lines as $i => $line) {
            if ($key = $this->sectionOf($line)) {
                $current = $key;
                $sections[$current] ??= [];
                continue;
            }
            if ($current === null) {
                if ($nameIdx !== null && $i > $nameIdx) {
                    $preamble[] = $line;
                }
                continue;
            }
            if (in_array($current, ['skills', 'about'], true) && $this->looksLikeHeading($line)) {
                $current = 'other';
                $sections['other'] ??= [];
                continue;
            }
            $sections[$current][] = $line;
        }

        $result['about'] = $this->buildAbout($preamble, $sections);
        $result['skills'] = $this->parseSkills($sections['skills'] ?? []);
        $result['experience'] = $this->parseExperience($sections['experience'] ?? []);
        $result['projects'] = $this->parseProjects($sections['projects'] ?? []);
        $result['certifications'] = $this->parseCertifications($sections['certifications'] ?? []);
        $result['education'] = $this->parseEducation($sections['education'] ?? []);

        return $result;
    }

    private function extractText(string $path): string
    {
        $content = Storage::disk('s3')->get($path);
        $ext = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($ext === 'pdf' || str_starts_with($content, '%PDF')) {
            return (new Parser())->parseContent($content)->getText();
        }

        if ($ext === 'docx' || str_starts_with($content, 'PK')) {
            return $this->docxText($content);
        }

        return '';
    }

    private function docxText(string $content): string
    {
        $tmp = tempnam(sys_get_temp_dir(), 'docx');
        file_put_contents($tmp, $content);

        try {
            $doc = \PhpOffice\PhpWord\IOFactory::load($tmp, 'Word2007');
            $lines = [];
            foreach ($doc->getSections() as $section) {
                $this->collect($section->getElements(), $lines);
            }

            return implode("\n", $lines);
        } finally {
            @unlink($tmp);
        }
    }

    private function collect(iterable $elements, array &$lines): void
    {
        foreach ($elements as $el) {
            if ($el instanceof \PhpOffice\PhpWord\Element\TextRun || $el instanceof \PhpOffice\PhpWord\Element\ListItemRun) {
                $buf = '';
                foreach ($el->getElements() as $child) {
                    if (method_exists($child, 'getText') && is_string($child->getText())) {
                        $buf .= $child->getText();
                    }
                }
                $lines[] = $buf;
            } elseif ($el instanceof \PhpOffice\PhpWord\Element\Text) {
                $lines[] = $el->getText();
            } elseif ($el instanceof \PhpOffice\PhpWord\Element\Table) {
                foreach ($el->getRows() as $row) {
                    foreach ($row->getCells() as $cell) {
                        $this->collect($cell->getElements(), $lines);
                    }
                }
            }
        }
    }

    private function sectionOf(string $line): ?string
    {
        if (preg_match('/^\p{Ll}/u', trim($line))) {
            return null;
        }

        $clean = strtolower(trim(preg_replace('/[^A-Za-z& ]/', '', $line)));
        $clean = str_replace('&', 'and', $clean);
        $clean = preg_replace('/\s+/', ' ', $clean);

        return mb_strlen($line) <= 45 ? (self::HEADINGS[$clean] ?? null) : null;
    }

    private function parseSkills(array $lines): array
    {
        $skills = [];
        foreach ($lines as $line) {
            if (preg_match('/^[^:,]{2,40}:\s*(.+)$/', $line, $m)) {
                $line = $m[1];
            }
            foreach (preg_split('/[,;•·|▪●]/u', $line) as $s) {
                $s = trim($s, " \t-–—*");
                if ($s !== '' && mb_strlen($s) <= 40) {
                    $skills[] = $s;
                }
            }
        }

        return array_values(array_unique($skills));
    }

    private function parseExperience(array $lines): array
    {
        $n = count($lines);
        $starts = [];

        foreach ($lines as $i => $line) {
            if ($this->hasDateRange($line)) {
                $left = trim(preg_replace($this->dateRegex(), '', $line), " \t|,-–—·()");
                if ($left !== '') {
                    $starts[$i] = true;
                } elseif ($i >= 2 && str_contains($lines[$i - 1], ' · ')) {
                    $starts[$i - 2] = true;
                } elseif ($i >= 1) {
                    $starts[$i - 1] = true;
                } else {
                    $starts[$i] = true;
                }
            }
        }

        // Entry na walang petsa: nagsisimula ito pagkatapos ng "Skills:" line
        foreach ($lines as $i => $line) {
            if (preg_match('/^Skills\s*:/i', $line) && $i + 1 < $n && ! isset($starts[$i + 1])
                && ! preg_match('/^[•·▪●\-–*]/u', $lines[$i + 1])) {
                $starts[$i + 1] = true;
            }
        }

        $idx = array_keys($starts);
        sort($idx);

        $entries = [];
        foreach ($idx as $k => $from) {
            $to = ($idx[$k + 1] ?? $n) - 1;
            $entries[] = $this->buildExperience(array_slice($lines, $from, $to - $from + 1));
        }

        return $entries;
    }

    private function splitPositionCompany(string $head, array &$entry): void
    {
        foreach ([' | ', ' - ', ' – ', ' — ', ' at ', ', '] as $sep) {
            if (str_contains($head, $sep)) {
                [$a, $b] = array_map('trim', explode($sep, $head, 2));
                $entry['position'] = $a;
                $entry['company'] = $b;

                return;
            }
        }
        $entry['position'] = $head;
    }

    private function parseProjects(array $lines): array
    {
        $entries = [];
        $cur = null;
        $desc = [];

        $flush = function () use (&$entries, &$cur, &$desc) {
            if ($cur) {
                $cur['description'] = implode(' ', $this->joinBullets($desc));
                $entries[] = $cur;
            }
            $cur = null;
            $desc = [];
        };

        foreach ($lines as $line) {
            $isBullet = (bool) preg_match('/^[•·▪●\-–*]/u', $line);

            if (! $isBullet && mb_strlen($line) <= 80 && $this->isUpper($line)) {
                $flush();
                $cur = ['title' => $this->prettyCaps($line), 'role' => '', 'description' => '', 'technologies' => '', 'repo_url' => '', 'demo_url' => ''];
                continue;
            }
            if (! $cur) {
                continue;
            }

            $text = ltrim($line, "•·▪●-–* \t");
            if (preg_match('/^(?:tech(?:nologies)?|stack|built with)\s*:\s*(.+)$/i', $text, $m)) {
                $cur['technologies'] = $m[1];
            } elseif (preg_match('~^(?:https?://)?[\w\-]+(?:\.[\w\-]+)*\.(?:com|io|app|dev|net|org|ph|co|me|ai|xyz)(?:/\S*)?$~i', $text)) {
                $url = preg_match('~^https?://~i', $text) ? $text : 'https://'.$text;
                $cur[str_contains($url, 'github.com') ? 'repo_url' : 'demo_url'] = rtrim($url, '.,;');
            } else {
                $desc[] = $line;
            }
        }
        $flush();

        return $entries;
    }

    private function parseCertifications(array $lines): array
    {
        $datePat = '/\b(?:'.self::MONTH.'\s+(?:\d{1,2},?\s+)?)?(?:19|20)\d{2}\b/i';
        $blockMode = (bool) array_filter($lines, fn ($l) => $this->isUpper(preg_replace($datePat, '', $l)));

        $out = [];
        $cur = null;
        $desc = [];

        $flush = function () use (&$out, &$cur, &$desc) {
            if ($cur) {
                $cur['description'] = implode(' ', $this->joinBullets($desc));
                $out[] = $cur;
            }
            $cur = null;
            $desc = [];
        };

        foreach ($lines as $line) {
            $line = trim($line);
            $clean = trim(preg_replace('/\s{2,}/', ' ', preg_replace($datePat, '', $line)), " \t|,-–—·()");
            if ($clean === '') {
                continue;
            }
            $isBullet = (bool) preg_match('/^[•·▪●\-–*]/u', $line);

            if ($blockMode) {
                $head = preg_split('/\s[–—-]\s/u', $clean)[0];
                if (! $isBullet && $this->isUpper($head)) {
                    $flush();
                    $date = preg_match($datePat, $line, $m) ? $m[0] : '';
                    $title = $clean;
                    $issuer = '';
                    foreach ([' – ', ' — ', ' - '] as $sep) {
                        if (str_contains($clean, $sep)) {
                            [$title, $issuer] = array_map('trim', explode($sep, $clean, 2));
                            break;
                        }
                    }
                    $cur = ['title' => $this->prettyCaps($title), 'issuer' => $issuer, 'date' => $date, 'description' => ''];
                    continue;
                }
                if (! $cur) {
                    continue;
                }
                if ($cur['issuer'] === '' && ! $desc && ! $isBullet && mb_strlen($clean) <= 60 && ! preg_match('/[.!?]$/', $clean)) {
                    $cur['issuer'] = $clean;
                    continue;
                }
                $desc[] = $line;
                continue;
            }

            $date = preg_match($datePat, $line, $m) ? $m[0] : '';
            $base = ltrim($clean, "•·▪●-–* \t");
            $title = $base;
            $issuer = '';
            foreach ([' | ', ' - ', ' – ', ' — ', ', '] as $sep) {
                if (str_contains($base, $sep)) {
                    [$title, $issuer] = array_map('trim', explode($sep, $base, 2));
                    break;
                }
            }
            $out[] = ['title' => $title, 'issuer' => $issuer, 'date' => $date, 'description' => ''];
        }
        $flush();

        return $out;
    }

    private function parseEducation(array $lines): array
    {
        $entries = [];
        $cur = null;
        $yearPat = '/\b(?:19|20)\d{2}\b/';

        foreach ($lines as $line) {
            $line = ltrim($line, "•·▪●-–* \t");
            $parts = array_values(array_filter(array_map('trim', preg_split('/\s{2,}/', $line))));
            if (! $parts) {
                continue;
            }
            $first = $parts[0];

            if (preg_match('/\b(university|college|institute|school|academy|polytechnic)\b/i', $first)) {
                if ($cur) {
                    $entries[] = $cur;
                }
                $year = '';
                foreach (array_slice($parts, 1) as $part) {
                    if (! $year && preg_match($yearPat, $part, $m)) {
                        $year = $m[0];
                    }
                }
                $school = $this->isUpper($first) ? $this->prettyCaps($first) : $first;
                $cur = ['school' => $school, 'degree' => '', 'year' => $year, 'achievements' => ''];
            } elseif ($cur) {
                if (! $cur['degree'] && preg_match('/\b(bachelor|master|bs|ms|ba|ma|b\.s|diploma|associate|ph\.?d|degree|course|computer)\b/i', $first)) {
                    $cur['degree'] = $first;
                    foreach (array_slice($parts, 1) as $part) {
                        if (! $cur['year'] && preg_match($yearPat, $part, $m)) {
                            $cur['year'] = $m[0];
                        }
                    }
                } else {
                    $cur['achievements'] .= ($cur['achievements'] ? '; ' : '').implode(' ', $parts);
                }
            }
        }
        if ($cur) {
            $entries[] = $cur;
        }

        return $entries;
    }

    private function dateRegex(): string
    {
        $m = self::MONTH;
        $d = "(?:(?:$m\s+)?(?:19|20)\d{2}|Present|Current|Now)";

        return "/$d\s*(?:-|–|—|to)\s*$d/i";
    }

    private function hasDateRange(string $line): bool
    {
        return (bool) preg_match($this->dateRegex(), $line);
    }

    private function splitDates(string $line): array
    {
        if (preg_match($this->dateRegex(), $line, $m)) {
            $parts = preg_split('/\s*(?:-|–|—|\bto\b)\s*/i', $m[0], 2);
            $end = trim($parts[1] ?? '');

            return [trim($parts[0]), preg_match('/present|current|now/i', $end) ? '' : $end];
        }

        return ['', ''];
    }

    private function buildExperience(array $block): array
    {
        $entry = ['company' => '', 'position' => '', 'type' => '', 'start' => '', 'end' => '', 'description' => ''];

        $dateAt = null;
        foreach (array_slice($block, 0, 4, true) as $i => $l) {
            if ($this->hasDateRange($l)) {
                $dateAt = $i;
                break;
            }
        }

        if ($dateAt !== null) {
            [$entry['start'], $entry['end']] = $this->splitDates($block[$dateAt]);
            $header = array_slice($block, 0, $dateAt);
            $left = trim(preg_replace($this->dateRegex(), '', $block[$dateAt]), " \t|,-–—·");
            $left = trim(preg_replace('/\(\s*\)/', '', $left), " \t|,-–—·");
            $left = trim(preg_replace('/\s*[-–—]\s*$/u', '', $left));
            if ($left !== '') {
                $header[] = $left;
            }
            $body = array_slice($block, $dateAt + 1);
        } else {
            $header = [$block[0]];
            $body = array_slice($block, 1);
            if ($body && mb_strlen($body[0]) <= 70 && ! preg_match('/^[•·▪●\-–*]|^Skills\s*:|[.!?]$/u', $body[0])) {
                $header[] = array_shift($body);
            }
        }

        $entry['position'] = $header[0] ?? '';
        if (substr_count($entry['position'], '(') > substr_count($entry['position'], ')')) {
            $entry['position'] .= ')';
        }
        if (count($header) >= 2) {
            [$company, $type] = array_pad(array_map('trim', explode(' · ', $header[1], 2)), 2, '');
            $entry['company'] = $company;
            $entry['type'] = $type;
        } else {
            $this->splitPositionCompany($entry['position'], $entry);
        }

        $body = array_values(array_filter($body, fn ($l) => ! preg_match('/^Skills\s*:/i', $l)));
        if ($body && preg_match('/(Remote|On-site|Hybrid)\s*$/i', $body[0])) {
            array_shift($body);
        }
        $entry['description'] = implode("\n", $this->joinBullets($body));

        return $entry;
    }

    private function buildAbout(array $preamble, array $sections): string
    {
        $prose = $sections['about'] ?? array_values(array_filter($preamble, fn ($l) => ! $this->isContactLine($l)));
        $text = trim(implode(' ', $prose));

        $highlights = $this->joinBullets($sections['highlights'] ?? []);
        if ($highlights) {
            $text .= ($text ? "\n\n" : '').implode("\n", array_map(fn ($h) => '• '.$h, $highlights));
        }

        return $text;
    }

    private function joinBullets(array $lines): array
    {
        $items = [];
        foreach ($lines as $line) {
            $isBullet = (bool) preg_match('/^[•·▪●\-–*]\s*/u', $line);
            $clean = trim(preg_replace('/^[•·▪●\-–*]\s*/u', '', $line));
            if ($clean === '') {
                continue;
            }
            if ($isBullet || ! $items) {
                $items[] = $clean;
            } else {
                $items[count($items) - 1] .= ' '.$clean;
            }
        }

        return $items;
    }

    private function isContactLine(string $line): bool
    {
        return (bool) preg_match('~https?://|@|☎|✉|^\s*(portfolio|github|linkedin|glassdoor|website)\s*:~iu', $line);
    }

    private function looksLikeHeading(string $line): bool
    {
        $len = mb_strlen($line);

        return $len >= 6 && $len <= 45 && str_contains($line, ' ')
            && ! preg_match('/[\p{Ll}\d:]/u', $line) && (bool) preg_match('/\p{Lu}/u', $line);
    }

    private function isUpper(string $s): bool
    {
        $s = trim($s);

        return preg_match_all('/\p{L}/u', $s) >= 3 && ! preg_match('/\p{Ll}/u', $s);
    }

    private function prettyCaps(string $s): string
    {
        $keep = ['NLP', 'API', 'PHP', 'SQL', 'CSS', 'OCR', 'ICS', 'STI', 'CRUD', 'HTML', 'XML', 'GWA', 'CS', 'IT', 'UI', 'UX', 'AI'];
        $small = ['AND', 'OF', 'THE', 'FOR', 'INTO', 'TO', 'IN', 'ON', 'A'];
        $first = true;

        $out = preg_replace_callback('/\p{L}+/u', function ($m) use ($keep, $small, &$first) {
            $w = $m[0];
            $up = mb_strtoupper($w);
            $isFirst = $first;
            $first = false;

            if (in_array($up, $keep, true)) {
                return $up;
            }
            if (! $isFirst && in_array($up, $small, true)) {
                return mb_strtolower($w);
            }
            if (mb_strlen($w) === 1) {
                return $up;
            }

            return mb_convert_case($w, MB_CASE_TITLE);
        }, $s);

        return preg_replace_callback('/(\d)(St|Nd|Rd|Th)\b/', fn ($m) => $m[1].strtolower($m[2]), $out);
    }
}
