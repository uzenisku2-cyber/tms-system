# Sprint 105 – evidence technických kontrol vozidla

Organizačně omezené API zapisuje záznamy provozní evidence do existující tabulky `vehicle_compliance_records`. Vyžaduje `vehicle.manage`, aktivní členství v organizaci, viditelnost vozidla, ověřený dokument téhož vozidla a organizace, důvod a aktuální revizi vozidla.

Oprava vyžaduje také aktuální revizi a veřejný identifikátor poslední verze záznamu. Vytváří novou řádku se stejným `record_uid`, novým `public_id` a vyšší revizí; předchozí verze zůstává zachována. Každý zápis zvýší revizi vozidla a připojí událost registru s identifikátorem a revizí zdrojového dokumentu.

Hodnoty `status` a `result` jsou evidenční údaje, nikoli automatické rozhodnutí o legální provozní způsobilosti. Neprovádí se výpočet lhůt, připomínky, změna životního cyklu vozidla, fyzické mazání ani změna databázového schématu. Uživatelské ovládání této evidence následuje samostatně.
