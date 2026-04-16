<?php
declare(strict_types=1);

final class LembarKegiatanHelpers
{
    private const WORD_NS = 'http://schemas.openxmlformats.org/wordprocessingml/2006/main';

    public static function applyPlaceholders(DOMDocument $dom, array $payload): void
    {
        $xpath = new DOMXPath($dom);
        $xpath->registerNamespace('w', self::WORD_NS);
        $runs = $xpath->query('//w:r[w:rPr/w:color[@w:val="EE0000"]]');

        if ($runs === false || $runs->length === 0) {
            return;
        }

        $counters = [
            'spesifikasi' => 0,
            'spesifikasi_jumlah' => 0,
            'harga_satuan' => 0,
            'pagu_anggaran' => 0,
        ];

        $pendingTerbilang = false;
        $items = $payload['items'] ?? [];

        $defaults = [
            'daftar', 'program', 'kegiatan', 'sub_kegiatan', 'nama_kepdin', 'namekepdin', 'nama_bendahara', 'namebendahara', 'nama_pembuat_komitmen', 'namepembuat',
        ];

        for ($i = 0; $i < $runs->length; $i++) {
            $run = $runs->item($i);
            if (!$run) continue;

            $group = [$run];
            $curr = $run;
            while (true) {
                $next = $curr->nextSibling;
                if (!$next || $next->nodeType !== XML_ELEMENT_NODE || $next->localName !== 'r') break;
                $colorNodes = $next->getElementsByTagNameNS(self::WORD_NS, 'color');
                $hasRed = false;
                foreach ($colorNodes as $cn) {
                    $val = $cn->getAttribute('w:val') ?: $cn->getAttributeNS(self::WORD_NS, 'val');
                    if (strtoupper((string)$val) === 'EE0000') { $hasRed = true; break; }
                }
                if ($hasRed) { $group[] = $next; $curr = $next; continue; }
                break;
            }

            if (count($group) > 1) {
                $i += count($group) - 1;
            }

            $raw = '';
            foreach ($group as $g) { $raw .= self::extractText($g); }
            $text = trim($raw);

            if ($text === '') {
                foreach ($group as $g) { self::setRunText($g, ''); self::normalizeRunColor($g); }
                continue;
            }

            if ($text === '(') {
                if ($pendingTerbilang) {
                    $terbilang = $payload['terbilang'] ?? '';
                    self::setRunText($group[0], $terbilang !== '' ? '(' . $terbilang . ')' : '');
                    for ($j = 1; $j < count($group); $j++) self::setRunText($group[$j], '');
                    $pendingTerbilang = false;
                } else {
                    foreach ($group as $g) self::setRunText($g, '');
                }
                foreach ($group as $g) self::normalizeRunColor($g);
                continue;
            }

            if (PlaceholderHelper::shouldDropRawToken($text)) {
                foreach ($group as $g) { self::setRunText($g, ''); self::normalizeRunColor($g); }
                continue;
            }

            $instructions = PlaceholderHelper::extractInstructions($text);
            $token = PlaceholderHelper::normalizeToken($text);

            if ($token === '') {
                foreach ($group as $g) { self::setRunText($g, ''); self::normalizeRunColor($g); }
                continue;
            }

            $value = self::resolvePlaceholderValue($token, $payload, $items, $counters, $pendingTerbilang);

            if ($value !== null) {
                if (PlaceholderHelper::shouldUppercaseToken($token, $instructions, $defaults)) {
                    $value = mb_strtoupper($value, 'UTF-8');
                }
                self::setRunText($group[0], $value);
                for ($j = 1; $j < count($group); $j++) self::setRunText($group[$j], '');
                foreach ($group as $g) self::normalizeRunColor($g);
                continue;
            }

            foreach ($group as $g) { self::setRunText($g, ''); self::normalizeRunColor($g); }
        }
    }

    private static function extractText(DOMElement $run): string
    {
        $text = '';
        foreach ($run->getElementsByTagNameNS(self::WORD_NS, 't') as $node) { $text .= $node->nodeValue; }
        return $text;
    }

    private static function setRunText(DOMElement $run, string $value): void
    {
        $textNodes = $run->getElementsByTagNameNS(self::WORD_NS, 't');
        if ($textNodes->length === 0) {
            $doc = $run->ownerDocument;
            $t = $doc->createElementNS(self::WORD_NS, 'w:t');
            $t->appendChild($doc->createTextNode($value));
            $run->appendChild($t);
            return;
        }

        $first = $textNodes->item(0);
        if ($first !== null) { $first->textContent = $value; }
        for ($i = 1; $i < $textNodes->length; $i++) {
            $node = $textNodes->item($i);
            if ($node !== null && $node->parentNode) { $node->parentNode->removeChild($node); }
        }
    }

    private static function normalizeRunColor(DOMElement $run): void
    {
        $doc = $run->ownerDocument;
        if (!$doc) return;

        $rPr = null;
        $rPrNodes = $run->getElementsByTagNameNS(self::WORD_NS, 'rPr');
        if ($rPrNodes->length > 0) { $rPr = $rPrNodes->item(0); }

        if (!$rPr) { $rPr = $doc->createElementNS(self::WORD_NS, 'w:rPr'); $run->insertBefore($rPr, $run->firstChild); }

        $colorNodes = $rPr->getElementsByTagNameNS(self::WORD_NS, 'color');
        $toRemove = [];
        foreach ($colorNodes as $colorNode) { $toRemove[] = $colorNode; }
        foreach ($toRemove as $colorNode) {
            if ($colorNode !== null && $colorNode->parentNode === $rPr) { $rPr->removeChild($colorNode); }
        }

        $color = $doc->createElementNS(self::WORD_NS, 'w:color');
        $color->setAttributeNS(self::WORD_NS, 'w:val', '000000');
        $rPr->appendChild($color);
    }

    private static function resolvePlaceholderValue(
        string $token,
        array $payload,
        array $items,
        array &$counters,
        bool &$pendingTerbilang
    ): ?string {
        switch ($token) {
            case 'daftar': return $payload['daftar'] ?? '';
            case 'program': return $payload['program'] ?? '';
            case 'kegiatan': return $payload['activity'] ?? '';
            case 'sub_kegiatan': return $payload['sub_activity'] ?? '';
            case 'kode_rekening': return $payload['kode_rekening'] ?? '';
            case 'bulan': return $payload['bulan_label'] ?? '';
            case 'uraian': return $items[0]['uraian'] ?? '';
            case 'spesifikasi': $index = $counters['spesifikasi']++; return $items[$index]['spesifikasi'] ?? '';
            case 'spesifikasi_jumlah': $index = $counters['spesifikasi_jumlah']++; return $items[$index]['spesifikasi_jumlah'] ?? '';
            case 'harga_satuan': $index = $counters['harga_satuan']++; $value = (float)($items[$index]['harga_satuan'] ?? 0); return PlaceholderHelper::plainCurrency($value);
            case 'pagu_anggaran': $index = $counters['pagu_anggaran']++; $value = (float)($items[$index]['jumlah_uang'] ?? 0); return PlaceholderHelper::plainCurrency($value);
            case 'total': $pendingTerbilang = true; return $payload['total_plain'] ?? '';
            case 'namekepdin': return $payload['kepala']['name'] ?? '';
            case 'nip_kepdin': return $payload['kepala']['nip'] ?? '';
            case 'namebendahara': return $payload['bendahara']['name'] ?? '';
            case 'nipbendahara': return $payload['bendahara']['nip'] ?? '';
            case 'namepembuat': return $payload['pembuat']['name'] ?? '';
            case 'nip_pembuat_komitmen': return $payload['pembuat']['nip'] ?? '';
            default: return null;
        }
    }
}
