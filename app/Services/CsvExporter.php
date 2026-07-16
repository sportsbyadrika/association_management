<?php

declare(strict_types=1);

namespace App\Services;

/**
 * Streams a CSV download using native fputcsv.
 */
final class CsvExporter
{
    /**
     * @param list<string> $headers
     * @param list<array<int|string,mixed>> $rows  each row aligned to $headers
     */
    public static function download(string $filename, array $headers, array $rows): never
    {
        // Discard any buffered output so the CSV is clean.
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . self::safeName($filename) . '"');
        header('Pragma: no-cache');
        header('Expires: 0');

        $out = fopen('php://output', 'wb');
        // UTF-8 BOM for spreadsheet compatibility.
        fwrite($out, "\xEF\xBB\xBF");
        // Explicit escape char ('') — required from PHP 8.4 to avoid deprecation.
        fputcsv($out, $headers, ',', '"', '');
        foreach ($rows as $row) {
            fputcsv($out, array_values($row), ',', '"', '');
        }
        fclose($out);
        exit;
    }

    private static function safeName(string $name): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?? 'export.csv';
        return str_ends_with($name, '.csv') ? $name : $name . '.csv';
    }
}
