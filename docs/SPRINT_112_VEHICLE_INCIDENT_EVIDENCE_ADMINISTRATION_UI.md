# Sprint 112 – uživatelské rozhraní evidence incidentů vozidla

Detail vozidla obsahuje samostatnou, ve výchozím stavu zavřenou podsložku Incidenty. Oprávněný správce zapisuje incident podle ověřeného dokladu a opravuje poslední verzi přidáním nové revize. Starší verze jsou čitelné v historii incidentu.

Formulář uvádí typ, stav, závažnost, časy, volitelného řidiče a odpovědnou organizaci, místo, reference a popis. Zápis posílá revizi vozidla, oprava navíc revizi incidentu. Konflikt načte aktuální detail. Backend ověřuje členství řidiče a organizaci; referenční číslo samo nevytváří pojistný nárok, servisní objednávku ani platbu. Schéma databáze se nemění.
