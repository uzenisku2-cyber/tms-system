# Sprint 127 — evidence doručení faktury

Vydavatel může k vydané zákaznické faktuře ručně zaznamenat způsob, příjemce, čas předání, referenci podpůrného dokladu a důvod zápisu. Záznam se váže na již vytvořené PDF a jeho SHA-256; poškozený, chybějící nebo jiný soubor se odmítne. První zápis má očekávanou revizi 0, oprava přidá novou revizi a starší záznam zůstává čitelný. Idempotentní klíč a očekávaná revize chrání před duplicitou a souběžnou změnou.

`GET /api/v1/customer-invoices/{uuid}/deliveries` zpřístupní historii vydavateli a odběrateli s právem čtení. `POST` je dostupný jen vydavateli s právem správy. Záznam představuje ručně zadanou evidenci; aplikace neodesílá e-mail, nepotvrzuje skutečné převzetí a neúčtuje platbu. Finance zobrazuje historii i formulář u vydané faktury.

Ověřit na jednorázovém SQLite: `CustomerInvoiceIssuanceTest.php`, `BillingVatVisibilityUiTest.php`; dále Pint, PHPStan pro nové služby, kompilaci Blade a plnou backendovou sadu.
