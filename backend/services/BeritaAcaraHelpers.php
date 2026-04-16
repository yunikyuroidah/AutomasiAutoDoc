<?php
declare(strict_types=1);

final class BeritaAcaraHelpers
{
    public static function makeStrikethrough(string $text): string
    {
        $strikeChar = "\xCC\xB6";
        $striked = '';
        for ($i = 0; $i < strlen($text); $i++) {
            $striked .= $text[$i] . $strikeChar;
        }
        return $striked;
    }

    public static function replaceAcrossTagsMulti(string $xml, array $map): string
    {
        foreach ($map as $needle => $replacement) {
            if (!is_string($needle) || $needle === '') {
                continue;
            }

            $chars = preg_split('//u', $needle, -1, PREG_SPLIT_NO_EMPTY);
            if (!$chars) {
                continue;
            }

            $parts = array_map(static fn(string $char): string => preg_quote($char, '/'), $chars);
            $pattern = implode('(?:<[^>]+>)*\s*', $parts);
            $candidate = preg_replace('/' . $pattern . '/iu', $replacement, $xml);
            if ($candidate !== null) {
                $xml = $candidate;
            }
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

    public static function formatDayName($dateValue): ?string
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
        return $days[(int)$date->format('N')] ?? null;
    }

    public static function formatNumericDate($dateValue): ?string
    {
        if ($dateValue === null || trim((string)$dateValue) === '') {
            return null;
        }

        $formatted = format_date_id((string)$dateValue);
        $normalized = trim((string)$formatted);
        return $normalized === '' ? null : $normalized;
    }

    public static function formatSpelledDate($dateValue): ?string
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
        $monthName = $months[(int)$date->format('n')] ?? null;
        $dayWords = self::buildNumberWords((int)$date->format('j'));
        $yearWords = self::buildNumberWords((int)$date->format('Y'));

        if ($dayName === null || $monthName === null || $dayWords === '' || $yearWords === '') {
            return null;
        }

        return $dayName . ' Tanggal ' . $dayWords . ' Bulan ' . $monthName . ' Tahun ' . $yearWords;
    }

    public static function buildNumberWords(int $value): string
    {
        $text = terbilang((float)$value);
        if (!is_string($text)) {
            return '';
        }

        $normalized = preg_replace('/\s+/', ' ', $text);
        if ($normalized === null) {
            return '';
        }

        $normalized = trim($normalized);
        if ($normalized === '') {
            return '';
        }

        return ucwords($normalized);
    }

    public static function replaceTerbilangParagraph(string $xml, string $text): string
    {
        $needle = 'TERBILANG';
        $offset = 0;
        $encoded = htmlspecialchars($text, ENT_QUOTES | ENT_XML1, 'UTF-8');

        for ($i = 0; $i < 5; $i++) {
            $pos = stripos($xml, $needle, $offset);
            if ($pos === false) break;

            $before = substr($xml, 0, $pos);
            $startP = strrpos($before, '<w:p');
            if ($startP === false) break;
            $openEnd = strpos($xml, '>', $startP);
            if ($openEnd === false) break;

            $endP = stripos($xml, '</w:p>', $pos);
            if ($endP === false) break;

            $prefix = substr($xml, 0, $openEnd + 1);
            $suffix = substr($xml, $endP);

            $run = '<w:r><w:t>TERBILANG: ' . $encoded . '</w:t></w:r>';
            $xml = $prefix . $run . $suffix;

            $offset = $openEnd + 1 + strlen($run) + 7;
        }

        return $xml;
    }
}
