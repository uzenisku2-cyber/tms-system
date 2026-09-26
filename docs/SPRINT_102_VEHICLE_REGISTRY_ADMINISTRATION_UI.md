# Sprint 102 – uživatelské rozhraní registru vozidel

Produkční obrazovka registru vozidel přebírá schválené členění z izolovaného náhledu.

- Založení vozidla a správa vozidel jsou oddělené hlavní složky; otevřená je správa vozidel.
- Detail vozidla používá výhradní, ve výchozím stavu zavřené podsložky pro dokumenty, vlastnictví, odpovědnosti, doplnění údajů, stav doplnění a historii.
- Historie a auditní stopa jsou skryté do vyžádání uživatelem.
- Typ paliva se vybírá z kanonického seznamu při založení i doplnění údajů.
- Zápisy používají existující organizací omezená API, oprávnění `vehicle.manage` a optimistické revize.
- Fyzické mazání vozidel, změny schématu, pojištění a financování nejsou součástí této změny.
