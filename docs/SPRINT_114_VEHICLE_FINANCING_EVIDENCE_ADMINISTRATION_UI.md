# Sprint 114 – uživatelské rozhraní evidence financování vozidla

Detail vozidla obsahuje ve výchozím stavu zavřenou podsložku Financování. Zobrazuje poslední revizi každé finanční smlouvy a na vyžádání starší neměnné verze. Správce může smlouvu založit nebo opravit poslední revizi podle znovu zvoleného ověřeného dokladu.

Formulář rozlišuje financující stranu a dlužníka, zadává data, měnu, částky a stav. Zápis posílá revizi vozidla, oprava také revizi smlouvy. Konflikt znovu načte detail. Zdrojový doklad je doložen auditní událostí, protože tabulka smluv nemá vazbu na doklad. Hodnoty smlouvy nejsou platbami; rozhraní nevytváří splátkový plán, účetní doklad ani úhradu. Schéma databáze se nemění.
