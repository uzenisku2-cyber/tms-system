# Sprint 130: Přehled odběratelských pohledávek

Čtecí API `GET /api/v1/customer-receivables` je dostupné hlavní organizaci s oprávněním `compensation.manage`. Zahrnuje pouze její vydané odběratelské faktury (`approved` nebo `closed`) s komerční identitou směru `receivable`. Návrhy a jiné doklady se nevykazují. Zákazník ani jiná organizace do přehledu nevstoupí.

Úhrada a zůstatek se počítají z aktuálně aktivních bankovních přiřazení. Storno již úhradu nezapočítává. Parametr `as_of` slouží výhradně k posouzení splatnosti proti zvolenému kalendářnímu dni; nepředstavuje historický stav úhrad. Výchozí den je aktuální datum v `Europe/Prague`. Faktura splatná právě v tento den ještě není po splatnosti. Filtry `customer_organization_id` a `state` (`open`, `unpaid`, `partially_paid`, `paid`, `overdue`) ovlivní stránkované položky i souhrny. Souhrny se vedou samostatně podle odběratele a měny bez sčítání různých měn.

Panel Finance zobrazuje částky celkem, uhrazeno, zbývá a po splatnosti. Výsledek nemění doklady, přiřazení, PDF ani účetní zápisy. Vystavená faktura zůstává neměnná.
