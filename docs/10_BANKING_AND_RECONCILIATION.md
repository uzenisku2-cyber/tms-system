# TMS Banking and Reconciliation Model v1.1

## 1. Purpose

This document defines the bank-account, bank-statement, transaction-import, matching, allocation, reconciliation, correction, and financial-control architecture of the TMS platform.

The model supports:

- multiple bank accounts
- multiple organizations
- domestic and foreign currencies
- bank statement imports
- CSV and spreadsheet imports
- future bank API integrations
- immutable source transactions
- duplicate detection
- automatic matching suggestions
- manual reconciliation
- exact matching
- partial matching
- combined payments
- split allocations
- overpayments
- underpayments
- bank fees
- refunds
- reversed transactions
- currency differences
- cost-period classification
- complete financial audit

The model must remain consistent with:

- TMS Business Principles
- TMS Core Organization Model
- TMS User Roles and Permissions
- TMS Fuel Management
- TMS Pricing and Compensation Model
- TMS Vehicle Asset and Financing Model
- TMS Notification and Rule Engine
- audit and data integrity rules


## 2. Core Separation Principle

The following concepts must remain separate:

- bank account
- account owner
- authorized account manager
- bank statement
- import batch
- raw imported row
- normalized bank transaction
- duplicate candidate
- reconciliation rule
- matching suggestion
- reconciliation decision
- allocation
- financial obligation
- invoice
- payment
- bank transaction
- financial classification
- economic cost period
- correction
- reversal

A bank transaction is external evidence of money movement.

A bank transaction does not automatically identify the business purpose of that movement.

A bank transaction is not itself an invoice, financial obligation, payment allocation, or reconciliation decision.

A matching suggestion is not an approved reconciliation.

Financial classification does not prove that a transaction settles a specific obligation.

The booking date, value date, payment date, invoice date, due date, and economic period must remain separate.


## 3. Bank Accounts, Ownership and Authorization

A bank account must have a stable internal identity.

A bank-account record may identify:

- internal account identifier
- account owner
- responsible organization
- bank
- account number
- bank code
- IBAN
- BIC or SWIFT
- account name
- account currency
- country
- valid-from date
- valid-to date when applicable
- lifecycle status
- import configuration
- access classification

Bank-account lifecycle states may include:

- DRAFT
- ACTIVE
- SUSPENDED
- CLOSED
- ARCHIVED

Account ownership must remain separate from permission to manage or view the account.

A user may access an account only through an applicable organization relationship and explicit permission scope.

Closing a bank account must not delete its statements, transactions, allocations, or reconciliation history.

A change of account owner or responsible organization must be represented through time-bounded records.


## 4. Bank Statements and Import Batches

A bank statement represents a defined reporting period for one bank account.

A bank statement may identify:

- bank account
- statement identifier
- statement number
- period start
- period end
- opening balance
- closing balance
- currency
- source
- generation date
- import status
- source-file reference

An import batch represents one controlled import operation.

An import batch must identify:

- import identifier
- bank account
- source type
- source file or API request
- source-file hash
- importing user or service
- organization context
- import date and time
- parser version
- mapping version
- row count
- accepted count
- rejected count
- duplicate-candidate count
- import status
- failure reason when applicable

Possible import-batch states include:

- CREATED
- VALIDATING
- IMPORTED
- PARTIALLY_IMPORTED
- REJECTED
- FAILED
- CANCELLED

A bank statement and an import batch are different concepts.

One statement may require more than one technical import attempt.

A repeated import must not silently create duplicate transactions.

Statement balance validation and business reconciliation must remain separate controls.

When the source provides opening and closing balances, the system must verify the opening balance plus the signed transaction total against the closing balance according to the bank's statement rules, currency, and precision.

A mathematically balanced statement does not prove that individual transactions are correctly classified, allocated, or reconciled.

An unbalanced statement or import must remain visible as an operational exception and must not be silently corrected.

## 5. Raw Imported Data and Transaction Normalization

The original imported representation must be preserved.

A raw imported row may contain:

- original row number
- original field names
- original field values
- original encoding information
- source-file reference
- import batch
- validation result
- validation errors

Normalized transaction data may include:

- internal transaction identifier
- bank-provided transaction identifier
- bank account
- statement
- booking date
- value date
- amount
- currency
- direction
- counterparty name
- counterparty account
- counterparty IBAN
- counterparty bank code
- variable symbol
- specific symbol
- constant symbol
- end-to-end identifier
- bank reference
- payment message
- transaction type
- original currency
- original amount
- applied bank exchange rate
- raw-row reference

Possible transaction directions include:

- CREDIT
- DEBIT

Normalization must not overwrite the raw imported representation.

A corrected parser or mapping must create a new normalization result or controlled replacement version.

Imported bank values must not be changed merely to make reconciliation easier.


## 6. Transaction Identity, Immutability and Duplicate Detection

A normalized bank transaction must retain stable identity after import.

Imported transaction facts must be immutable, except through an explicit correction or replacement process.

Duplicate detection may use:

- bank-provided transaction identifier
- source-file hash
- source-row fingerprint
- bank account
- booking date
- value date
- amount
- currency
- direction
- counterparty account
- variable symbol
- end-to-end identifier
- bank reference
- normalized message
- configurable comparison window

A transaction fingerprint must be deterministic for the same normalized source data.

Duplicate detection must distinguish:

- exact duplicate
- probable duplicate
- repeated legitimate transaction
- corrected bank record
- reversal transaction
- unresolved duplicate candidate

A duplicate candidate must not be discarded without a trace.

The system must record:

- compared transactions
- comparison method
- matching fields
- confidence
- decision
- deciding user or service
- decision date and time
- reason

Import idempotency and business reconciliation are separate controls.


## 7. Transaction Processing and Reconciliation States

Transaction ingestion state and reconciliation state must remain separate.

Possible ingestion states include:

- IMPORTED
- VALIDATED
- REJECTED
- DUPLICATE_CANDIDATE
- SUPERSEDED

Possible reconciliation states include:

- UNREVIEWED
- SUGGESTED
- PARTIALLY_ALLOCATED
- FULLY_ALLOCATED
- UNDER_REVIEW
- RECONCILED
- DISPUTED
- REVERSED

A transaction may be validly imported while remaining unreconciled.

A transaction may have matching suggestions without having an approved allocation.

A fully allocated transaction may still require review before reconciliation is completed.

Reconciled status must be derived from approved allocations and applicable controls.

A state change must not rewrite the original bank transaction.


## 8. Reconciliation Rules and Versioning

A reconciliation rule defines how matching suggestions may be generated.

Rules may evaluate:

- bank account
- organization
- transaction direction
- amount
- currency
- counterparty name
- counterparty account
- variable symbol
- specific symbol
- constant symbol
- end-to-end identifier
- message text
- transaction type
- date range
- supplier
- customer
- driver
- vehicle
- contract
- invoice
- obligation
- financial category
- combination of conditions

A reconciliation rule must identify:

- rule identifier
- owner
- organization scope
- name
- description
- version
- priority
- conditions
- proposed target type
- proposed classification
- confidence configuration
- valid-from date
- valid-to date when applicable
- lifecycle status
- creating user
- approving user
- change reason

Rule states may include:

- DRAFT
- UNDER_REVIEW
- ACTIVE
- PAUSED
- REPLACED
- ARCHIVED

An active reconciliation rule must not be edited in place.

Changing an active rule requires a new version.

Rule ownership does not grant access to unrelated bank accounts or financial records.


## 9. Matching Suggestions and Confidence

Automatic processing may create matching suggestions.

A matching suggestion must identify:

- bank transaction
- reconciliation-rule version
- suggested target
- suggested amount
- suggested currency
- suggested classification
- confidence score
- matched conditions
- conflicting conditions
- creation date and time
- expiration when applicable
- suggestion status

Possible suggestion states include:

- CREATED
- ACCEPTED
- REJECTED
- EXPIRED
- SUPERSEDED

A confidence score expresses matching confidence, not financial approval.

High confidence must not bypass permissions, organizational scope, or mandatory approval controls.

More than one suggestion may exist for one transaction.

Accepting a suggestion must create or propose a separate reconciliation decision and allocation.

Rejecting a suggestion must not delete its evaluation history.


## 10. Financial Obligations, Invoices, Payments and Transactions

The following records must remain separate:

- financial obligation
- invoice
- payment
- bank transaction
- payment allocation
- reconciliation decision

A financial obligation represents an amount expected to become due or payable.

An invoice documents or requests settlement.

A payment represents a business settlement action.

A bank transaction provides external evidence that money moved.

An allocation connects a defined amount of a bank transaction to a payment, invoice, obligation, refund, advance, or other authorized financial target.

A bank transaction does not prove that an invoice was correctly issued.

An invoice does not prove that a bank payment was received.

A matching reference does not prove that the payer, recipient, amount, or purpose is correct.

Settlement status must be derived from approved allocations rather than directly from message text or symbols.


## 11. Allocation Model

Each allocation must be an independent record.

An allocation must identify:

- allocation identifier
- bank transaction
- target type
- target identifier
- allocated amount
- transaction currency
- target currency when different
- exchange rate when applicable
- converted amount when applicable
- organization context
- allocation source
- reconciliation decision
- creating user or service
- approving user when applicable
- creation date and time
- effective date
- status
- reason
- reversal reference when applicable

Allocation sources may include:

- accepted automatic suggestion
- manual allocation
- imported external reconciliation
- authorized correction
- system-generated balancing entry

Possible allocation states include:

- PROPOSED
- APPROVED
- REJECTED
- REVERSED
- SUPERSEDED

An allocation must not modify the amount or identity of the source bank transaction.

The sum of active approved allocations must not exceed the allocatable transaction amount unless an explicit correction model permits it.

Allocated amount, unallocated amount, and disputed amount must remain independently calculable.


## 12. Partial, Combined and Split Reconciliation

The model must support:

- one transaction allocated to one target
- one transaction split across several targets
- several transactions allocated to one target
- partial settlement
- combined payment
- advance payment
- deposit
- overpayment
- underpayment
- refund
- chargeback
- unidentified receipt
- unidentified expense

A partial allocation must retain the remaining unallocated amount.

A combined payment must retain references to every contributing bank transaction.

A split allocation must retain each individual target and amount.

An overpayment must remain visible as an open balance, advance, refund obligation, or other explicitly classified item.

An underpayment must not silently close the referenced obligation.

Rounding differences must be handled through an explicit rule or adjustment.

A zero remaining amount does not by itself prove that all allocations are semantically correct.


## 13. Fees, Currency Differences, Refunds and Reversals

Bank fees must remain separate from the underlying supplier, customer, driver, or vehicle payment.

Currency conversion must record:

- source currency
- target currency
- source amount
- converted amount
- exchange rate
- rate source
- rate date
- rounding rule
- currency difference

A bank reversal must be represented as a separate bank transaction when supplied by the bank.

A refund must remain separate from the original payment and allocation.

A chargeback must remain separate from the original receipt.

A correction must not rewrite the original bank transaction or approved allocation.

Corrections may use:

- reversal allocation
- replacement allocation
- adjustment record
- corrected classification
- linked bank reversal
- authorized write-off

The original record and every corrective record must remain traceable.


## 14. Reconciliation Decisions, Approval and Closure

A reconciliation decision represents the controlled approval or rejection of proposed financial connections.

A reconciliation decision must identify:

- transaction
- considered suggestions
- selected allocations
- rejected suggestions
- organization context
- deciding user or service
- decision date and time
- decision type
- approval status
- reason
- supporting evidence
- applicable rule versions
- resulting remaining amount

Decision types may include:

- ACCEPT_AUTOMATIC_MATCH
- MANUAL_MATCH
- PARTIAL_MATCH
- REJECT_MATCH
- MARK_UNIDENTIFIED
- DISPUTE
- REOPEN
- REVERSE

Sensitive or high-value decisions may require four-eyes approval.

A user must not approve a reconciliation outside the user's authorized organization and account scope.

Reconciliation closure must not prevent a controlled later correction.

Reopening or reversing a reconciliation must preserve the original decision.


## 15. Financial Classification and Economic Period

Financial classification must remain separate from payment reconciliation.

Classification may identify:

- income or expense
- financial category
- supplier or customer category
- vehicle cost category
- fuel cost
- driver settlement
- insurance cost
- financing cost
- tax or fee
- internal transfer
- unidentified movement

A classification may be suggested independently from a matching target.

The economic period may differ from:

- booking date
- value date
- payment date
- invoice issue date
- invoice due date
- import date

Example:

Fuel consumed in July and paid in August may belong to the July economic period.

Changing an economic period must not change the bank transaction date.

Profitability and KPI calculations must consume classified and reconciled source records without rewriting them.


## 16. Cross-Organization Scope, Visibility and Privacy

Each bank account belongs to a defined account owner and responsible organization context.

Cross-organization visibility may exist only through:

- account ownership
- delegated account management
- commercial relationship
- settlement relationship
- explicit permission
- data-sharing rule
- legal or contractual requirement

A higher-level organization does not automatically receive visibility into another organization's bank statements or internal allocations.

Shared reconciliation information may be limited to:

- payment confirmation
- referenced invoice
- allocated amount
- currency
- payment date
- settlement status
- dispute status

Shared information does not automatically include:

- complete account statement
- unrelated transactions
- opening or closing balance
- unrelated counterparties
- internal categories
- internal profitability
- other organizations' allocations
- private payment messages

Access to a reconciliation result does not automatically grant access to the complete source bank transaction.


## 17. Exceptions, Audit and Operational Control

The system must identify operational exceptions such as:

- rejected imports
- invalid transaction rows
- duplicate candidates
- unmatched transactions
- partially allocated transactions
- conflicting suggestions
- overdue unresolved transactions
- currency differences
- allocation-limit violations
- missing approvals
- reversed bank transactions
- failed external integrations

Notifications must be created through the Notification and Rule Engine.

Sensitive banking actions must be audited, including:

- bank-account creation
- access grant
- statement import
- import rejection
- duplicate decision
- normalization replacement
- rule creation
- rule approval
- suggestion acceptance
- manual allocation
- reconciliation approval
- reconciliation reopening
- allocation reversal
- classification change
- economic-period change
- correction
- export

Audit records must preserve:

- acting user or service
- organization context
- action
- affected record
- previous values
- new values
- date and time
- reason
- approval reference
- correlation identifier

Financial corrections must use reversals, adjustments, or replacement records.

Imported evidence, historical decisions, and prior allocations must not be silently overwritten.


## 18. Integrations and Final Design Rule

The banking and reconciliation model supports integration with:

- organizations
- users and permissions
- bank accounts
- vehicle asset and financing
- fuel management
- pricing and compensation
- invoices
- financial obligations
- payments
- notifications and rule engine
- audit and data integrity
- document storage
- reporting and KPI modules
- future bank APIs
- future accounting integrations

The banking module must not silently create, approve, cancel, or rewrite invoices, compensation records, obligations, or source operational records owned by another module.

Final design rule:

Bank account, statement, import batch, raw imported row, normalized bank transaction, duplicate candidate, reconciliation rule, matching suggestion, reconciliation decision, allocation, obligation, invoice, payment, classification, correction, and reversal must remain separate controlled and auditable records.
