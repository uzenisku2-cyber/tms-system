# Sprint 104 – uživatelská správa životního cyklu vozidla

Karta vozidla obsahuje ve výchozím stavu zavřenou podsložku Životní cyklus. Zobrazuje aktuální stav a revizi. Uživatel s oprávněním `vehicle.manage` vybírá pouze přechody schválené ve Sprintu 103 a vyplní důvod. Zápis vyžaduje potvrzení a odesílá `target_status`, `reason` a `expected_revision` do existujícího organizačně omezeného API.

Po úspěchu se obnoví seznam i karta. Při konfliktu revize nebo probíhající jízdy se karta znovu načte a zobrazí důvod odmítnutí. Archivované vozidlo nemá další akce. Změna nevytváří schéma, nový finanční záznam ani možnost fyzického smazání.
