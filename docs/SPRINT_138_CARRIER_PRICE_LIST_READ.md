# Sprint 138: ceníky externího dopravce

Správce externího dopravce čte zveřejněné ceníky, které pro jeho organizaci vede nadřazená organizace. Čtecí API `GET /api/v1/carrier/price-lists` vyžaduje aktivní členství a `people.manage` ve zvolené organizaci typu subcontractor. Výběr je omezen poskytovatelem, správou ceníku, zdrojovou a cílovou organizací vztahu. Vrací jen aktivní ceníky a aktivní verze; drafty ani ceníky jiných dopravců nejsou viditelné. Běžné správcovské API zůstává nepřístupné. Navigace „Moje ceníky“ pouze zobrazuje ceny a nepřidává zápisové akce.

Toto je první část Sprintu 138. Úpravy vlastních importovaných výkazů, přehled vyúčtování, statistiky a PHM jsou samostatné navazující změny a nesmějí používat neomezená finanční oprávnění.
