# Sprint 108 – správa pojistné evidence na kartě vozidla

Detail vozidla obsahuje ve výchozím stavu zavřenou podsložku Pojištění. U každé pojistky se zobrazuje poslední verze a starší verze lze rozbalit. Historie a auditní stopa v samostatné podsložce zůstávají zachovány.

Uživatel s `vehicle.manage` může pojistku zapsat podle ověřeného dokladu nebo opravit její poslední verzi. Formulář odesílá aktuální revizi vozidla a při opravě také revizi pojistky. Po úspěchu se obnoví seznam a karta. Při konfliktu se načtou aktuální údaje a zobrazí zpráva.

Částky, měna a stav jsou evidenční údaje. Formulář neposuzuje pojistné krytí, nevytváří platbu ani pojistnou událost, nemění schéma a nenabízí fyzické mazání.
