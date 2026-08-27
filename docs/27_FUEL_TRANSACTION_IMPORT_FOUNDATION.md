# Fuel Transaction Import Foundation v1.0

## Supported real source formats

- ORLEN CSV: UTF-8 BOM, semicolon delimiter, Czech decimal comma, 27-column transaction export.
- MOL XLSX: visible sheet `Podrobný report o transakcích`, 32 columns, numeric Excel date values and an unnamed quantity-unit column normalized as `Jednotka množství`.

The provider source is never rewritten. Every row stores its original values, normalized values, source row number, validation messages and deterministic fingerprint.

## Identity and deduplication

Each batch is unique by owner organization, provider and file SHA-256. Each transaction is unique by owner organization, provider and a fingerprint made from provider identifier, timestamp, card identifier, station, product, quantity, gross amount and currency. Provider transaction identifiers remain preserved even when they are insufficient as a unique key.

## Historical matching

Card identifiers are treated as text. Provider whitespace and NBSP characters are removed before matching. A transaction is matched only to the card assignment effective at `occurred_at`. Unknown cards, no valid assignment and conflicting assignments produce a review state; they are not silently assigned to the current driver or vehicle.

## Monetary precision

Quantities and unit prices use six decimal places. Monetary source fields use six decimal places so MOL values such as five-decimal invoiced amounts are preserved. Currency is mandatory and stored with every transaction.

## Security and authorization

Imports require the authenticated organization middleware and `users.manage`. Read access requires `compensation.view`. XLSX parsing reuses the bounded, macro-rejecting and XXE-protected internal workbook reader. Source files are traceable through their metadata and hash; the binary file is not stored by this foundation.
