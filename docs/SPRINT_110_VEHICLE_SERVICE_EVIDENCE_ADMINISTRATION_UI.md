# Sprint 110 – servisní evidence na kartě vozidla

Detail vozidla má ve výchozím stavu zavřenou podsložku Servis. Zobrazuje poslední revizi každého servisního záznamu a starší revize lze rozbalit. Samostatná historie a auditní stopa zůstávají dostupné.

Uživatel s `vehicle.manage` zapisuje servis podle ověřeného dokladu nebo opravuje poslední verzi. Formulář nabízí typ a stav servisu, čas zahájení a dokončení, další termín, tachometr, poskytovatele a popis. Posílá aktuální revizi vozidla a při opravě i revizi záznamu. Konflikt vyvolá obnovení karty.

Údaje jsou evidenční; zápis nevytváří servisní objednávku ani platbu. Schéma, provozní stav vozidla a fyzické mazání se nemění.
