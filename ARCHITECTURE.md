# Architecture

## Overview

AI CV Platform is an AI-powered job application workspace implemented as a Laravel monolith with two presentation surfaces, shared Eloquent domain models, service-layer orchestration, queued AI processing, and a MySQL datastore.

```text
Browser
  |-- Customer application: Inertia + Vue 3
  |     `-- shadcn-vue
  |           `-- Tailwind CSS
  `-- Administration: Filament + Livewire
             |
        Laravel routes / resources
             |
       Application services
       |-- AI services
       |-- CV services
       `-- queued ProcessAIRequest jobs
             |
       Eloquent models + transactions
             |
          MySQL 8.4

CV generation --> GenerateCvAgent --> Laravel AI SDK --> configured provider
Generic career content --> CareerContentAgent --> Laravel AI SDK --> configured provider
```

The codebase favours a modular monolith: domains share one Laravel application and database, while orchestration is separated into narrowly focused services.

## Product and Domain View

The product is organised around a job seeker's repeated application workflow, not around isolated AI generations:

```text
Career Profile
  -> Jobs
  -> Tailored CVs
  -> Cover Letters
  -> Applications
  -> Interviews and Outcomes
```

The conceptual ownership view is:

```text
User
|-- Career Profile
|-- CVs
|-- Cover Letters
|-- Jobs
`-- Applications

AI workflows
|-- CV generation / tailoring
|-- Professional summary generation
|-- Skills optimisation
|-- Cover letter generation
`-- Job-match analysis
```

This is a product/domain map, not an entity-relationship diagram. The current persistence model is documented under [Domain Model](#domain-model); intended links that do not yet exist are called out below rather than implied as implemented.

### Domain responsibilities

- The career profile is the product's intended factual source of truth. Currently, `Profile` stores identity, contact, headline, and biography data, while detailed experience, education, skills, projects, certifications, languages, and references are stored as sections of CV records. CV generation combines the profile with sections from the user's latest CV.
- CVs are user-owned, independently editable documents. The current model supports master CVs and parent/variant relationships. An optional `JobDescription` may inform generation, but the resulting CV stores only `target_job_title`; it has no persisted job relationship.
- Cover letters are user-owned and can optionally belong to a CV. They currently store company and job-title text and do not have foreign keys to a saved job, job description, or application.
- Saved jobs represent opportunities a user wants to retain and may reference a company and job description. Job applications represent progress through an opportunity and may reference a company, job description, and CV at the schema level.
- AI workflows transform supplied factual context. Agents own instructions and output contracts; application services retain ownership of validation, authorisation, request state, persistence, history, transactions, usage accounting, and credits.

### Intended workflow relationships

The intended direction is for a saved opportunity to provide job context for analysis, a tailored CV, and a cover letter, then flow into an application whose screening, interview, offer, rejection, and other outcomes can be tracked. CVs and cover letters should be associated with a specific job or application where that improves traceability.

Those links are not all implemented. In particular, there is no direct CV-to-job relationship, cover letters are not linked to jobs or applications, saved jobs and applications are separate records, and export is still a reserved service boundary. New work must inspect the current schema and models before relying on a conceptual link.

## Runtime Topology

`compose.yaml` defines two services on the `sail` network:

| Service | Responsibility | Persistence / exposure |
| --- | --- | --- |
| `laravel.test` | PHP 8.4 Sail runtime, HTTP application, Artisan, queue workers, and Vite | Repository bind-mounted to `/var/www/html`; application and Vite ports exposed |
| `mysql` | MySQL 8.4 database | Data stored in the `sail-mysql` named volume; host port is configurable |

The application container depends on MySQL. MySQL has a health check, but `depends_on` does not currently wait for the health condition. Queue workers are processes run in the application container rather than a separate Compose service.

## Presentation Layer

The intended presentation architecture separates the two UI surfaces:

```text
Presentation
|-- Customer application
|   `-- Inertia + Vue 3
|       `-- shadcn-vue
|           `-- Tailwind CSS
`-- Administration
    `-- Filament + Livewire
```

shadcn-vue is installed and configured as the customer application's reusable component layer.

### Customer application

Laravel routes render Inertia pages from `resources/js/Pages`. Vue components and layouts live under `resources/js`, Vite builds the client bundle, and Ziggy exposes named Laravel routes to Vue.

The current route surface includes the welcome page, Breeze authentication, an authenticated and verified dashboard, profile editing, and Laravel's `/up` health endpoint.

### Administration panel

Filament is mounted at `/admin`. Resources under `app/Filament/Resources` manage CVs and their sections, templates, companies, job descriptions, saved jobs, applications, AI requests, credit transactions, and CV histories. Shared resource configuration is grouped in:

- `Support/CvAdmin.php`
- `Support/JobAdmin.php`
- `Support/AiAdmin.php`

Dashboard widgets expose AI usage and job-management summaries. Filament authentication protects the panel.

### UI Component Boundaries

- shadcn-vue is the primary reusable component system for customer-facing Inertia/Vue pages.
- Filament is reserved for administrative interfaces. Keep the two UI systems separate, and do not reuse Filament components inside Inertia/Vue pages.
- Product-specific Vue components may compose shadcn-vue primitives into domain workflows and experiences.
- Do not duplicate low-level primitives when a suitable shadcn-vue component exists.
- Do not introduce a second general-purpose Vue component library without approval.

## Domain Model

```text
User
  |-- has one Profile
  |-- has many CVs
  |     |-- belongs to optional CVTemplate
  |     |-- belongs to optional parent CV; has many variants
  |     |-- has many Experiences, Education entries, Skills,
  |     |   Projects, Certifications, Languages, and References
  |     |-- has many CoverLetters
  |     `-- has many CvHistories
  |-- has many CoverLetters
  |-- has many SavedJobs
  |-- has many JobApplications
  |-- has many AiRequests
  |-- has many Subscriptions
  |-- has many CreditTransactions
  `-- has many CvHistories

Company
  |-- has many JobDescriptions
  |-- has many SavedJobs
  `-- has many JobApplications

JobDescription
  |-- belongs to Company
  |-- has many SavedJobs
  `-- has many JobApplications

SavedJob
  |-- belongs to User
  |-- belongs to optional Company
  `-- belongs to optional JobDescription

JobApplication
  |-- belongs to User
  |-- belongs to Company
  |-- belongs to optional JobDescription
  `-- has optional cv_id in the database

CoverLetter
  |-- belongs to User
  `-- belongs to optional CV
```

The CV aggregate is relational rather than a single JSON document. Ordered sections use `sort_order` where applicable. `CvHistory.snapshot` is the deliberate denormalised record of a complete CV state at an event in time.

`job_applications.cv_id` is an optional foreign key used by the admin workflow, but `JobApplication` does not currently define a `cv()` Eloquent relationship. Treat the schema and declared model relationships above as the current source of truth; do not infer inverse or cross-workflow relationships from the conceptual product map.

Foreign keys generally cascade when their owning aggregate is deleted. A CV variant's `parent_cv_id` is set to null when its parent is removed.

## AI Subsystem

### Agent and SDK boundary

CV generation uses `GenerateCvAgent` under `app/Ai/Agents`. The agent implements Laravel AI SDK's `Agent` and `HasStructuredOutput` contracts, owns the CV-generation instructions and schema, and does not perform persistence or accounting.

Supported generic text generation uses `CareerContentAgent`. It implements the SDK `Agent` contract with `Promptable`, owns the explicit feature allow-list and feature-specific instructions, and produces plain text without persistence, accounting, queue, or provider-specific concerns.

Laravel AI SDK is the sole provider boundary. It owns provider transport, normalized response usage, and actual provider/model metadata for both paths. Agents own instructions and output contracts; application services own request lifecycle, accounting, validation, transactions, history, and persistence. Provider-side SDK response storage is disabled by default for CV/profile privacy.

```text
ProcessAIRequest
|-- GenerateCvAgent
`-- CareerContentAgent
          |
          v
Laravel AI SDK providers
          |
          v
AIUsageService / AIRequestService
          |
          v
Domain validation, transactions, history, and persistence
```

### Prompt composition

`GenerateCvAgent` owns CV-generation instructions and receives explicitly labelled JSON context. `CareerContentAgent` owns the approved generic feature instructions:

- CV rewrite;
- professional summary;
- skills optimisation;
- cover letter;
- job-match analysis.

Generic structured request context is passed to the agent as labelled JSON. A plain prompt is wrapped as a labelled `request` field only when its request feature is approved. Unknown features and malformed structured context are rejected before prompting.

The generic AI path returns normalized text and does not by itself create a cover letter, link a CV to a job, or advance an application. Product features that need those effects must coordinate them explicitly in the application/domain layer.

### Request orchestration

`AIRequestService` owns persistence and state transitions. It creates and queues requests, marks work as processing, records completion metrics, deducts credits, creates CV-linked history where appropriate, and records failures.

`ProcessAIRequest` is the sole asynchronous entry point. Generic features synchronously prompt `CareerContentAgent` inside the job. CV generation delegates to `CVGenerationService` because it has a larger transactional workflow. The SDK agent queue API is not used.

```text
Feature action
   |
   v
AIRequestService::create --> ai_requests: queued
   |
   v
ProcessAIRequest
   |-- approved generic feature --> labelled context --> CareerContentAgent
   |                              --> SDK text + Usage + Meta
   |                              --> transactional complete + credits
   `-- cv_generation --> CVGenerationService --> GenerateCvAgent
                            |-- verify ownership and active template
                            |-- build profile/job context
                            |-- request SDK structured output
                            |-- read SDK usage + provider/model metadata
                            |-- validate normalized data
                            `-- transaction
                                 |-- lock/reload request and check idempotency
                                 |-- create CV and section rows
                                 |-- create history snapshot
                                 |-- complete AI request
                                 `-- deduct credits
```

### Failure semantics

- Malformed configuration, invalid domain input, and selected 4xx provider responses are non-retryable.
- Other exceptions are rethrown so the queue can apply retry and backoff policy.
- The queue failure hook ensures an exhausted request reaches `failed` state.
- CV records, their sections, history, request completion, and credit deduction are committed together, preventing partial generated CVs. The provider call occurs before this transaction.
- The request is reloaded with a row lock before CV persistence; an already-completed request does not create a duplicate CV, history record, or credit deduction.
- Generic request completion, optional CV history, and credit deduction are also committed under a request row lock. An already-completed request cannot deduct credits twice, and a late exhausted-job hook cannot overwrite completion.

## CV Generation Components

| Component | Responsibility |
| --- | --- |
| `GenerateCvAgent` | Defines CV instructions and structured output schema, and invokes the configured provider through Laravel AI SDK |
| `CVGenerationService` | Coordinates queueing, context construction, generation, validation, and the transaction |
| `CVValidationService` | Enforces the generated CV shape and required section fields |
| `CVBuilderService` | Creates the CV aggregate and allow-lists attributes for each child section |
| `CVHistoryService` | Captures a loaded, complete CV snapshot |
| `CVExportService` | Reserved boundary for future provider-neutral exports |

The generation prompt instructs the provider to use supplied facts only. That instruction is a quality control, not a security boundary; server-side validation and attribute allow-lists remain required.

## Data and Accounting

`ai_requests` is the audit record for prompt, normalized response, actual SDK provider, model, status, token use, cost, and processing time. Legacy rows may have a null provider and the admin UI falls back to model-name inference for those records. Credits are append-only entries in `credit_transactions`; AI consumption creates negative amounts. Subscription records hold plan state and the remaining-credit value.

Pricing and credit conversion are configuration-driven. `AIUsageService` calculates:

- total tokens from prompt and completion usage;
- estimated provider cost from provider-specific rates;
- credits from tokens-per-credit with a configurable minimum.

Cost and credits are different units and should not be conflated in UI labels or reporting.

## Security and Privacy Boundaries

- Web forms use Laravel authentication, CSRF protection, validation, and route middleware.
- The Filament panel uses its own authentication middleware stack.
- CVs, profiles, prompts, and responses may contain personal data; logging should use identifiers and metrics instead of raw content.
- Provider credentials are environment configuration and must never be stored in the database or repository.
- Ownership must be checked before an AI request can consume a profile, CV, job description, or template selection.
- Generated text is untrusted input. It must not bypass parsing, validation, mass-assignment allow-lists, escaping, or authorisation.

## Testing Strategy

Feature tests use Pest with `RefreshDatabase`. CV generation uses `GenerateCvAgent::fake()` with structured responses; generic generation uses `CareerContentAgent::fake()` with text responses. Both provide explicit usage/meta where accounting is asserted, inspect prompts at the application boundary, and prevent stray prompts. No external provider calls or custom provider fakes are used in AI feature tests.

The AI engine tests cover all approved generic feature instructions and context, queue dispatch, request transitions, normalized SDK text and metadata, usage/cost/credits, unknown and malformed input, retry/exhaustion behavior, and idempotency. CV generation tests cover request transitions, the complete aggregate and sections, structured domain validation, agent failure, empty and invalid input, idempotency, provider/model usage accounting, history, credits, and transactional rollback. Tests also prove CV generation does not invoke the generic agent.

Tests should target the service or feature boundary that owns the behaviour. Provider calls should be faked; real API credentials are never required by the test suite.

## Extension Points

- Add SDK providers for both agents through Laravel AI configuration and provider/model pricing entries.
- Add AI features by defining a stable feature name and prompt, then routing any specialised persistence through a domain service.
- Add customer workflows as Inertia pages and named Laravel routes; keep admin-only operations in Filament.
- Implement CV export behind `CVExportService` so output formats do not leak into the CV generation workflow.
- If queue throughput grows, split workers into dedicated Compose or deployment processes without changing the job contract.

## Architectural Constraints

- Maintain one source of truth for AI request states and usage calculations.
- Keep provider response formats out of domain services by normalising at the AI boundary.
- Keep multi-record aggregate writes transactional.
- Prefer explicit relationships and service methods over hidden model-event side effects.
- Avoid calling AI providers from controllers, Vue components, Filament schemas, or model accessors.
- Preserve CV history as an immutable snapshot rather than rebuilding historical state from current child rows.
