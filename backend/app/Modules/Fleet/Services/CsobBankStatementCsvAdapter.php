<?php

declare(strict_types=1);

namespace App\Modules\Fleet\Services;

use DateTimeImmutable;
use Illuminate\Validation\ValidationException;
use RuntimeException;

final class CsobBankStatementCsvAdapter
{
    public const VERSION = 'csob-csv-v1';

    private const HEADERS = ['číslo účtu', 'datum zaúčtování', 'částka', 'měna', 'zůstatek', 'číslo protiúčtu', 'kód banky protiúčtu', 'jméno protistrany', 'adresa protistrany', 'konstantní symbol', 'variabilní symbol', 'specifický symbol', 'označení operace', 'název trvalého příkazu', 'vlastní poznámka', 'zpráva', 'kategorie', 'původní transakce', 'kurz', 'E2E identifikace', 'reference plátce', 'původní plátce', 'konečný příjemce', 'ID transakce'];

    /** @return list<array{source_row:int,raw:array<string,string>,normalized:array<string,string|null>}> */
    public function parse(string $path): array
    {
        $bytes = file_get_contents($path);
        if (! is_string($bytes)) {
            throw new RuntimeException('ČSOB CSV reading failed.');
        }
        if (! str_starts_with($bytes, "\xEF\xBB\xBF")) {
            throw ValidationException::withMessages(['file' => ['ČSOB CSV must be UTF-8 with BOM.']]);
        }
        $bytes = substr($bytes, 3);
        $lines = preg_split('/\R/u', $bytes);
        if (! is_array($lines)) {
            throw new RuntimeException('ČSOB CSV line parsing failed.');
        }
        $headerIndex = null;
        foreach ($lines as $i => $line) {
            if (substr_count($line, ';') === 23) {
                $headerIndex = $i;
                break;
            }
        }
        if ($headerIndex !== 2) {
            throw ValidationException::withMessages(['file' => ['ČSOB CSV must contain the expected two-line preamble.']]);
        }
        $stream = fopen('php://temp', 'r+');
        if ($stream === false) {
            throw new RuntimeException('Temporary stream failed.');
        } fwrite($stream, implode("\n", array_slice($lines, $headerIndex)));
        rewind($stream);
        $header = fgetcsv($stream, null, ';', '"', '');
        if ($header !== self::HEADERS) {
            fclose($stream);
            throw ValidationException::withMessages(['file' => ['ČSOB CSV header contract differs.']]);
        }
        $result = [];
        $offset = 0;
        while (($values = fgetcsv($stream, null, ';', '"', '')) !== false) {
            if ($values === [null]) {
                continue;
            }if (count($values) !== 24) {
                fclose($stream);
                throw ValidationException::withMessages(['file' => ['ČSOB CSV row width differs.']]);
            }$raw = array_combine(self::HEADERS, array_map(static fn ($v): string => trim((string) $v), $values));
            $result[] = ['source_row' => $headerIndex + $offset + 2, 'raw' => $raw, 'normalized' => $this->normalize($raw)];
            $offset++;
        }fclose($stream);

        return $result;
    }

    /** @param array<string,string> $r @return array<string,string|null> */
    private function normalize(array $r): array
    {
        $date = DateTimeImmutable::createFromFormat('!d.m.Y', $r['datum zaúčtování']);
        $amount = str_replace(["\u{00A0}", ' '], '', $r['částka']);
        $amount = str_replace(',', '.', $amount);
        if (! $date instanceof DateTimeImmutable || ! is_numeric($amount) || (float) $amount === 0.0 || $r['ID transakce'] === '' || strlen($r['měna']) !== 3) {
            throw ValidationException::withMessages(['file' => ['ČSOB CSV row contains invalid required values.']]);
        }
        $counterparty = $r['číslo protiúčtu'];
        if ($counterparty !== '' && $r['kód banky protiúčtu'] !== '') {
            $counterparty .= '/'.$r['kód banky protiúčtu'];
        }

        return ['bank_statement_reference' => $r['původní transakce'] ?: null, 'direction' => (float) $amount < 0 ? 'debit' : 'credit', 'booked_at' => $date->format('Y-m-d'), 'value_date' => null, 'amount' => number_format(abs((float) $amount), 2, '.', ''), 'currency' => strtoupper($r['měna']), 'account_identifier' => $r['číslo účtu'], 'counterparty_name' => $r['jméno protistrany'] ?: null, 'counterparty_account_identifier' => $counterparty ?: null, 'variable_symbol' => $r['variabilní symbol'] ?: null, 'message' => $r['zpráva'] ?: null, 'original_source_reference' => $r['ID transakce']];
    }
}
