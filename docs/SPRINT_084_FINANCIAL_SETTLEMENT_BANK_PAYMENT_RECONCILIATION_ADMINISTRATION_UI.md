# Sprint 084 - Administrace reconcilace bankovnich plateb vyuctovani

## Rozsah

- read model vystavuje stav, revizi, duvod, casy a append-only historii reconcilace pro kazdou bankovni platbu,
- administracni detail umoznuje potvrzeni, znovuotevreni a opetovne potvrzeni kontroly,
- zapis pouziva existujici confirm/reopen endpointy, optimisticke revize a idempotencni klice,
- organizacni scope zustava povinny.

## Hranice

- bez nove databazove migrace,
- bez zmeny platebniho lifecycle, vyuctovani nebo fakturacniho dokladu,
- bez ucetniho zapisu a exportu,
- reconcilacni historie zustava append-only.

## Overeni

Permanentni kontrakty kontroluji presne vazby read modelu, oba existujici prikazy a zakaz prime financni mutace.
