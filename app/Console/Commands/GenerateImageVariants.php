<?php

namespace App\Console\Commands;

use App\Support\ImageVariants;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

class GenerateImageVariants extends Command
{
    protected $signature = 'images:variants {dirs?* : Folder di disk public (default: wp posts cities)}';

    protected $description = 'Buat versi kecil (480/960px WebP) untuk gambar di disk public';

    public function handle(): int
    {
        $dirs = $this->argument('dirs') ?: ['wp', 'posts', 'cities'];
        $files = collect($dirs)
            ->flatMap(fn ($dir) => Storage::disk('public')->allFiles($dir))
            ->reject(fn ($path) => ImageVariants::isVariant($path))
            ->values();

        $created = 0;
        $this->withProgressBar($files, function (string $path) use (&$created) {
            $created += ImageVariants::generate($path);
        });

        $this->newLine(2);
        $this->info("{$files->count()} gambar diperiksa, {$created} versi kecil dibuat.");

        return self::SUCCESS;
    }
}
