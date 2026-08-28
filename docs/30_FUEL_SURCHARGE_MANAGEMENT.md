# Palivové příplatky a lokální náhled

## Účel

Palivové příplatky jsou samostatný finanční vztah. Nemění dlouhodobé ceníky odběratelů, vlastních řidičů ani externích dopravců.

Správce může evidovat sazbu fakturovanou odběrateli a samostatně rozhodnout, zda a v jaké individuální výši ji přidělí vlastnímu řidiči nebo externímu dopravci. Příjemce není povinný a nulová sazba je platná.

## Výpočet a ochrana marže

- Základem je počet skutečně ujetých kilometrů evidovaných u trasy.
- Odběratelská i výplatní sazba se zadává bez DPH.
- Výplata příjemce používá jeho individuální sazbu.
- Rozdíl mezi odběratelskou a výplatní sazbou je interní marže.
- Řidič ani externí dopravce nesmí vidět odběratelskou sazbu nebo interní marži.
- Příplatek bez příjemce je platný a nevytváří žádnou výplatu.

## Průvodce

Správa v PHM používá šest kroků: odběratel a platnost, odběratelská sazba, volitelní příjemci, jejich individuální sazby, potvrzení skutečných kilometrů a závěrečný souhrn.

Historie používá české názvy stavů a český formát data. Technické hodnoty API zůstávají stabilní a překládají se pouze v prezentační vrstvě.

## Opakovatelný localhost

Finální stav větší funkční části musí být fyzicky ověřitelný na localhostu. Izolovaný náhled lze po základních migracích a vytvoření hlavní organizace naplnit příkazem:

```powershell
docker exec s039-r5-preview-app php artisan db:seed --class=Database\\Seeders\\FuelSurchargePreviewSeeder --force
```

Seeder je idempotentní, smí běžet pouze v prostředí `local` nebo `testing` a připraví:

- správce `preview.s039@drayvia.local`,
- ukázkového odběratele,
- vlastního řidiče včetně aktivního členství a pracovního přiřazení,
- externího dopravce,
- oprávnění a dohledový rozsah potřebný pro fyzické ověření.

Přihlašovací údaje lokálního náhledu jsou `preview.s039@drayvia.local` / `Nahled-S039!`. Seeder se nesmí spouštět nad produkční databází.
