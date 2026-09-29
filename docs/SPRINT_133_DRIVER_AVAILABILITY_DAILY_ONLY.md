# Sprint 133 — dostupnost řidiče po dnech

Dostupnost řidiče se zadává a potvrzuje pro celý kalendářní den. Hodnoty jsou dostupný a nedostupný den; chybějící záznam zůstává neznámý. Nezadávají se časová okna ani plánovaný konec jízdy kvůli dostupnosti.

Tento sprint vrací změny Sprintu 132. Zachovává denní kalendář, oddělené potvrzení nadřízeným, revize a auditní historii ze Sprintu 131. Přiřazení jízdy a rozpis směn zůstávají samostatné procesy.

Revert odstraňuje migraci časových oken ze zdrojového kódu. Databáze, na které již byla spuštěna, může nadále obsahovat nepoužívané sloupce `windows`; tento sprint nemaže existující data ani nemění trvalou databázi.
