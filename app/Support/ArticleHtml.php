<?php

namespace App\Support;

use DOMDocument;
use DOMElement;
use DOMXPath;
use Illuminate\Support\Str;

/**
 * Penyesuaian HTML artikel saat ditampilkan:
 * - id pada setiap <h2> + daftar isi,
 * - <h1> di dalam isi dibuang bila sama dengan judul, selain itu diturunkan menjadi <h2>,
 * - gambar lazy-load dengan srcset/width/height (hemat kuota & tanpa pergeseran tata letak).
 */
class ArticleHtml
{
    /** @return array{html: string, toc: array<int, array{id: string, text: string}>} */
    public static function process(string $html, ?string $title = null, string $sizes = '(min-width: 1024px) 720px, 100vw'): array
    {
        if (trim($html) === '') {
            return ['html' => '', 'toc' => []];
        }

        $dom = new DOMDocument;
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="__article">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);
        $root = $dom->getElementById('__article');

        $normalize = fn (?string $s) => mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', (string) $s));

        foreach (iterator_to_array($xpath->query('.//h1', $root)) as $h1) {
            // Judul ganda (sama dengan <h1> halaman) dibuang.
            if ($title && $normalize($h1->textContent) === $normalize($title)) {
                $h1->parentNode->removeChild($h1);

                continue;
            }
            $h2 = $dom->createElement('h2');
            while ($h1->firstChild) {
                $h2->appendChild($h1->firstChild);
            }
            $h1->parentNode->replaceChild($h2, $h1);
        }

        $toc = [];
        $used = [];
        foreach ($xpath->query('.//h2', $root) as $h2) {
            $text = Str::squish($h2->textContent);
            if ($text === '') {
                continue;
            }
            $id = Str::limit(Str::slug($text), 60, '') ?: 'bagian';
            $base = $id;
            for ($i = 2; isset($used[$id]); $i++) {
                $id = "{$base}-{$i}";
            }
            $used[$id] = true;
            $h2->setAttribute('id', $id);
            $toc[] = ['id' => $id, 'text' => $text];
        }

        foreach ($xpath->query('.//img', $root) as $index => $img) {
            static::enhanceImage($img, $sizes, eager: false);
        }

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return ['html' => $out, 'toc' => $toc];
    }

    private static function enhanceImage(DOMElement $img, string $sizes, bool $eager): void
    {
        $img->removeAttribute('style');
        $img->setAttribute('loading', $eager ? 'eager' : 'lazy');
        $img->setAttribute('decoding', 'async');

        $src = $img->getAttribute('src');
        if (! str_starts_with($src, '/storage/') && ! str_contains($src, '/storage/')) {
            return;
        }

        $meta = ImageVariants::meta(parse_url($src, PHP_URL_PATH));
        if (! $meta) {
            return;
        }
        if ($meta['width'] && $meta['height']) {
            $img->setAttribute('width', (string) $meta['width']);
            $img->setAttribute('height', (string) $meta['height']);
        }
        if ($meta['srcset']) {
            $img->setAttribute('srcset', $meta['srcset']);
            $img->setAttribute('sizes', $sizes);
        }
    }
}
