# Billing Conditional Surcharge Administration

## 1. Purpose

A billing price-list version may contain an unlimited number of independently
configured conditional surcharge rules. Each rule remains part of the immutable
commercial contract represented by the approved price-list version.

Conditional billing surcharges remain separate from the manually entered
`surcharge_amount` operational fact on a Daily Report.

## 2. Parcel semantics

The historical persistence field `undelivered_parcels` represents parcels
rejected by the customer after a delivery attempt. Its operator-facing label is
`Odmítnuto zákazníkem`.

The genuinely not-delivered quantity is derived:

```text
not_delivered_parcels =
loaded_parcels
- delivered_parcels
- redirected_parcels
- customer_rejected_parcels
```

The processed quantity is derived:

```text
processed_parcels =
delivered_parcels
+ redirected_parcels
+ customer_rejected_parcels
```

Only `not_delivered_parcels` negatively affects the default delivery-quality
metric.

## 3. Configurable percentage formula

A percentage metric contains one or more selected numerator components and one
or more selected denominator components:

```text
metric percentage =
sum(selected numerator metrics)
/ sum(selected denominator metrics)
* 100
```

The administration UI must not impose an application-level maximum number of
components. Duplicate use of the same source in the same formula role is
rejected.

A zero denominator means that the rule cannot be evaluated. It is neither a
successful threshold match nor an automatic zero-percent result.

The existing single numerator and denominator columns remain available for
backward compatibility. A rule with explicit metric components uses those
components as its authoritative formula.

## 4. Delivery-quality example

The default delivery-quality formula can use:

```text
numerator: processed_parcels
denominator: loaded_parcels
```

or the equivalent explicit component selection:

```text
numerator:
- delivered_parcels
- redirected_parcels
- customer_rejected_parcels

denominator:
- loaded_parcels
```

## 5. Redirected-percentage example

```text
numerator:
- redirected_parcels

denominator:
- loaded_parcels
```

## 6. Evaluation scopes

`per_route` evaluates exactly one approved route source.

`monthly_price_list` aggregates all eligible approved route calculations that:

- use the same billing price list and price-list version;
- belong to the same commercial organization relationship;
- fall inside the same calendar month and effective version period.

The monthly price-list scope deliberately aggregates across performing drivers.
It sums every configured formula component before division. It never averages
route-level percentages.

The historical `monthly_driver` scope remains supported for existing rules but
is not the default scope for customer billing surcharge administration.

## 7. Threshold bands

Each rule may contain an unlimited ordered set of non-overlapping bands. A band
defines optional inclusive or exclusive minimum and maximum values and one
non-negative adjustment value.

PostgreSQL range exclusion remains the final protection against overlapping
bands.

## 8. Reward methods

Supported reward methods remain:

- `amount_per_unit`;
- `fixed_amount`;
- `percentage_of_item`.

For an amount per unit, the rule identifies the quantity source multiplied by
the matched band's adjustment value. A fixed amount is applied once for the
evaluated scope. A percentage-of-item reward targets the calculated amount of a
canonical base pricing item.

## 9. Version lifecycle

Rules, formula components and bands are editable only on a draft price-list
version. Approval and activation freeze the complete configuration. A later
change creates a new price-list version and copies the prior configuration
without mutating historical rows.

## 10. S023 implementation boundary

S023 adds persistence, validation, resources, write-service integration and
browser administration for conditional billing surcharge rules.

Automatic invoice issuance, payment settlement, accounting export, payroll and
driver-compensation calculation remain outside this increment.

## 11. Administration payload

`conditional_rules` is an optional unlimited ordered array on provider-managed
draft creation and draft-version update. Each entry contains an unlimited
ordered numerator-source array, an ordered denominator-source array and an
unlimited ordered `bands` array.

The API assigns rule, component and band positions from array order. The API
does not accept client-managed persistence identifiers.

Cross-field validation enforces metric shape, reward-method shape, canonical
sources, non-negative boundaries and non-overlapping bands before persistence.

## 12. Atomic replacement and version copying

A draft-version update replaces the complete conditional-rule tree inside the
same database transaction as the canonical base-item replacement when the
`conditional_rules` field is present. Omitting the field preserves the current
tree for backward-compatible base-item updates; sending an empty array removes
all rules explicitly. Because child foreign keys use restricted deletion, bands
and metric components are deleted before their parent rule.

Creating a later draft version preserves the established base-item lifecycle:
canonical items remain empty and must be submitted as a complete replacement
before approval. Conditional rules, explicit formula components and bands are
copied from the previous immutable version. A copied legacy rule without
explicit components is normalized into explicit numerator and denominator
components while its legacy first-source columns remain populated.

## 13. Browser administration

The Finance billing-price-list form creates the complete conditional-rule tree
inside the same atomic provider-managed draft request as the four canonical
base rates. The browser starts with editable delivery-quality and redirected-
share presets. Operators may remove them or add an unlimited number of custom
rules and threshold bands.

For every rule, the browser exposes the technical code, operator name,
percentage or quantity metric, ordered numerator and denominator selections,
per-route or monthly evaluation scope, reward method and its dependent source
or target item. The default delivery-quality numerator contains delivered,
redirected and customer-rejected parcels over loaded parcels, so only the
derived not-delivered quantity lowers quality.

The UI performs shape and non-negative-number checks before submission. The
backend remains authoritative for canonical validation, non-overlapping bands,
atomic persistence and version lifecycle protection.
