# Sprint 129: Přiřazení bankovní úhrady k vydané faktuře

Vydaná odběratelská faktura se páruje ručním potvrzením konkrétní kreditní bankovní položky. Musí souhlasit organizace, měna, variabilní symbol a přijímající IBAN uložený ve vydané faktuře. Párování vyžaduje revizi bankovní položky a vlastní idempotentní klíč. Částka nesmí překročit zbývající hodnotu faktury ani sdílenou volnou kapacitu bankovní položky. Opakované volání se stejným klíčem vrátí původní záznam.

Přiřazení je samostatný záznam s auditní událostí. Storno zachová historii a vrátí kapacitu bankovní položky i neuhrazenou část faktury. Stav `unpaid`, `partially_paid` či `paid` se vypočítá ze součtu aktivních přiřazení; nemění vydaný doklad, PDF ani jeho komerční identitu. Bankovní detail uvádí fakturační přiřazení spolu s ostatními typy alokací. Přístup k úhradám má pouze vydavatel.

API: `GET/POST /api/v1/customer-invoices/{customerInvoice}/bank-payments`, `POST /api/v1/customer-invoices/{customerInvoice}/bank-payments/{payment}/reverse`. Záznam bankovní položky je nezávislý předpoklad; tato funkce neimportuje výpis, neodesílá platbu a neúčtuje do hlavní knihy.
