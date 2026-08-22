<?php

declare(strict_types=1);

namespace Tests\Support;

use RuntimeException;
use ZipArchive;

final class DepotWorkbookFactory
{
    /**
     * @param  list<list<int|float|string|null>>  $rows
     * @param  array<string, array{formula:string, value:int|float|string}>  $formulas
     */
    public static function create(
        array $rows,
        array $formulas = [],
        string $sheetName = 'Depot',
    ): string {
        $path = tempnam(
            sys_get_temp_dir(),
            'depot-import-',
        );

        if (! is_string($path)) {
            throw new RuntimeException(
                'Temporary workbook path could not be created.',
            );
        }

        $zip = new ZipArchive;

        if (
            $zip->open(
                $path,
                ZipArchive::CREATE | ZipArchive::OVERWRITE,
            ) !== true
        ) {
            throw new RuntimeException(
                'Temporary workbook could not be opened.',
            );
        }

        try {
            $zip->addFromString(
                '[Content_Types].xml',
                <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types">
  <Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/>
  <Default Extension="xml" ContentType="application/xml"/>
  <Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/>
  <Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/>
</Types>
XML,
            );
            $zip->addFromString(
                'xl/workbook.xml',
                sprintf(
                    <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships">
  <sheets><sheet name="%s" sheetId="1" r:id="rId1"/></sheets>
</workbook>
XML,
                    self::xml($sheetName),
                ),
            );
            $zip->addFromString(
                'xl/_rels/workbook.xml.rels',
                <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships">
  <Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/>
</Relationships>
XML,
            );
            $zip->addFromString(
                'xl/worksheets/sheet1.xml',
                self::sheetXml($rows, $formulas),
            );
        } finally {
            $zip->close();
        }

        return $path;
    }

    /**
     * @param  list<list<int|float|string|null>>  $rows
     * @param  array<string, array{formula:string, value:int|float|string}>  $formulas
     */
    private static function sheetXml(
        array $rows,
        array $formulas,
    ): string {
        $sheetData = '';

        foreach ($rows as $rowIndex => $values) {
            $rowNumber = $rowIndex + 1;
            $cells = '';

            foreach ($values as $columnIndex => $value) {
                if ($value === null) {
                    continue;
                }

                $reference = self::columnLetter($columnIndex + 1).$rowNumber;

                if (array_key_exists($reference, $formulas)) {
                    $formula = $formulas[$reference];
                    $cells .= sprintf(
                        '<c r="%s"><f>%s</f><v>%s</v></c>',
                        $reference,
                        self::xml($formula['formula']),
                        self::xml((string) $formula['value']),
                    );

                    continue;
                }

                if (is_int($value) || is_float($value)) {
                    $cells .= sprintf(
                        '<c r="%s"><v>%s</v></c>',
                        $reference,
                        self::xml((string) $value),
                    );

                    continue;
                }

                $cells .= sprintf(
                    '<c r="%s" t="inlineStr"><is><t xml:space="preserve">%s</t></is></c>',
                    $reference,
                    self::xml($value),
                );
            }

            $sheetData .= sprintf(
                '<row r="%d">%s</row>',
                $rowNumber,
                $cells,
            );
        }

        return sprintf(
            <<<'XML'
<?xml version="1.0" encoding="UTF-8" standalone="yes"?>
<worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main">
  <sheetData>%s</sheetData>
</worksheet>
XML,
            $sheetData,
        );
    }

    private static function xml(string $value): string
    {
        return htmlspecialchars(
            $value,
            ENT_XML1 | ENT_QUOTES,
            'UTF-8',
        );
    }

    private static function columnLetter(int $column): string
    {
        $letter = '';

        while ($column > 0) {
            $column--;
            $letter = chr(65 + ($column % 26)).$letter;
            $column = intdiv($column, 26);
        }

        return $letter;
    }
}
