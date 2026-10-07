# Sprint 146: souhlas se zápočtem konkrétní položky

Příjemce vidí vlastní potvrzené a sdílené pohledávky nadřazené organizace za PHM nebo vozidlové náklady. Správce externího dopravce a zaměstnaný řidič mohou každý rozhodnout jen o své položce. Rozhodnutí je neměnné, váže se na verzi, částku, zdroj a protistranu; opakování téhož příkazu je idempotentní. Odmítnutí blokuje zařazení do vyúčtování, stejně jako chybějící souhlas. Změna zdroje či částky zneplatní dřívější souhlas.

Rozhodnutí samo nemění pohledávku, nespouští bankovní platbu ani fakturaci. Pro jinou verzi položky je třeba nový souhlas. Tento krok zatím zpřístupňuje API, nikoli obrazovku.
