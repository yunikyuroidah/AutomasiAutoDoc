<?php
declare(strict_types=1);

final class PlaceholderHelper
{
    private const WORD_IGNORED = [
        'di',
        'kepdin',
        'bendahara',
        'pembuat_komitmen',
        'penyedia',
        'spesifika',
        'lembar_kegiatan',
        'spesifikasi_anggaran',
        'penulisan',
        'bilangan',
        'dengan',
        'huruf',
        'dari',
        'total)',
        'baris',
        'baris_',
        'baris_pertama',
        'pertama',
        'kedua',
        'garis',
        'bawah',
        'dan',
        ')',
    ];

    public static function shouldDropRawToken(string $text): bool
    {
        $normalized = mb_strtolower(trim($text), 'UTF-8');

        if ($normalized === '') {
            return true;
        }

        if (in_array($normalized, self::WORD_IGNORED, true)) {
            return true;
        }

        return str_starts_with($normalized, '(baris') || str_starts_with($normalized, '(garis');
    }

    public static function normalizeToken(string $text): string
    {
        $text = trim($text);
        if ($text === '') {
            return '';
        }

        $text = trim($text, "<> ");
        $text = preg_replace('/\([^)]*\)/u', '', $text);
        $text = preg_replace('/\s+di\s+[a-z0-9_]+$/iu', '', $text);
        $text = preg_replace('/[^a-z0-9_]+$/iu', '', $text);

        return trim(mb_strtolower($text, 'UTF-8'));
    }

    public static function extractInstructions(string $text): array
    {
        $flags = [
            'uppercase' => false,
            'underline' => false,
            'bold' => false,
        ];

        if (preg_match('/\(([^)]+)\)/u', $text, $matches)) {
            $content = mb_strtolower($matches[1], 'UTF-8');
            if (str_contains($content, 'kapital')) {
                $flags['uppercase'] = true;
            }
            if (str_contains($content, 'garis bawah')) {
                $flags['underline'] = true;
            }
            if (str_contains($content, 'bold')) {
                $flags['bold'] = true;
            }
        }

        return $flags;
    }

    public static function shouldUppercaseToken(string $token, array $flags, array $defaults = []): bool
    {
        if (!empty($flags['uppercase'])) {
            return true;
        }

        if ($defaults && in_array($token, $defaults, true)) {
            return true;
        }

        return false;
    }

    public static function plainCurrency(float $value): string
    {
        return number_format($value, 0, ',', '.');
    }
}
