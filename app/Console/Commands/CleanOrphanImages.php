<?php

namespace App\Console\Commands;

use App\Models\Portfolio;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

class CleanOrphanImages extends Command
{
    protected $signature = 'portfolio:clean-images
        {--delete : Actually delete the files (default is a dry run that only lists them)}
        {--hours=24 : Only touch files older than this many hours}
        {--max=100 : Refuse to delete more than this many files in one run}';

    protected $description = 'Find (and optionally delete) portfolio images that no portfolio uses any more';

    public function handle(): int
    {
        $disk = Storage::disk('images');
        $urls = $this->referencedUrls();
        $cutoff = Carbon::now()->subHours((int) $this->option('hours'))->getTimestamp();

        // Only the portfolios/ folder is ever scanned. Other files in the bucket (site/payment-qr.jpg) are never touched.
        $orphans = [];
        foreach ($disk->allFiles('portfolios') as $path) {
            if ($this->isReferenced($path, $urls)) {
                continue;
            }
            // Uploads land in the bucket before the customer clicks Save, so give new files time.
            if ($disk->lastModified($path) > $cutoff) {
                continue;
            }
            $orphans[] = $path;
        }

        $this->info(count($orphans).' unused image(s) found.');
        foreach ($orphans as $path) {
            $this->line('  '.$path);
        }

        if (! $orphans) {
            return self::SUCCESS;
        }

        if (! $this->option('delete')) {
            $this->warn('Dry run: nothing was deleted. Add --delete to remove them.');

            return self::SUCCESS;
        }

        if (count($orphans) > (int) $this->option('max')) {
            $this->error('Refusing to delete more than '.$this->option('max').' files. Check the list, then raise --max if it is correct.');

            return self::FAILURE;
        }

        $disk->delete($orphans);
        $this->info('Deleted '.count($orphans).' file(s).');

        return self::SUCCESS;
    }

    // Every image URL used by any portfolio, draft or published.
    private function referencedUrls(): array
    {
        $urls = [];

        Portfolio::query()->select(['id', 'data', 'published_data'])->cursor()->each(function ($p) use (&$urls) {
            foreach ([$p->data, $p->published_data] as $d) {
                if (! is_array($d)) {
                    continue;
                }
                if (! empty($d['photo_url'])) {
                    $urls[] = (string) $d['photo_url'];
                }
                foreach ($d['projects'] ?? [] as $project) {
                    if (! empty($project['image_url'])) {
                        $urls[] = (string) $project['image_url'];
                    }
                }
            }
        });

        return array_values(array_unique($urls));
    }

    private function isReferenced(string $path, array $urls): bool
    {
        foreach ($urls as $url) {
            if (str_contains($url, $path)) {
                return true;
            }
        }

        return false;
    }
}
