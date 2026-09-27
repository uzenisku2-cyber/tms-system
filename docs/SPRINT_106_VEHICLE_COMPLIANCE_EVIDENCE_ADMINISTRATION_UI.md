# Sprint 106 – správa evidence technických kontrol v registru vozidel

Detail vozidla obsahuje zavřenou podsložku Technické kontroly. Uživatel vidí nejnovější verzi každého záznamu a starší verze může rozbalit. Existující historie a auditní stopa zůstávají dostupné v samostatné podsložce.

Uživatel s oprávněním `vehicle.manage` zapisuje kontrolu s ověřeným dokumentem nebo opravuje poslední verzi záznamu. Formulář odesílá aktuální revizi vozidla a při opravě také revizi poslední verze. Po úspěchu obnoví seznam i kartu. Při konfliktu se karta obnoví a uživatel dostane zprávu, aby použil aktuální údaje.

Formulář nepředstírá automatické posouzení zákonné způsobilosti vozidla. Nemění životní cyklus, podkladový dokument ani schéma databáze a nenabízí fyzické mazání.
