# TMS Business Principles v1.2

## 1. Purpose

This document defines the fundamental architectural and business principles of the TMS platform.

This document is the internal source of truth for:

- system architecture
- database design
- permission model
- business processes
- future modules development


## 2. TMS Core Philosophy

TMS is not an employee management tree.

TMS is a hierarchical business responsibility system.

The platform represents relationships between:

- organizations
- users
- drivers
- vehicles
- financial rules
- operational processes


## 3. Hierarchical Responsibility Model

The TMS platform supports a multi-level business structure.

Example:

MASTER

    |

Carrier Organization

    |

Subcontractor

    |

Driver Team


Each level can manage its own:

- users
- drivers
- vehicles
- fuel cards
- price lists
- compensation rules
- internal processes


## 4. Dual Role Principle

A platform administrator can also operate as a normal business organization inside the TMS structure.

Example:

MASTER

    |

Own Carrier Organization

    |

Own employees and drivers


The MASTER role provides platform administration.

Operational activities are performed inside the organization where the user operates.


## 5. Organization Boundary Rule

Each organization manages its own internal business data.

A higher-level organization sees only:

- direct business relationships
- agreed services
- authorized operational data


A higher-level organization does not automatically see:

- internal payments
- internal agreements
- internal driver compensation
- internal fuel allocation


## 6. Responsibility Transfer Principle

When a resource or responsibility is assigned to a lower-level organization, responsibility follows the assignment.

Example:

MASTER assigns a fuel card to Carrier A.

Carrier A becomes responsible for:

- usage
- internal allocation
- monitoring
- internal costs


## 7. Economic Independence Principle

Each organizational level can maintain its own economic model.

Each level may define:

- internal prices
- driver compensation
- expenses
- internal agreements


The parent organization manages only its direct commercial relationship.


## 8. Commercial Rule Inheritance Principle

Commercial and accounting rules are defined from higher levels toward lower levels.

Example:

MASTER defines rules for Carrier A.

Carrier A may define its own rules for its subcontractors.

Lower levels cannot modify the commercial relationship above them.


## 9. Operational Data Ownership Principle

Actual operational data belongs to the person who performed the work.

Example:

A driver completes a route.

The driver owns:

- actual kilometers
- delivered parcels
- operational result
- route completion data


Other users may:

- create operational tasks
- review
- approve


They cannot normally modify the driver's factual result.


## 10. Operational Override Principle

Exceptional situations may require administrative intervention.

Examples:

- driver unavailable
- driver not responding
- technical issue


An authorized user may perform an administrative override.

Such action must:

- be marked as an exception
- record who performed it
- record reason
- preserve history
- notify affected users


## 11. Terms Acceptance Principle

The TMS platform supports hierarchical acceptance of rules and conditions.

Each organization may define its own internal rules.

The system records:

- document version
- accepting user
- organization
- date and time of acceptance


## 12. Planning and Reality Separation

TMS separates:

Planning data:

- assigned driver
- vehicle
- route
- planned kilometers


from:

Execution data:

- actual kilometers
- delivered parcels
- operational result


## 13. Audit and History Principle

Important business actions must be traceable.

The system must maintain history of:

- changes
- approvals
- administrative interventions
- assignments
- ownership changes

## 14. Ownership and Visibility Separation Principle

Ownership of data and visibility of data are different concepts.

A user may have visibility into operational information without becoming owner of the data.

Example:

A higher-level carrier may see operational performance of lower-level drivers.

However, the driver remains the owner of factual execution data.


## 15. Resource Ownership Principle

Business resources have their own ownership and responsibility.

Examples:

- vehicles
- fuel cards
- financial resources
- contracts


The owner defines rules and manages responsibility.

The user of the resource may be different from the owner.


## 16. Configuration First Principle

TMS is designed as a configurable platform.

Organizations may define:

- business rules
- required information
- notifications
- reporting views
- financial classifications
- operational settings


The system provides capabilities while allowing different business models.


## 17. Financial Responsibility Chain Principle

Every financial relationship exists between defined parties.

Each organizational level manages its own:

- revenue
- costs
- profit
- internal allocation


Higher levels manage their direct commercial relationships.


## 18. Closed Loop Business Process Principle

Important business processes should reach a controlled final state.

Example:

Operational event

↓

Financial impact

↓

Payment

↓

Allocation

↓

Closed result


The system identifies incomplete processes requiring attention.

## 19. Future Development Rule

All future modules must respect these principles.

Including:

- fuel management
- finance
- compensation
- mobile application
- GPS integration
- external integrations
