# Sprint 128 — řízené e-mailové odeslání zákaznické faktury

Vydavatel s právem správy může jednou výslovně požádat o odeslání vydané faktury na validovanou adresu. Příkaz vyžaduje idempotentní klíč, důvod a přesný SHA-256 již uloženého PDF. API ověří snímek vydání i obsah souboru a vytvoří záznam `queued` v databázi. Pracovník fronty ověří soubor znovu a předá PDF transportu SMTP. Logovací transport ani výchozí ukázková adresa odesílatele nejsou přípustné.

Stav `accepted` znamená přijetí transportem SMTP, nikoli potvrzené doručení či přečtení adresátem. V tom okamžiku vznikne také nová revize historie evidence doručení s referencí na pokus. Chyba nebo nejednoznačný výsledek po začátku odesílání vede na `uncertain`; úloha má jediný pokus a aplikace automaticky neopakuje přenos, aby po výpadku neposlala duplikát. Nové odeslání stejné faktury blokuje samostatný záznam pokusu; provozní řešení nejasného stavu vyžaduje kontrolu SMTP a případný další řízený proces.

API: `GET/POST /api/v1/customer-invoices/{uuid}/email-dispatch` pouze pro vydavatele s `compensation.manage`. Finance nabízí explicitní potvrzení adresy a stav pokusu. Pro provoz je nutné konfigurovat `MAIL_MAILER=smtp`, skutečné `MAIL_FROM_ADDRESS`, dostupný SMTP server a běžícího pracovníka databázové fronty. Testy používají `Queue::fake` a `Mail::fake`; žádný skutečný e-mail se při nich neodesílá.
