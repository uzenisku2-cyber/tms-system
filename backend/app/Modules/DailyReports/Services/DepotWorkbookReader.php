<?php

declare(strict_types=1);

namespace App\Modules\DailyReports\Services;

use App\Modules\DailyReports\Exceptions\DepotWorkbookException;
use DOMDocument;
use DOMElement;
use DOMXPath;
use ZipArchive;

final class DepotWorkbookReader
{
    private const MAIN_NS = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    private const DOCUMENT_REL_NS = 'http://schemas.openxmlformats.org/officeDocument/2006/relationships';

    private const PACKAGE_REL_NS = 'http://schemas.openxmlformats.org/package/2006/relationships';

    private const MAX_ARCHIVE_ENTRIES = 2048;

    private const MAX_TOTAL_UNCOMPRESSED_BYTES = 134_217_728;

    private const MAX_XML_BYTES = 33_554_432;

    private const MAX_SHEETS = 32;

    private const MAX_ROWS_PER_SHEET = 25_000;

    private const MAX_COLUMNS = 512;

    private const MAX_CELL_TEXT_LENGTH = 10_000;

    /**
     * @return array{
     *     file_sha256:string,
     *     formula_cell_count:int,
     *     sheets:list<array{
     *         name:string,
     *         path:string,
     *         rows:list<array{
     *             row:int,
     *             cells:array<int, array{
     *                 reference:string,
     *                 value:?string,
     *                 formula:bool
     *             }>
     *         }>
     *     }>
     * }
     */
    public function read(string $path): array
    {
        if (! is_file($path) || ! is_readable($path)) {
            throw new DepotWorkbookException(
                'Zdrojový sešit není dostupný pro bezpečné čtení.',
            );
        }

        $hash = hash_file('sha256', $path);

        if (! is_string($hash)) {
            throw new DepotWorkbookException(
                'Kontrolní otisk zdrojového sešitu nelze vypočítat.',
            );
        }

        $zip = new ZipArchive;
        $opened = $zip->open($path);

        if ($opened !== true) {
            throw new DepotWorkbookException(
                'Soubor není platný nepoškozený sešit XLSX.',
            );
        }

        try {
            $this->assertSafeArchive($zip);
            $this->assertReadableArchiveEntries($zip);

            $sharedStrings = $this->sharedStrings($zip);
            $sheetDefinitions = $this->sheetDefinitions($zip);
            $sheets = [];
            $formulaCellCount = 0;

            foreach ($sheetDefinitions as $definition) {
                $sheet = $this->worksheet(
                    $zip,
                    $definition['name'],
                    $definition['path'],
                    $sharedStrings,
                );

                $formulaCellCount += $sheet['formula_cell_count'];
                unset($sheet['formula_cell_count']);
                $sheets[] = $sheet;
            }

            return [
                'file_sha256' => strtoupper($hash),
                'formula_cell_count' => $formulaCellCount,
                'sheets' => $sheets,
            ];
        } finally {
            $zip->close();
        }
    }

    private function assertReadableArchiveEntries(ZipArchive $zip): void
    {
        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);

            if (! is_array($stat)) {
                throw new DepotWorkbookException(
                    'Interní položku sešitu nelze ověřit.',
                );
            }

            $name = (string) ($stat['name'] ?? '');
            $expectedSize = (int) ($stat['size'] ?? 0);

            if (str_ends_with($name, '/') && $expectedSize === 0) {
                continue;
            }

            $stream = $zip->getStream($name);

            if (! is_resource($stream)) {
                throw new DepotWorkbookException(
                    'Interní položku sešitu nelze bezpečně přečíst.',
                );
            }

            $readSize = 0;

            try {
                while (! feof($stream)) {
                    $chunk = fread($stream, 1_048_576);

                    if ($chunk === false) {
                        throw new DepotWorkbookException(
                            'Interní položka sešitu je poškozená.',
                        );
                    }

                    if ($chunk === '') {
                        if (! feof($stream)) {
                            throw new DepotWorkbookException(
                                'Interní položku sešitu nelze dočíst.',
                            );
                        }

                        break;
                    }

                    $readSize += strlen($chunk);

                    if ($readSize > $expectedSize) {
                        throw new DepotWorkbookException(
                            'Velikost interní položky sešitu nesouhlasí.',
                        );
                    }
                }
            } finally {
                fclose($stream);
            }

            if ($readSize !== $expectedSize) {
                throw new DepotWorkbookException(
                    'Interní položka sešitu není úplná.',
                );
            }
        }
    }

    private function assertSafeArchive(ZipArchive $zip): void
    {
        if (
            $zip->numFiles < 1
            || $zip->numFiles > self::MAX_ARCHIVE_ENTRIES
        ) {
            throw new DepotWorkbookException(
                'Sešit má nepovolený počet interních souborů.',
            );
        }

        $totalSize = 0;

        for ($index = 0; $index < $zip->numFiles; $index++) {
            $stat = $zip->statIndex($index);

            if (! is_array($stat)) {
                throw new DepotWorkbookException(
                    'Interní strukturu sešitu nelze ověřit.',
                );
            }

            $name = (string) ($stat['name'] ?? '');

            if (! $this->isSafeArchivePath($name)) {
                throw new DepotWorkbookException(
                    'Sešit obsahuje nepovolenou interní cestu.',
                );
            }

            if (
                mb_strtolower($name, 'UTF-8')
                === 'xl/vbaproject.bin'
            ) {
                throw new DepotWorkbookException(
                    'Sešit s makry není pro import povolen.',
                );
            }

            $size = (int) ($stat['size'] ?? 0);
            $compressedSize = (int) ($stat['comp_size'] ?? 0);
            $encryptionMethod = (int) ($stat['encryption_method'] ?? 0);

            if ($size < 0 || $compressedSize < 0 || $encryptionMethod !== 0) {
                throw new DepotWorkbookException(
                    'Sešit obsahuje nepodporovanou interní položku.',
                );
            }

            if (
                $compressedSize > 0
                && $size > 1_048_576
                && ($size / $compressedSize) > 500
            ) {
                throw new DepotWorkbookException(
                    'Sešit překročil bezpečný kompresní poměr.',
                );
            }

            $totalSize += $size;

            if ($totalSize > self::MAX_TOTAL_UNCOMPRESSED_BYTES) {
                throw new DepotWorkbookException(
                    'Rozbalený obsah sešitu překračuje bezpečný limit.',
                );
            }
        }

        foreach (
            [
                '[Content_Types].xml',
                'xl/workbook.xml',
                'xl/_rels/workbook.xml.rels',
            ] as $requiredEntry
        ) {
            if ($zip->locateName($requiredEntry) === false) {
                throw new DepotWorkbookException(
                    'Soubor nemá povinnou strukturu sešitu XLSX.',
                );
            }
        }
    }

    private function isSafeArchivePath(string $name): bool
    {
        if (
            $name === ''
            || str_contains($name, "\0")
            || str_contains($name, '\\')
            || str_starts_with($name, '/')
            || preg_match('/^[A-Za-z]:/', $name) === 1
        ) {
            return false;
        }

        foreach (explode('/', $name) as $part) {
            if ($part === '..') {
                return false;
            }
        }

        return true;
    }

    /** @return list<string> */
    private function sharedStrings(ZipArchive $zip): array
    {
        if ($zip->locateName('xl/sharedStrings.xml') === false) {
            return [];
        }

        $document = $this->xmlDocument(
            $zip,
            'xl/sharedStrings.xml',
        );
        $xpath = $this->mainXPath($document);
        $nodes = $xpath->query('/x:sst/x:si');

        if ($nodes === false) {
            throw new DepotWorkbookException(
                'Sdílené texty sešitu nelze načíst.',
            );
        }

        $strings = [];

        foreach ($nodes as $node) {
            if (! $node instanceof DOMElement) {
                continue;
            }

            $parts = $xpath->query('.//x:t', $node);
            $value = '';

            if ($parts !== false) {
                foreach ($parts as $part) {
                    $value .= $part->textContent;
                }
            }

            $strings[] = $this->boundedCellText($value);
        }

        return $strings;
    }

    /** @return list<array{name:string, path:string}> */
    private function sheetDefinitions(ZipArchive $zip): array
    {
        $workbook = $this->xmlDocument($zip, 'xl/workbook.xml');
        $relationships = $this->xmlDocument(
            $zip,
            'xl/_rels/workbook.xml.rels',
        );

        $relationshipXPath = new DOMXPath($relationships);
        $relationshipXPath->registerNamespace(
            'p',
            self::PACKAGE_REL_NS,
        );

        $targets = [];
        $relationshipNodes = $relationshipXPath->query(
            '/p:Relationships/p:Relationship',
        );

        if ($relationshipNodes === false) {
            throw new DepotWorkbookException(
                'Vazby listů sešitu nelze načíst.',
            );
        }

        foreach ($relationshipNodes as $relationship) {
            if (! $relationship instanceof DOMElement) {
                continue;
            }

            if (
                $relationship->getAttribute('TargetMode')
                === 'External'
            ) {
                continue;
            }

            $id = $relationship->getAttribute('Id');
            $target = $this->relationshipTarget(
                $relationship->getAttribute('Target'),
            );

            if ($id !== '' && $target !== null) {
                $targets[$id] = $target;
            }
        }

        $workbookXPath = $this->mainXPath($workbook);
        $sheetNodes = $workbookXPath->query(
            '/x:workbook/x:sheets/x:sheet',
        );

        if ($sheetNodes === false) {
            throw new DepotWorkbookException(
                'Seznam listů sešitu nelze načíst.',
            );
        }

        $sheets = [];

        foreach ($sheetNodes as $sheet) {
            if (! $sheet instanceof DOMElement) {
                continue;
            }

            $state = $sheet->getAttribute('state');

            if (in_array($state, ['hidden', 'veryHidden'], true)) {
                continue;
            }

            $relationshipId = $sheet->getAttributeNS(
                self::DOCUMENT_REL_NS,
                'id',
            );
            $target = $targets[$relationshipId] ?? null;

            if (! is_string($target) || $zip->locateName($target) === false) {
                throw new DepotWorkbookException(
                    'Viditelný list odkazuje na chybějící interní soubor.',
                );
            }

            $sheets[] = [
                'name' => $sheet->getAttribute('name'),
                'path' => $target,
            ];

            if (count($sheets) > self::MAX_SHEETS) {
                throw new DepotWorkbookException(
                    'Sešit překročil podporovaný počet viditelných listů.',
                );
            }
        }

        if ($sheets === []) {
            throw new DepotWorkbookException(
                'Sešit neobsahuje žádný viditelný list.',
            );
        }

        return $sheets;
    }

    private function relationshipTarget(string $target): ?string
    {
        $target = rawurldecode(trim($target));

        if ($target === '' || str_contains($target, '\\')) {
            return null;
        }

        $candidate = str_starts_with($target, '/')
            ? ltrim($target, '/')
            : 'xl/'.$target;

        if (! $this->isSafeArchivePath($candidate)) {
            return null;
        }

        return $candidate;
    }

    /**
     * @param  list<string>  $sharedStrings
     * @return array{
     *     name:string,
     *     path:string,
     *     rows:list<array{
     *         row:int,
     *         cells:array<int, array{
     *             reference:string,
     *             value:?string,
     *             formula:bool
     *         }>
     *     }>,
     *     formula_cell_count:int
     * }
     */
    private function worksheet(
        ZipArchive $zip,
        string $name,
        string $path,
        array $sharedStrings,
    ): array {
        $document = $this->xmlDocument($zip, $path);
        $xpath = $this->mainXPath($document);
        $rowNodes = $xpath->query('/x:worksheet/x:sheetData/x:row');

        if ($rowNodes === false) {
            throw new DepotWorkbookException(
                "Řádky listu {$name} nelze načíst.",
            );
        }

        if ($rowNodes->length > self::MAX_ROWS_PER_SHEET) {
            throw new DepotWorkbookException(
                "List {$name} překročil podporovaný počet řádků.",
            );
        }

        $rows = [];
        $formulaCellCount = 0;

        foreach ($rowNodes as $rowNode) {
            if (! $rowNode instanceof DOMElement) {
                continue;
            }

            $rowNumber = (int) $rowNode->getAttribute('r');

            if ($rowNumber < 1) {
                continue;
            }

            $cells = [];
            $cellNodes = $xpath->query('./x:c', $rowNode);

            if ($cellNodes === false) {
                continue;
            }

            foreach ($cellNodes as $cellNode) {
                if (! $cellNode instanceof DOMElement) {
                    continue;
                }

                $reference = $cellNode->getAttribute('r');
                $column = $this->columnNumber($reference);

                if ($column === null || $column > self::MAX_COLUMNS) {
                    continue;
                }

                $formulaNodes = $xpath->query('./x:f', $cellNode);
                $formula = $formulaNodes !== false
                    && $formulaNodes->length > 0;

                if ($formula) {
                    $formulaCellCount++;
                }

                $cells[$column] = [
                    'reference' => $reference,
                    'value' => $this->cellValue(
                        $xpath,
                        $cellNode,
                        $sharedStrings,
                    ),
                    'formula' => $formula,
                ];
            }

            if ($cells !== []) {
                ksort($cells);
                $rows[] = [
                    'row' => $rowNumber,
                    'cells' => $cells,
                ];
            }
        }

        return [
            'name' => $name,
            'path' => $path,
            'rows' => $rows,
            'formula_cell_count' => $formulaCellCount,
        ];
    }

    /** @param  list<string>  $sharedStrings */
    private function cellValue(
        DOMXPath $xpath,
        DOMElement $cell,
        array $sharedStrings,
    ): ?string {
        $type = $cell->getAttribute('t');

        if ($type === 'inlineStr') {
            $parts = $xpath->query('./x:is//x:t', $cell);
            $value = '';

            if ($parts !== false) {
                foreach ($parts as $part) {
                    $value .= $part->textContent;
                }
            }

            return $value === ''
                ? null
                : $this->boundedCellText($value);
        }

        $valueNodes = $xpath->query('./x:v', $cell);
        $valueNode = $valueNodes === false
            ? null
            : $valueNodes->item(0);

        if ($valueNode === null) {
            return null;
        }

        $value = $valueNode->textContent;

        if ($type === 's') {
            if (! ctype_digit($value)) {
                throw new DepotWorkbookException(
                    'Sešit obsahuje neplatný odkaz na sdílený text.',
                );
            }

            $index = (int) $value;

            if (! array_key_exists($index, $sharedStrings)) {
                throw new DepotWorkbookException(
                    'Sešit odkazuje na chybějící sdílený text.',
                );
            }

            return $sharedStrings[$index];
        }

        return $this->boundedCellText($value);
    }

    private function columnNumber(string $reference): ?int
    {
        if (
            preg_match('/^([A-Z]{1,3})[1-9][0-9]*$/', $reference, $matches)
            !== 1
        ) {
            return null;
        }

        $column = 0;

        foreach (str_split($matches[1]) as $character) {
            $column = ($column * 26) + (ord($character) - 64);
        }

        return $column;
    }

    private function xmlDocument(
        ZipArchive $zip,
        string $path,
    ): DOMDocument {
        $stat = $zip->statName($path);

        if (
            ! is_array($stat)
            || (int) ($stat['size'] ?? 0) > self::MAX_XML_BYTES
        ) {
            throw new DepotWorkbookException(
                "Interní XML {$path} překračuje bezpečný limit.",
            );
        }

        $xml = $zip->getFromName($path);

        if (! is_string($xml) || $xml === '') {
            throw new DepotWorkbookException(
                "Interní XML {$path} nelze načíst.",
            );
        }

        if (stripos($xml, '<!DOCTYPE') !== false) {
            throw new DepotWorkbookException(
                'Sešit obsahuje nepovolenou XML deklaraci typu dokumentu.',
            );
        }

        $document = new DOMDocument;
        $previous = libxml_use_internal_errors(true);

        try {
            $loaded = $document->loadXML(
                $xml,
                LIBXML_NONET
                | LIBXML_NOBLANKS
                | LIBXML_NOCDATA
                | LIBXML_COMPACT,
            );
        } finally {
            libxml_clear_errors();
            libxml_use_internal_errors($previous);
        }

        if (! $loaded) {
            throw new DepotWorkbookException(
                "Interní XML {$path} není platné.",
            );
        }

        return $document;
    }

    private function mainXPath(DOMDocument $document): DOMXPath
    {
        $xpath = new DOMXPath($document);
        $xpath->registerNamespace('x', self::MAIN_NS);

        return $xpath;
    }

    private function boundedCellText(string $value): string
    {
        if (
            mb_strlen($value, 'UTF-8')
            > self::MAX_CELL_TEXT_LENGTH
        ) {
            throw new DepotWorkbookException(
                'Sešit obsahuje nepovoleně dlouhou hodnotu buňky.',
            );
        }

        return $value;
    }
}
