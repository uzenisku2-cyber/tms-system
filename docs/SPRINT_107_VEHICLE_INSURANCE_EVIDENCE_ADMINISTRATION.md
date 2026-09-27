# Sprint 107 – evidence pojištění vozidla

Organizačně omezené API zapisuje údaje pojistky do existující tabulky `vehicle_insurance_policies`. Zápis vyžaduje `vehicle.manage`, aktivní členství v organizaci, viditelnost vozidla, ověřený dokument téhož vozidla a organizace, důvod a aktuální revizi vozidla.

Oprava vytváří novou verzi se stejným `record_uid`, novým `public_id` a vyšší revizí. Vyžaduje aktuální revizi vozidla, revizi poslední verze pojistky a její veřejný identifikátor. Starší verze se nemění. Každý úspěšný zápis zvýší revizi vozidla a připojí auditní událost s identifikátorem a revizí zdrojového dokumentu.

Typ, stav, platnost, limity, spoluúčast a měna jsou evidenční údaje. Tento sprint nevyhodnocuje pojistné krytí, nevytváří pojistné události, platby ani finanční závazky. Nemění databázové schéma nebo životní cyklus vozidla a neumožňuje fyzické mazání. Uživatelské ovládání evidence následuje samostatně.
