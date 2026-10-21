<?php

namespace App\Console\Commands;

use App\Models\Post;
use App\Services\WordPress\WordPressClient;
use App\Services\WordPress\WordPressImporter;
use Illuminate\Console\Command;
use Throwable;

class ImportWordPress extends Command
{
    protected $signature = 'wp:import
        {--url=https://bimtekdiklat.com : Alamat situs WordPress sumber}
        {--limit= : Jumlah artikel maksimal (untuk uji coba)}
        {--per-page=20 : Artikel per permintaan API (maks. 100)}
        {--page=1 : Mulai dari halaman API ke-}
        {--dry-run : Tampilkan rencana impor tanpa menyimpan apa pun}
        {--skip-images : Jangan unduh gambar (tetap memakai URL lama)}
        {--update : Perbarui artikel yang sudah pernah diimpor (juga mengulang unduhan berkas yang gagal)}
        {--delay=150 : Jeda antar unduhan berkas dalam milidetik}';

    protected $description = 'Impor artikel & gambar dari WordPress (REST API) menjadi Artikel';

    public function handle(): int
    {
        $client = new WordPressClient($this->option('url'), max(0, (int) $this->option('delay')));
        $importer = new WordPressImporter(
            $client,
            dryRun: (bool) $this->option('dry-run'),
            downloadMedia: ! $this->option('skip-images'),
            updateExisting: (bool) $this->option('update'),
        );
        $limit = $this->option('limit') !== null ? (int) $this->option('limit') : null;

        $this->info(($this->option('dry-run') ? '[DRY RUN] ' : '').'Impor dari '.$client->baseUrl());

        $bar = null;
        try {
            $importer->run(
                limit: $limit,
                perPage: max(1, min(100, (int) $this->option('per-page'))),
                startPage: max(1, (int) $this->option('page')),
                onTotal: function (int $total) use ($limit, &$bar) {
                    $this->line("Total artikel di WordPress: {$total}");
                    if ($limit === null || $limit > 10) {
                        $bar = $this->output->createProgressBar($limit !== null ? min($limit, $total) : $total);
                        $bar->start();
                    }
                },
                onItem: function (string $status, array $wp, ?Post $post) use (&$bar) {
                    if ($bar) {
                        $bar->advance();

                        return;
                    }
                    $title = html_entity_decode(strip_tags($wp['title']['rendered'] ?? ''));
                    $this->line(sprintf('  %-8s #%d %s%s', $status, $wp['id'], mb_strimwidth($title, 0, 80, '…'), $post ? '  → /artikel/'.$post->slug : ''));
                },
            );
        } catch (Throwable $e) {
            $this->newLine(2);
            $done = array_sum($importer->stats);
            $this->error("Impor terhenti setelah {$done} artikel: ".$e->getMessage());
            $this->warn('Jalankan ulang perintah yang sama; artikel yang sudah masuk akan dilewati.');

            return self::FAILURE;
        }

        $bar?->finish();
        $this->newLine(2);

        $this->table(['Hasil', 'Jumlah'], [
            ['Artikel baru', $importer->stats['created']],
            ['Artikel diperbarui', $importer->stats['updated']],
            ['Dilewati (sudah ada)', $importer->stats['skipped']],
            ['Spam dilewati', $importer->stats['spam']],
            ['Berkas diunduh', $importer->media->downloaded],
            ['Berkas dipakai ulang', $importer->media->reused],
            ['Berkas gagal', count($importer->media->failed)],
        ]);

        if ($created = $importer->categoryMapper()->created) {
            $this->line('Kategori baru: '.implode(', ', $created));
        }

        if ($failed = $importer->media->failed) {
            file_put_contents(storage_path('logs/wp-import-failed.txt'), implode(PHP_EOL, $failed).PHP_EOL, FILE_APPEND);
            foreach (array_slice($failed, 0, 10) as $url) {
                $this->warn('  Gagal unduh: '.$url);
            }
            $this->warn('Daftar lengkap: storage/logs/wp-import-failed.txt. Ulangi dengan --update untuk mencoba lagi.');
        }

        return self::SUCCESS;
    }
}
