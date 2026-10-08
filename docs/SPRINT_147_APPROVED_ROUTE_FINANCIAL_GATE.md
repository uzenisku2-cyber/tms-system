# S147: Schválená trasa jako podmínka finálního dokladu

Průběžný výpočet odměny z aktuálního zápisu řidiče není blokován měsíčním zápisem depa. Při vystavení odběratelské faktury nebo vytvoření výstupu vyúčtování z finančních kalkulací však systém znovu ověří každou zahrnutou trasu: kalkulace musí používat aktuální verzi zápisu, pro tuto verzi musí existovat právě jedno schválení nadřazenou organizací, nezměněný jednoznačný zdroj depa a aktuální provozní shoda s ním. Kontrolují se provozní údaje, nikoli finanční ceny ve výpisu depa.

Chybějící či zastaralé schválení vrací validační chybu `depot_route_approval`, bez vystavení dokladu. Opakované volání již dokončeného příkazu zůstává idempotentní. Vyúčtování tvořené pouze samostatnými vzájemnými náklady nemá navázanou trasu; jeho souhlas s PHM/vozidlem nadále kontroluje stávající pravidlo. Tento krok zatím nevytváří nové oprávnění, aby si řidič sám vystavil fakturu, ani nepotvrzuje bankovní úhradu.
