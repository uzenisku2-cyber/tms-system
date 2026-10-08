# S147: Zápis depa u vlastní trasy

Řidič a správce podřízeného dopravce vidí u zapsané trasy rozbalitelný provozní náhled řidičského a příslušného depo záznamu. API vrací pouze záznamy přiřazené jejich oprávněnému řidiči/dopravci, provozní hodnoty a odlišná pole. U chybného řidiče nebo více zdrojových záznamů je nutná kontrola nadřazené organizace. Příplatek je samostatný provozní údaj; cenové výpočty depa se neposílají.

Navigace Kontrola zápisů a přímá kontrolní API jsou určeny pouze master organizaci. Vytvoření schvalovacího záznamu zůstává jejím úkonem. Tato úprava sama nevystavuje fakturu, nemění schválení ani datum platby. Pro konečné vystavení faktury je stále potřeba samostatná serverová kontrola schválených aktuálních verzí všech tras v dokladu.
