# S149 – Výběr zdroje porovnání depa

Pokud `/auth/me` nevrátí typ aktivní organizace, přehled Tras zkusí čtecí API nadřazené organizace. Teprve odmítnutí 403 vede k odpovídajícímu API dopravce nebo řidiče. Úspěšná odpověď master API bezpečně určí kontext master. Na shodné trase bez oprávnění `daily-reports.approve` se zobrazí důvod, proč nelze potvrdit provozní shodu. Neprovádí se žádný zápis ani změna role.
