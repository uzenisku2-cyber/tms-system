# Sprint 138: vlastní importované výkazy

Řidič externího dopravce může upravit provozní hodnoty vlastního importovaného draftu vedeného u master organizace. Nová cesta PATCH `/api/v1/daily-reports/{uuid}/carrier-import` vyžaduje řidičské oprávnění v aktivním členství dopravce. Pod zámkem výkazu znovu ověřuje řidiče, přiřazení dopravce i vztah master–dopravce k datu jízdy, stav draft a očekávanou verzi. Zapisuje novou verzi a událost pod původní organizací; autor a metoda původního importu zůstávají. Datum, číslo trasy, příplatek a smazání nepovoluje. Zobrazovací akce „Upravit“ je dostupná jen vlastnímu řidiči.

Provozní opravy se nesmějí provádět přímo SQL ani přes obecné PATCH původní organizace. Následující části Sprintu 138 (statistiky, PHM a vyúčtování) nejsou součástí tohoto balíčku.
