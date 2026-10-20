<?php

namespace App\Services\WordPress;

use DOMDocument;
use DOMElement;
use DOMNode;
use DOMXPath;

/**
 * Menyesuaikan HTML artikel WordPress agar tampil benar di renderer RichEditor:
 * membuang daftar isi plugin, script & atribut tampilan, mengunduh gambar/lampiran
 * ke disk lokal, mengubah figure menjadi paragraf, dan menjadikan tautan internal relatif.
 */
class ContentCleaner
{
    private const REMOVE_XPATH = [
        '//script', '//style', '//noscript', '//form', '//button', '//svg',
        '//*[@id="ez-toc-container"]',
        '//*[contains(concat(" ", normalize-space(@class), " "), " ez-toc-container ")]',
        '//*[contains(concat(" ", normalize-space(@class), " "), " ez-toc-section ")]',
        '//*[contains(concat(" ", normalize-space(@class), " "), " ez-toc-section-end ")]',
        '//*[contains(concat(" ", normalize-space(@class), " "), " sharedaddy ")]',
    ];

    private const SHORTCODE = '/\[\/?(?:caption|gallery|embed|audio|video|playlist|contact-form-7|wpforms|toc|ez-toc|vc_\w+|et_pb_\w+|elementor[\w-]*|su_\w+)(?:\s[^\]]*)?\]/i';

    public function __construct(private MediaDownloader $media, private WordPressClient $client) {}

    public function clean(string $html, ?string $permalink = null, ?string $title = null): string
    {
        $html = preg_replace(self::SHORTCODE, '', $html);
        if (trim(strip_tags($html, '<img><iframe>')) === '') {
            return '';
        }

        $dom = new DOMDocument;
        $dom->loadHTML('<?xml encoding="utf-8"?><div id="__wp_root">'.$html.'</div>', LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD | LIBXML_NOERROR | LIBXML_NOWARNING);
        $xpath = new DOMXPath($dom);
        $root = $dom->getElementById('__wp_root');

        foreach (self::REMOVE_XPATH as $query) {
            foreach (iterator_to_array($xpath->query($query, $root)) as $node) {
                $node->parentNode?->removeChild($node);
            }
        }

        $this->demoteHeadings($dom, $xpath, $root, $title);

        foreach (iterator_to_array($xpath->query('.//iframe', $root)) as $iframe) {
            $this->replaceIframe($dom, $iframe);
        }

        foreach (iterator_to_array($xpath->query('.//img', $root)) as $img) {
            $this->rewriteImage($img);
        }

        foreach (iterator_to_array($xpath->query('.//figure', $root)) as $figure) {
            $this->unwrapFigure($dom, $figure);
        }

        foreach (iterator_to_array($xpath->query('.//a[@href]', $root)) as $link) {
            $this->rewriteLink($link, $permalink);
        }

        $this->stripAttributes($xpath, $root);
        $this->removeEmptyParagraphs($xpath, $root);

        $out = '';
        foreach ($root->childNodes as $child) {
            $out .= $dom->saveHTML($child);
        }

        return trim(preg_replace("/\n{3,}/", "\n\n", $out));
    }

    /**
     * Halaman sudah punya satu <h1> (judul artikel): <h1> di isi yang sama dengan judul
     * dibuang, <h1> lainnya diturunkan menjadi <h2>.
     */
    private function demoteHeadings(DOMDocument $dom, DOMXPath $xpath, DOMElement $root, ?string $title): void
    {
        $normalize = fn (?string $s) => mb_strtolower(preg_replace('/[^\p{L}\p{N}]+/u', '', html_entity_decode((string) $s)));

        foreach (iterator_to_array($xpath->query('.//h1', $root)) as $h1) {
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
    }

    private function rewriteImage(DOMElement $img): void
    {
        $src = $img->getAttribute('src') ?: $img->getAttribute('data-src');
        if ($src === '' || str_starts_with($src, 'data:')) {
            $img->parentNode?->removeChild($img);

            return;
        }

        $src = $this->absolute($src);
        if ($this->media->isSourceUpload($src) && ($path = $this->media->fetch($src))) {
            $src = MediaDownloader::publicPath($path);
        }

        $img->setAttribute('src', $src);
        $img->setAttribute('alt', trim(html_entity_decode($img->getAttribute('alt'))));
    }

    /** <figure><img><figcaption>…</figcaption></figure> → <p><img></p><p><em>…</em></p> */
    private function unwrapFigure(DOMDocument $dom, DOMElement $figure): void
    {
        $parent = $figure->parentNode;
        $caption = null;
        foreach ($figure->getElementsByTagName('figcaption') as $node) {
            $caption = trim($node->textContent);
        }

        foreach (iterator_to_array($figure->getElementsByTagName('img')) as $img) {
            $p = $dom->createElement('p');
            $p->appendChild($img);
            $parent->insertBefore($p, $figure);
        }

        foreach (iterator_to_array($figure->getElementsByTagName('table')) as $table) {
            $parent->insertBefore($table, $figure);
        }

        if ($caption) {
            $p = $dom->createElement('p');
            $em = $dom->createElement('em');
            $em->appendChild($dom->createTextNode($caption));
            $p->appendChild($em);
            $parent->insertBefore($p, $figure);
        }

        $parent->removeChild($figure);
    }

    /** Renderer membuang iframe: video YouTube dijadikan tautan, iframe lain dibuang. */
    private function replaceIframe(DOMDocument $dom, DOMElement $iframe): void
    {
        $src = $iframe->getAttribute('src');
        if (preg_match('~youtube(?:-nocookie)?\.com/embed/([\w-]{6,})~', $src, $m)) {
            $p = $dom->createElement('p');
            $a = $dom->createElement('a', 'Tonton video di YouTube');
            $a->setAttribute('href', 'https://www.youtube.com/watch?v='.$m[1]);
            $p->appendChild($a);
            $iframe->parentNode->replaceChild($p, $iframe);

            return;
        }

        $iframe->parentNode?->removeChild($iframe);
    }

    private function rewriteLink(DOMElement $link, ?string $permalink): void
    {
        $href = trim($link->getAttribute('href'));
        if ($href === '' || $href === '#') {
            $this->unwrap($link);

            return;
        }

        $absolute = $this->absolute($href);
        $host = preg_replace('/^www\./', '', (string) parse_url($absolute, PHP_URL_HOST));
        if ($host !== $this->client->host()) {
            $link->setAttribute('href', $href);
            $link->setAttribute('rel', 'noopener');

            return;
        }

        // Anchor daftar isi ke artikel itu sendiri: daftar isi sudah dibuang.
        if ($permalink && rtrim(strtok($absolute, '#'), '/') === rtrim($permalink, '/')) {
            $this->unwrap($link);

            return;
        }

        if ($this->media->isSourceUpload($absolute) && ($path = $this->media->fetch($absolute))) {
            $link->setAttribute('href', MediaDownloader::publicPath($path));

            return;
        }

        // Tautan internal lain dijadikan relatif; alamat lama diarahkan 301 oleh PageController.
        $relative = (parse_url($absolute, PHP_URL_PATH) ?: '/').(($q = parse_url($absolute, PHP_URL_QUERY)) ? '?'.$q : '').(($f = parse_url($absolute, PHP_URL_FRAGMENT)) ? '#'.$f : '');
        $link->setAttribute('href', $relative);
    }

    private function stripAttributes(DOMXPath $xpath, DOMElement $root): void
    {
        $keep = ['href', 'src', 'alt', 'title', 'rel', 'target', 'colspan', 'rowspan'];

        foreach ($xpath->query('.//*', $root) as $element) {
            foreach (iterator_to_array($element->attributes) as $attribute) {
                if (! in_array(strtolower($attribute->name), $keep, true)) {
                    $element->removeAttribute($attribute->name);
                }
            }
            if ($element->hasAttribute('href') && preg_match('/^\s*javascript:/i', $element->getAttribute('href'))) {
                $element->removeAttribute('href');
            }
        }
    }

    private function removeEmptyParagraphs(DOMXPath $xpath, DOMElement $root): void
    {
        foreach (iterator_to_array($xpath->query('.//p | .//span | .//div', $root)) as $node) {
            $text = trim(str_replace("\u{00A0}", ' ', $node->textContent));
            if ($text === '' && $xpath->query('.//img | .//table | .//a', $node)->length === 0) {
                $node->parentNode?->removeChild($node);
            }
        }
    }

    private function unwrap(DOMNode $node): void
    {
        $parent = $node->parentNode;
        while ($node->firstChild) {
            $parent->insertBefore($node->firstChild, $node);
        }
        $parent->removeChild($node);
    }

    private function absolute(string $url): string
    {
        if (str_starts_with($url, '//')) {
            return 'https:'.$url;
        }

        return str_starts_with($url, '/') ? $this->client->baseUrl().$url : $url;
    }
}
