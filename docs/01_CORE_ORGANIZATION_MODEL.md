# TMS Core Organization Model v1.1


## 1. Purpose

This document defines the organizational architecture of the TMS platform.

The goal is to support:

- MASTER platform owner
- independent carrier organizations
- subcontractor organizations
- drivers
- delegated management
- responsibility relationships
- permission-based visibility
- financial responsibility chains


## 2. Core Principle

TMS is not an employee hierarchy system.

TMS represents a hierarchical business responsibility network.

The organizational structure defines relationships between:

- organizations
- users
- resources
- operational responsibilities
- financial relationships
- visibility rules


The hierarchy represents responsibility, not employment.


## 3. Responsibility Chain Model

TMS uses a responsibility chain model.

Example:


MASTER

    |

Carrier Organization A

    |

Sub-carrier B

    |

Driver


Each level may:

- operate independently
- manage own resources
- define internal rules
- maintain own economics


Responsibility flows from higher levels toward lower levels.


## 4. MASTER

MASTER represents the technical owner of the TMS platform.

Responsibilities:

- global system administration
- platform configuration
- client management
- permission governance
- technical oversight


MASTER has technical visibility over the platform.


MASTER may also operate as a normal business organization.


Example:


MASTER

    |

Own Carrier Organization

    |

Own drivers and vehicles


Operational activities are performed inside the relevant organization.


## 5. Carrier Organization

A carrier organization is an independent operating entity inside TMS.

A carrier may manage:

- dispatchers
- drivers
- vehicles
- fuel cards
- price lists
- compensation rules
- expenses
- imports
- financial processes


Each carrier manages its own business model.


## 6. Sub-carrier Organization

A sub-carrier may operate below another carrier.

A sub-carrier may have:

- own users
- own drivers
- own vehicles
- own fuel resources
- own pricing rules
- own internal economics


A higher-level organization manages only its direct business relationship.


## 7. Organizational Economic Independence

Each organizational level has its own economy.


Each level may define:

- revenue
- costs
- internal prices
- driver compensation
- profit calculation
- internal allocations


A parent organization does not automatically manage the internal economics of lower levels.


## 8. Ownership and Visibility Separation

Ownership and visibility are different concepts.


A user or organization may see operational information without becoming the owner of that data.


Example:


Carrier A may see:

- delivered routes
- operational performance
- quality indicators


Carrier A does not automatically control:

- internal salaries
- internal agreements
- internal cost allocation


## 9. Resource Responsibility Model

Business resources have their own responsibility.


Examples:

- vehicles
- fuel cards
- contracts
- financial resources


Resource ownership defines:

- who manages the resource
- who defines rules
- who carries responsibility


The resource user may be different from the resource owner.


## 10. Users

Every person working with TMS is a user.


A user can have:

- account
- organization membership
- role
- permissions
- responsibility scope


## 11. Driver as Full User

A driver can be a full TMS user.


A driver may have:

- own account
- mobile application access
- own operational records
- own permissions


A driver remains responsible for factual operational data created during execution.


## 12. Data Visibility Principle

Visibility is controlled by:

- organization relationship
- permissions
- responsibility
- business rules


Higher levels see information required for their business relationship.


Internal data of lower organizations remains controlled by that organization.


## 13. Delegated Management

Organizations may delegate operational management.


Example:

A carrier may allow a dispatcher to:

- review reports
- manage operational processes
- communicate with drivers


Delegated management does not automatically transfer ownership.


## 14. Future Extensions

This model supports:

- mobile application
- financial modules
- reporting
- banking integration
- fleet management
- external integrations