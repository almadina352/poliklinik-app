<?php

namespace App\Support;

use RuntimeException;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use ZipArchive;

/**
 * Penulis file .xlsx sederhana tanpa dependensi tambahan (hanya ext-zip bawaan PHP).
 * Sheet tunggal: baris judul tebal berlatar abu, kolom lebar otomatis, header dibekukan,
 * angka disimpan sebagai angka (bisa dijumlah di Excel), teks disimpan sebagai teks
 * (isi sel tidak pernah dievaluasi sebagai rumus).
 */
class XlsxExport
{
    /**
     * @param  list<string>  $headings
     * @param  iterable<list<scalar|null>>  $rows
     */
    public static function download(string $filename, string $sheetName, array $headings, iterable $rows): BinaryFileResponse
    {
        $path = static::build($sheetName, $headings, $rows);

        return response()->download($path, $filename, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    /** Membangun file dan mengembalikan path file sementara. */
    public static function build(string $sheetName, array $headings, iterable $rows): string
    {
        $path = tempnam(sys_get_temp_dir(), 'xlsx');
        $zip = new ZipArchive();

        if ($zip->open($path, ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('Tidak dapat membuat file Excel.');
        }

        $zip->addFromString('[Content_Types].xml', static::contentTypes());
        $zip->addFromString('_rels/.rels', static::rootRels());
        $zip->addFromString('xl/workbook.xml', static::workbook($sheetName));
        $zip->addFromString('xl/_rels/workbook.xml.rels', static::workbookRels());
        $zip->addFromString('xl/styles.xml', static::styles());
        $zip->addFromString('xl/worksheets/sheet1.xml', static::sheet($headings, $rows));
        $zip->close();

        return $path;
    }

    private static function sheet(array $headings, iterable $rows): string
    {
        $widths = array_map(fn ($h) => mb_strlen((string) $h) + 4, $headings);
        $xml = '';
        $r = 1;

        $xml .= '<row r="1">';
        foreach (array_values($headings) as $i => $h) {
            $xml .= static::cell($i, 1, (string) $h, 1);
        }
        $xml .= '</row>';

        foreach ($rows as $row) {
            $r++;
            $xml .= '<row r="' . $r . '">';
            foreach (array_values($row) as $i => $v) {
                $len = is_scalar($v) ? mb_strlen((string) $v) : 0;
                $widths[$i] = max($widths[$i] ?? 8, min($len + 2, 50));
                $xml .= static::cell($i, $r, $v, is_int($v) || is_float($v) ? 2 : 0);
            }
            $xml .= '</row>';
        }

        $cols = '';
        foreach ($widths as $i => $w) {
            $n = $i + 1;
            $cols .= '<col min="' . $n . '" max="' . $n . '" width="' . min(max($w, 8), 50) . '" customWidth="1"/>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<sheetViews><sheetView workbookViewId="0"><pane ySplit="1" topLeftCell="A2" activePane="bottomLeft" state="frozen"/></sheetView></sheetViews>'
            . '<cols>' . $cols . '</cols>'
            . '<sheetData>' . $xml . '</sheetData></worksheet>';
    }

    private static function cell(int $col, int $row, mixed $value, int $style): string
    {
        $ref = static::column($col) . $row;

        if ($value === null || $value === '') {
            return '<c r="' . $ref . '" s="' . $style . '"/>';
        }

        if (is_int($value) || is_float($value)) {
            return '<c r="' . $ref . '" s="' . $style . '"><v>' . $value . '</v></c>';
        }

        return '<c r="' . $ref . '" s="' . $style . '" t="inlineStr"><is><t xml:space="preserve">'
            . static::escape((string) $value) . '</t></is></c>';
    }

    /** 0 => A, 25 => Z, 26 => AA ... */
    private static function column(int $index): string
    {
        $s = '';
        for ($n = $index + 1; $n > 0; $n = intdiv($n - 1, 26)) {
            $s = chr(65 + ($n - 1) % 26) . $s;
        }

        return $s;
    }

    private static function escape(string $text): string
    {
        // Buang karakter kontrol yang tidak valid di XML 1.0
        $text = preg_replace('/[^\x{9}\x{A}\x{D}\x{20}-\x{D7FF}\x{E000}-\x{FFFD}\x{10000}-\x{10FFFF}]/u', '', $text) ?? '';

        return htmlspecialchars($text, ENT_XML1 | ENT_QUOTES, 'UTF-8');
    }

    private static function contentTypes(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            . '<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            . '<Default Extension="xml" ContentType="application/xml"/>'
            . '<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            . '<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            . '<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            . '</Types>';
    }

    private static function rootRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            . '</Relationships>';
    }

    private static function workbook(string $sheetName): string
    {
        $name = mb_substr(preg_replace('/[\[\]:*?\/\\\\]/', ' ', $sheetName) ?: 'Sheet1', 0, 31);

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            . '<sheets><sheet name="' . static::escape($name) . '" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private static function workbookRels(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            . '<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            . '<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            . '</Relationships>';
    }

    /** Style: 0 = biasa, 1 = header (tebal + abu), 2 = angka (#,##0). */
    private static function styles(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            . '<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            . '<fonts count="2"><font><sz val="11"/><name val="Calibri"/></font><font><b/><sz val="11"/><name val="Calibri"/></font></fonts>'
            . '<fills count="3"><fill><patternFill patternType="none"/></fill><fill><patternFill patternType="gray125"/></fill>'
            . '<fill><patternFill patternType="solid"><fgColor rgb="FFE2E8F0"/><bgColor indexed="64"/></patternFill></fill></fills>'
            . '<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            . '<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            . '<cellXfs count="3">'
            . '<xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/>'
            . '<xf numFmtId="0" fontId="1" fillId="2" borderId="0" xfId="0" applyFont="1" applyFill="1"/>'
            . '<xf numFmtId="3" fontId="0" fillId="0" borderId="0" xfId="0" applyNumberFormat="1"/>'
            . '</cellXfs>'
            . '<cellStyles count="1"><cellStyle name="Normal" xfId="0" builtinId="0"/></cellStyles>'
            . '</styleSheet>';
    }
}