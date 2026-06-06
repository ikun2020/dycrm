<?php

namespace App\Support;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

class SimpleXlsx
{
    /**
     * @return array<int, array<int, string|null>>
     */
    public static function read(string $path): array
    {
        $zip = new ZipArchive;

        if ($zip->open($path) !== true) {
            throw new RuntimeException('无法打开 Excel 文件。');
        }

        try {
            $sharedStrings = self::sharedStrings($zip);
            $sheetPath = self::firstWorksheetPath($zip);
            $sheetXml = $zip->getFromName($sheetPath);

            if ($sheetXml === false) {
                throw new RuntimeException('Excel 文件中没有可读取的工作表。');
            }

            $sheet = simplexml_load_string($sheetXml);

            if (! $sheet instanceof SimpleXMLElement) {
                throw new RuntimeException('Excel 工作表解析失败。');
            }

            $rows = [];

            foreach ($sheet->sheetData->row as $row) {
                $values = [];

                foreach ($row->c as $cell) {
                    $attributes = $cell->attributes();
                    $reference = (string) ($attributes['r'] ?? '');
                    $index = self::columnIndex($reference);

                    if ($index === null) {
                        $index = count($values);
                    }

                    $values[$index] = self::cellValue($cell, $sharedStrings);
                }

                if ($values !== []) {
                    ksort($values);
                    $rowValues = [];
                    $lastIndex = max(array_keys($values));

                    for ($index = 0; $index <= $lastIndex; $index++) {
                        $rowValues[] = $values[$index] ?? null;
                    }

                    $rows[] = $rowValues;
                }
            }

            return $rows;
        } finally {
            $zip->close();
        }
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    public static function write(array $rows, string $path): void
    {
        $zip = new ZipArchive;

        if ($zip->open($path, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
            throw new RuntimeException('无法创建 Excel 文件。');
        }

        try {
            $zip->addFromString('[Content_Types].xml', self::contentTypesXml());
            $zip->addFromString('_rels/.rels', self::rootRelsXml());
            $zip->addFromString('xl/workbook.xml', self::workbookXml());
            $zip->addFromString('xl/_rels/workbook.xml.rels', self::workbookRelsXml());
            $zip->addFromString('xl/styles.xml', self::stylesXml());
            $zip->addFromString('xl/worksheets/sheet1.xml', self::worksheetXml($rows));
        } finally {
            $zip->close();
        }
    }

    /**
     * @return array<int, string>
     */
    private static function sharedStrings(ZipArchive $zip): array
    {
        $xml = $zip->getFromName('xl/sharedStrings.xml');

        if ($xml === false) {
            return [];
        }

        $sharedXml = simplexml_load_string($xml);

        if (! $sharedXml instanceof SimpleXMLElement) {
            return [];
        }

        $strings = [];

        foreach ($sharedXml->si as $item) {
            if (isset($item->t)) {
                $strings[] = (string) $item->t;

                continue;
            }

            $text = '';

            foreach ($item->r as $run) {
                $text .= (string) $run->t;
            }

            $strings[] = $text;
        }

        return $strings;
    }

    private static function firstWorksheetPath(ZipArchive $zip): string
    {
        $workbookXml = $zip->getFromName('xl/workbook.xml');
        $relsXml = $zip->getFromName('xl/_rels/workbook.xml.rels');

        if ($workbookXml === false || $relsXml === false) {
            return 'xl/worksheets/sheet1.xml';
        }

        $workbook = simplexml_load_string($workbookXml);
        $rels = simplexml_load_string($relsXml);

        if (! $workbook instanceof SimpleXMLElement || ! $rels instanceof SimpleXMLElement) {
            return 'xl/worksheets/sheet1.xml';
        }

        $namespaces = $workbook->getNamespaces(true);
        $relNamespace = $namespaces['r'] ?? 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';
        $firstSheet = $workbook->sheets->sheet[0] ?? null;

        if ($firstSheet === null) {
            return 'xl/worksheets/sheet1.xml';
        }

        $relationshipId = (string) $firstSheet->attributes($relNamespace)['id'];

        foreach ($rels->Relationship as $relationship) {
            $attributes = $relationship->attributes();

            if ((string) $attributes['Id'] === $relationshipId) {
                $target = ltrim((string) $attributes['Target'], '/');

                return str_starts_with($target, 'xl/')
                    ? $target
                    : 'xl/'.$target;
            }
        }

        return 'xl/worksheets/sheet1.xml';
    }

    /**
     * @param  array<int, string>  $sharedStrings
     */
    private static function cellValue(SimpleXMLElement $cell, array $sharedStrings): ?string
    {
        $attributes = $cell->attributes();
        $type = (string) ($attributes['t'] ?? '');

        if ($type === 's') {
            $index = (int) $cell->v;

            return $sharedStrings[$index] ?? null;
        }

        if ($type === 'inlineStr') {
            return (string) ($cell->is->t ?? '');
        }

        if (isset($cell->v)) {
            return (string) $cell->v;
        }

        return null;
    }

    private static function columnIndex(string $reference): ?int
    {
        if (! preg_match('/^([A-Z]+)/', $reference, $matches)) {
            return null;
        }

        $index = 0;

        foreach (str_split($matches[1]) as $letter) {
            $index = $index * 26 + (ord($letter) - 64);
        }

        return $index - 1;
    }

    /**
     * @param  array<int, array<int, mixed>>  $rows
     */
    private static function worksheetXml(array $rows): string
    {
        $sheetRows = [];

        foreach (array_values($rows) as $rowIndex => $row) {
            $cells = [];

            foreach (array_values($row) as $columnIndex => $value) {
                $reference = self::columnName($columnIndex + 1).($rowIndex + 1);
                $cells[] = '<c r="'.$reference.'" t="inlineStr"><is><t xml:space="preserve">'.self::escape((string) $value).'</t></is></c>';
            }

            $sheetRows[] = '<row r="'.($rowIndex + 1).'">'.implode('', $cells).'</row>';
        }

        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheetData>'.implode('', $sheetRows).'</sheetData></worksheet>';
    }

    private static function columnName(int $index): string
    {
        $name = '';

        while ($index > 0) {
            $index--;
            $name = chr(65 + ($index % 26)).$name;
            $index = intdiv($index, 26);
        }

        return $name;
    }

    private static function escape(string $value): string
    {
        return htmlspecialchars($value, ENT_XML1 | ENT_COMPAT, 'UTF-8');
    }

    private static function contentTypesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">'
            .'<Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>'
            .'<Default Extension="xml" ContentType="application/xml"/>'
            .'<Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>'
            .'<Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>'
            .'<Override PartName="/xl/styles.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.styles+xml"/>'
            .'</Types>';
    }

    private static function rootRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/>'
            .'</Relationships>';
    }

    private static function workbookXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" '
            .'xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">'
            .'<sheets><sheet name="达人档案" sheetId="1" r:id="rId1"/></sheets></workbook>';
    }

    private static function workbookRelsXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">'
            .'<Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>'
            .'<Relationship Id="rId2" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/styles" Target="styles.xml"/>'
            .'</Relationships>';
    }

    private static function stylesXml(): string
    {
        return '<?xml version="1.0" encoding="UTF-8" standalone="yes"?>'
            .'<styleSheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">'
            .'<fonts count="1"><font><sz val="11"/><name val="Calibri"/></font></fonts>'
            .'<fills count="1"><fill><patternFill patternType="none"/></fill></fills>'
            .'<borders count="1"><border><left/><right/><top/><bottom/><diagonal/></border></borders>'
            .'<cellStyleXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0"/></cellStyleXfs>'
            .'<cellXfs count="1"><xf numFmtId="0" fontId="0" fillId="0" borderId="0" xfId="0"/></cellXfs>'
            .'</styleSheet>';
    }
}
