# Fuel Transaction Import Review

## Účel

Review workflow poskytuje dispečerovi přehled importních dávek ORLEN a MOL,
kontrolu přijatých, duplicitních, problematických a odmítnutých řádků
a auditované ruční opravy.

## Neměnnost zdrojových dat

`raw_payload` a původní `normalized_payload` zůstávají neměnné.
Oprava se ukládá jako samostatná revize v tabulce
`fuel_import_row_corrections`.

Každá revize obsahuje:

- původní efektivní hodnotu,
- opravenou hodnotu,
- povinný důvod,
- autora,
- čas vytvoření,
- pořadové číslo revize.

Revize se nepřepisují ani nemažou.

## Povolené opravy

Opravovat lze pouze řádky ve stavu `review` nebo `rejected`.
Řádky `accepted` a `duplicate` nelze tímto workflow měnit.

Nová oprava musí změnit alespoň jednu hodnotu.
Identická oprava je odmítnuta validační chybou a auditní revize nevznikne.

## Oprávnění a organizační rozsah

Čtení vyžaduje `compensation.view`.
Vytvoření opravy vyžaduje `users.manage`.

Importní dávka musí patřit do aktivního organizačního kontextu.
Cizí organizace nesmí dávku ani její řádky zobrazit nebo opravovat.

## Uživatelské rozhraní

Stránka `/settings/fuel-imports` nabízí:

- nahrání ORLEN CSV nebo MOL XLSX,
- seznam importních dávek,
- stavové souhrny,
- filtrování řádků,
- oddělené raw a normalizované hodnoty,
- poslední efektivní hodnotu,
- formulář auditované opravy,
- úplnou historii revizí.

## Databázová bezpečnost

Migrace vytváří pouze tabulku auditních revizí.
Testy používají jednorázovou databázi; persistentní produkční databáze
nesmí být při ověřování Sprintu 036 použita.