# Sprint 145: schválení provozní shody trasy

Nadřazená organizace může výslovně schválit jediný aktuální výkaz, jehož provozní hodnoty odpovídají jedinému řádku importovaného depa. Schválení uchovává identitu řádku depa, kontrolní součet jeho chráněných hodnot, verzi výkazu, schvalujícího uživatele, důvod a čas. Stejný příkaz vrací již vytvořený záznam; změna řidičova výkazu nebo hodnot depa zneplatní použití historického schválení v přehledu.

Tento krok nepřepisuje zdroj depa, nemění stav výkazu, nepočítá odměnu, neprovádí zápočet, nevystavuje fakturu a neoznačuje úhradu ani zaúčtování. Další kroky jsou oprava depa se zachováním originálu, souhlas s konkrétními PHM a vozidlovými položkami, schválení finanční kalkulace a fakturační doklad pro dopravce i zaměstnaného řidiče podle jeho fakturační identity.
