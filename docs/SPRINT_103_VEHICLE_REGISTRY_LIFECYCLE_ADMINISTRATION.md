# Sprint 103 – správa životního cyklu vozidla

Organizačně omezené API mění stav existujícího vozidla bez změny identity nebo schématu. Vyžaduje `vehicle.manage`, aktivní členství v organizaci, viditelnost vozidla, aktivní odpovědnost organizace, důvod a aktuální revizi. Úspěšná změna zvýší revizi a připojí událost `vehicle_lifecycle_transitioned` s původním a cílovým stavem.

Povolené přechody: `active` → `temporarily_inactive` nebo `restricted`; z obou těchto stavů → `active`, `disposed` nebo `written_off`; `disposed` a `written_off` → `archived`. Archivace je koncový stav. Přechod z `active` při přidělené nebo zahájené jízdě je odmítnut. `active` je pravda právě ve stavu `active`; `archived_at` se nastaví při archivaci.

Staré HTTP API nesmí změnit `active` ani fyzicky smazat vozidlo. Ostatní staré úpravy zůstávají dostupné. Pojištění, financování, platby, bankovní párování a schéma databáze nejsou měněny.
