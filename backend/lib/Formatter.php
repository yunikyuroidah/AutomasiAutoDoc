<?php
declare(strict_types=1);

function format_rupiah(float $value): string
{
    return 'Rp ' . number_format($value, 0, ',', '.');
}

function format_month_label(string $dateValue): string
{
    $timestamp = strtotime($dateValue);
    if ($timestamp === false) {
        return strtoupper($dateValue);
    }

    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $month = (int) date('n', $timestamp);
    $year = date('Y', $timestamp);

    return strtoupper($months[$month] . ' ' . $year);
}

function format_date_id(string $dateValue, bool $uppercase = false): string
{
    if ($dateValue === '') {
        return '';
    }

    $timestamp = strtotime($dateValue);
    if ($timestamp === false) {
        return $dateValue;
    }

    $months = [
        1 => 'Januari',
        2 => 'Februari',
        3 => 'Maret',
        4 => 'April',
        5 => 'Mei',
        6 => 'Juni',
        7 => 'Juli',
        8 => 'Agustus',
        9 => 'September',
        10 => 'Oktober',
        11 => 'November',
        12 => 'Desember',
    ];

    $day = (int) date('j', $timestamp);
    $month = (int) date('n', $timestamp);
    $year = date('Y', $timestamp);

    $result = sprintf('%d %s %s', $day, $months[$month] ?? $month, $year);

    return $uppercase ? mb_strtoupper($result, 'UTF-8') : $result;
}

function terbilang(float $value): string
{
    $value = (int) round($value);

    if ($value === 0) {
        return 'Nol';
    }

    $units = ['', 'Satu', 'Dua', 'Tiga', 'Empat', 'Lima', 'Enam', 'Tujuh', 'Delapan', 'Sembilan', 'Sepuluh', 'Sebelas'];

    $build = function ($number) use (&$build, $units): string {
        if ($number < 12) {
            return $units[$number];
        }
        if ($number < 20) {
            return $build($number - 10) . ' Belas';
        }
        if ($number < 100) {
            return $build(intval($number / 10)) . ' Puluh ' . $build($number % 10);
        }
        if ($number < 200) {
            return 'Seratus ' . $build($number - 100);
        }
        if ($number < 1000) {
            return $build(intval($number / 100)) . ' Ratus ' . $build($number % 100);
        }
        if ($number < 2000) {
            return 'Seribu ' . $build($number - 1000);
        }
        if ($number < 1_000_000) {
            return $build(intval($number / 1000)) . ' Ribu ' . $build($number % 1000);
        }
        if ($number < 1_000_000_000) {
            return $build(intval($number / 1_000_000)) . ' Juta ' . $build($number % 1_000_000);
        }
        if ($number < 1_000_000_000_000) {
            return $build(intval($number / 1_000_000_000)) . ' Milyar ' . $build($number % 1_000_000_000);
        }

        return $build(intval($number / 1_000_000_000_000)) . ' Triliun ' . $build($number % 1_000_000_000_000);
    };

    $result = trim(preg_replace('/\s+/', ' ', $build($value)) ?? '');

    return $result !== '' ? $result : 'Nol';
}
