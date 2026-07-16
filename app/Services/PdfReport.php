<?php

declare(strict_types=1);

namespace App\Services;

use App\Core\Logger;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Renders report HTML to a PDF using Dompdf, with a common header (association
 * logo + name, report title, date range, generated-on timestamp).
 *
 * If Dompdf is not installed, falls back to streaming a print-optimised HTML
 * page so reports remain accessible.
 */
final class PdfReport
{
    public function __construct(
        private string $associationName,
        private ?string $logoAbsolutePath = null,
    ) {
    }

    /**
     * @param list<string> $columns
     * @param list<array<int|string,mixed>> $rows
     * @param array<string,string> $meta e.g. ['Date range' => '...']
     */
    public function stream(string $filename, string $title, array $columns, array $rows, array $meta = [], array $summary = []): never
    {
        $html = $this->buildHtml($title, $columns, $rows, $meta, $summary);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        if (class_exists(Dompdf::class)) {
            $options = new Options();
            $options->set('isRemoteEnabled', false);
            $options->set('defaultFont', 'DejaVu Sans');
            $dompdf = new Dompdf($options);
            $dompdf->loadHtml($html);
            $dompdf->setPaper('A4', 'portrait');
            $dompdf->render();
            header('Content-Type: application/pdf');
            header('Content-Disposition: attachment; filename="' . $this->safeName($filename, 'pdf') . '"');
            echo $dompdf->output();
            exit;
        }

        // Fallback: printable HTML.
        Logger::warning('Dompdf not installed; serving printable HTML fallback for report.');
        header('Content-Type: text/html; charset=UTF-8');
        echo $html . '<script>window.print&&setTimeout(function(){window.print()},300);</script>';
        exit;
    }

    /**
     * @param list<string> $columns
     * @param list<array<int|string,mixed>> $rows
     * @param array<string,string> $meta
     * @param array<string,string> $summary
     */
    public function buildHtml(string $title, array $columns, array $rows, array $meta = [], array $summary = []): string
    {
        $esc = static fn ($v) => htmlspecialchars((string) $v, ENT_QUOTES, 'UTF-8');
        $generated = date('d M Y, H:i');

        $logo = '';
        if ($this->logoAbsolutePath && is_file($this->logoAbsolutePath)) {
            $data = @file_get_contents($this->logoAbsolutePath);
            if ($data !== false) {
                $mime = (new \finfo(FILEINFO_MIME_TYPE))->buffer($data) ?: 'image/png';
                $logo = '<img src="data:' . $mime . ';base64,' . base64_encode($data) . '" style="height:48px;width:48px;object-fit:cover;border-radius:6px">';
            }
        }

        $metaHtml = '';
        foreach ($meta as $k => $v) {
            $metaHtml .= '<span style="margin-right:16px;color:#4b5563">' . $esc($k) . ': <strong>' . $esc($v) . '</strong></span>';
        }

        $thead = '';
        foreach ($columns as $c) {
            $thead .= '<th>' . $esc($c) . '</th>';
        }

        $tbody = '';
        foreach ($rows as $row) {
            $tbody .= '<tr>';
            foreach ($row as $cell) {
                $tbody .= '<td>' . $esc($cell) . '</td>';
            }
            $tbody .= '</tr>';
        }
        if ($rows === []) {
            $tbody = '<tr><td colspan="' . count($columns) . '" style="text-align:center;color:#9ca3af">No records found.</td></tr>';
        }

        $summaryHtml = '';
        if ($summary !== []) {
            $summaryHtml = '<table class="summary"><tr>';
            foreach ($summary as $k => $v) {
                $summaryHtml .= '<td><span class="label">' . $esc($k) . '</span><span class="value">' . $esc($v) . '</span></td>';
            }
            $summaryHtml .= '</tr></table>';
        }

        $assocName = $esc($this->associationName);
        $titleEsc = $esc($title);
        $year = date('Y');

        return <<<HTML
        <!DOCTYPE html>
        <html><head><meta charset="utf-8"><style>
            * { font-family: 'DejaVu Sans', sans-serif; }
            body { color: #111827; font-size: 11px; margin: 0; }
            .header { border-bottom: 2px solid #047857; padding-bottom: 10px; margin-bottom: 12px; }
            .brand { display: table; width: 100%; }
            .brand .logo { display: table-cell; width: 56px; vertical-align: middle; }
            .brand .name { display: table-cell; vertical-align: middle; }
            .brand .name h1 { color: #047857; font-size: 18px; margin: 0; }
            .brand .name .sub { color: #6b7280; font-size: 12px; }
            .title { font-size: 15px; font-weight: bold; margin: 10px 0 4px; }
            .meta { font-size: 10px; margin-bottom: 8px; }
            table.data { width: 100%; border-collapse: collapse; margin-top: 8px; }
            table.data th { background: #ecfdf5; color: #065f46; text-align: left; padding: 6px 8px; border: 1px solid #d1fae5; font-size: 10px; text-transform: uppercase; }
            table.data td { padding: 5px 8px; border: 1px solid #e5e7eb; }
            table.summary { width: 100%; margin: 12px 0; border-collapse: collapse; }
            table.summary td { background: #f9fafb; border: 1px solid #e5e7eb; padding: 8px; }
            table.summary .label { display: block; color: #6b7280; font-size: 9px; text-transform: uppercase; }
            table.summary .value { display: block; font-size: 14px; font-weight: bold; color: #047857; }
            .footer { margin-top: 16px; color: #9ca3af; font-size: 9px; text-align: center; }
        </style></head><body>
            <div class="header">
                <div class="brand">
                    <div class="logo">{$logo}</div>
                    <div class="name">
                        <h1>{$assocName}</h1>
                        <div class="sub">Habitract — Membership Management</div>
                    </div>
                </div>
            </div>
            <div class="title">{$titleEsc}</div>
            <div class="meta">{$metaHtml}<span style="color:#4b5563">Generated: <strong>{$generated}</strong></span></div>
            {$summaryHtml}
            <table class="data"><thead><tr>{$thead}</tr></thead><tbody>{$tbody}</tbody></table>
            <div class="footer">Generated by Habitract · &copy; {$year} SportsByA Tech (OPC) Private Limited</div>
        </body></html>
        HTML;
    }

    private function safeName(string $name, string $ext): string
    {
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name) ?? 'report';
        return str_ends_with($name, '.' . $ext) ? $name : $name . '.' . $ext;
    }
}
