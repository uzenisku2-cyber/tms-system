# TMS Settings and Catalogs Architecture

## 1. Settings structure

Nastavení obsahuje dvě oddělené oblasti:

- Číselníky
- Nastavení tras

## 2. Initial catalogs

Počáteční číselníky jsou:

- Trasy
- Charakter tras
- Provozní důvody

## 3. Route character

Charakter trasy popisuje provozní prostředí, například městská, venkovská nebo smíšená.

Charakter trasy není totožný s obtížností.

## 4. Route difficulty

Obtížnost je samostatný parametr konkrétní trasy.

Městská trasa nemusí být automaticky obtížná a venkovská nemusí být automaticky snadná.

## 5. Route identity

Číslo ani název trasy nejsou její permanentní identitou.

Historická označení musí zůstat zachována podle období své platnosti.

Příklad: 35 Nepomuk může být historickým označením trasy, která se později označuje jako 28 Nepomuk.

## 6. Dispatcher approval

Schválení dispečerem je konečná business validační brána.

Po schválení předchozí business warningy již neblokují navazující workflow.

Historický původní zápis zůstává auditně zachován.

## 7. Current implementation boundary

Tento krok vytváří navigační a UI foundation.

Nevytváří databázová data a nemění hlavní MVP navigaci.

## 8. Canonical route identity foundation

The route catalog separates permanent route identity from historically valid route attributes.

The routes table contains the stable canonical identity.

The route_versions table contains historically versioned attributes:

- route number,
- route name,
- area,
- validity period,
- change type,
- change note.

Example:

- one canonical route may historically be 35 Nepomuk,
- a later version may be 28 Nepomuk.

Existing daily-report values are not rewritten when a route version changes.

Route catalog history provides context and lineage only.

Dispatcher-approved operational reality remains independent from canonical route maintenance.

## 9. Route catalog CRUD authorization

Route catalog writes use the dedicated permission `settings.catalogs.manage`.

The permission is declared in the central permission list.

It is not hardcoded to a named application role.

The existing super-admin role inherits the complete central permission list.

Route catalog CRUD follows these rules:

- create establishes one canonical route and its first version,
- number, name or area changes create a new RouteVersion,
- historical RouteVersion rows are never overwritten,
- activation and deactivation change only the canonical Route active state,
- route catalog operations do not write to historical daily reports,
- authorization is enforced on the backend and not only in the UI.
