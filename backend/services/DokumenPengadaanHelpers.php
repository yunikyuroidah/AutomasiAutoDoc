<?php
declare(strict_types=1);

final class DokumenPengadaanHelpers
{
    public static function formatSpelledDate(?string $dateValue): ?string
    {
        try {
            if ($dateValue === null || trim((string)$dateValue) === '') {
                $date = new DateTimeImmutable('now');
            } else {
                $date = new DateTimeImmutable((string)$dateValue);
            }
        } catch (Throwable $e) {
            return null;
        }

        $days = [1 => 'Senin', 2 => 'Selasa', 3 => 'Rabu', 4 => 'Kamis', 5 => 'Jumat', 6 => 'Sabtu', 7 => 'Minggu'];

        $months = [1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April', 5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus', 9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'];

        $dayName = $days[(int)$date->format('N')] ?? null;
        $dayValue = (int)$date->format('j');
        $monthName = $months[(int)$date->format('n')] ?? null;
        $yearValue = (int)$date->format('Y');

        if ($dayName === null || $monthName === null) {
            return null;
        }

        $dayWords = trim(preg_replace('/\s+/', ' ', terbilang((float)$dayValue)) ?? '');
        $yearWords = trim(preg_replace('/\s+/', ' ', terbilang((float)$yearValue)) ?? '');

        if ($dayWords === '' || $yearWords === '') {
            return null;
        }

        return $dayName . ' Tanggal ' . $dayWords . ' Bulan ' . $monthName . ' Tahun ' . $yearWords;
    }

    public static function toFloat(string $value): float
    {
        if ($value === '') {
            return 0.0;
        }

        $normalized = str_replace(['.', ','], ['', '.'], preg_replace('/[^0-9,.-]/', '', $value));
        return is_numeric($normalized) ? (float)$normalized : 0.0;
    }

    public static function alphabetLabel(int $index): string
    {
        if ($index < 0) {
            return '';
        }

        $label = '';
        $number = $index + 1;
        while ($number > 0) {
            $number--;
            $label = chr(97 + ($number % 26)) . $label;
            $number = intdiv($number, 26);
        }

        return $label;
    }

    public static function injectReferenceRows(string $xml, array $items, string $providerName): string
    {
        $marker = strpos($xml, 'Harga tayang');
        if ($marker === false) {
            return $xml;
        }

        $tableStart = strrpos(substr($xml, 0, $marker), '<w:tbl');
        if ($tableStart === false) {
            return $xml;
        }

        $tableEnd = strpos($xml, '</w:tbl>', $marker);
        if ($tableEnd === false) {
            return $xml;
        }
        $tableEnd += strlen('</w:tbl>');

        $tableXml = substr($xml, $tableStart, $tableEnd - $tableStart);

        $headerEnd = strpos($tableXml, '</w:tr>');
        if ($headerEnd === false) {
            return $xml;
        }
        $headerEnd += strlen('</w:tr>');

        $closingPos = strrpos($tableXml, '</w:tbl>');
        if ($closingPos === false) {
            return $xml;
        }

        $header = substr($tableXml, 0, $headerEnd);
        $closing = substr($tableXml, $closingPos);

        if (!$items) {
            $items[] = ['product' => '-', 'price' => 0.0];
        }

        $rowsXml = self::buildReferenceRows($items, $providerName);
        $newTable = $header . $rowsXml . $closing;

        return substr($xml, 0, $tableStart) . $newTable . substr($xml, $tableEnd);
    }

    public static function buildReferenceRows(array $items, string $providerName): string
    {
        $providerText = trim($providerName) !== '' ? trim($providerName) : '-';
        $rows = [];

        foreach ($items as $index => $item) {
            $noText = (string)($index + 1);
            $productText = trim((string)($item['product'] ?? '-')) ?: '-';
            $priceValue = (float)($item['price'] ?? 0.0);
            $priceText = 'Rp. ' . PlaceholderHelper::plainCurrency($priceValue) . ',-';
            $priceLabelText = 'Harga tayang e-katalog : ' . $priceText;

            $rows[] = '<w:tr>'
                . '<w:trPr><w:trHeight w:val="816"/></w:trPr>'
                . self::buildTableCell('572', $noText)
                . self::buildTableCell('2400', $productText)
                . self::buildTableCell('1482', $priceText)
                . self::buildTableCell('1904', $providerText)
                . self::buildTableCell('2461', $priceLabelText)
                . '</w:tr>';
        }

        return implode('', $rows);
    }

    public static function buildTableCell(string $width, string $text): string
    {
        return '<w:tc>'
            . '<w:tcPr><w:tcW w:w="' . $width . '" w:type="dxa"/></w:tcPr>'
            . '<w:p><w:r><w:t xml:space="preserve">' . self::escapeXml($text) . '</w:t></w:r></w:p>'
            . '</w:tc>';
    }

    public static function escapeXml(string $value): string
    {
        return htmlspecialchars($value, ENT_QUOTES | ENT_XML1, 'UTF-8');
    }

    public static function replaceAcrossTags(string $xml, array $map, array $keys): string
    {
        foreach ($keys as $key) {
            if (!isset($map[$key])) {
                continue;
            }

            $chars = preg_split('//u', $key, -1, PREG_SPLIT_NO_EMPTY);
            if (!$chars) {
                continue;
            }

            $parts = array_map(static fn(string $char): string => preg_quote($char, '/'), $chars);
            $pattern = implode('(?:<[^>]+>)*\s*', $parts);
            $xml = preg_replace('/' . $pattern . '/iu', $map[$key], $xml);
        }

        return $xml;
    }

    public static function replaceStandaloneToken(string $xml, string $token, string $replacement): string
    {
        $chars = preg_split('//u', $token, -1, PREG_SPLIT_NO_EMPTY);
        if (!$chars) {
            return $xml;
        }

        $segments = array_map(static fn(string $char): string => preg_quote($char, '/'), $chars);
        $body = implode('(?:<[^>]+>)*\s*', $segments);
        $suffixGuard = '(?:<[^>]+>)*\s*(?:[^A-Za-z0-9]|$)';
        $pattern = '/(?<![A-Za-z0-9])' . $body . '(?=' . $suffixGuard . ')/iu';
        $result = preg_replace($pattern, $replacement, $xml);
        return $result === null ? $xml : $result;
    }
}
