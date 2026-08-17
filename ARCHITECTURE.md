# Architecture

## Overview

AI CV Platform is a Laravel monolith with two presentation surfaces, shared Eloquent domain models, service-layer orchestration, queued AI processing, and a MySQL datastore.

```text
Browser
  |-- Inertia + Vue customer application
  `-- Filament + Livewire administration panel
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

AI services --> configured provider --> OpenAI Responses API
```

The codebase favours a modular monolith: domains share one Laravel application and database, while orchestration is separated into narrowly focused services.

## Runtime Topology

`compose.yaml` defines two services on the `sail` network:

| Service | Responsibility | Persistence / exposure |
| --- | --- | --- |
| `laravel.test` | PHP 8.4 Sail runtime, HTTP application, Artisan, queue workers, and Vite | Repository bind-mounted to `/var/www/html`; application and Vite ports exposed |
| `mysql` | MySQL 8.4 database | Data stored in the `sail-mysql` named volume; host port is configurable |

The application container depends on MySQL. MySQL has a health check, but `depends_on` does not currently wait for the health condition. Queue workers are processes run in the application container rather than a separate Compose service.

## Presentation Layer

### Customer application

Laravel routes render Inertia pages from `resources/js/Pages`. Vue components and layouts live under `resources/js`, Vite builds the client bundle, and Ziggy exposes named Laravel routes to Vue.

The current route surface includes the welcome page, Breeze authentication, an authenticated and verified dashboard, profile editing, and Laravel's `/up` health endpoint.

### Administration panel

Filament is mounted at `/admin`. Resources under `app/Filament/Resources` manage CVs and their sections, templates, companies, job descriptions, saved jobs, applications, AI requests, credit transactions, and CV histories. Shared resource configuration is grouped in:

- `Support/CvAdmin.php`
- `Support/JobAdmin.php`
- `Support/AiAdmin.php`

Dashboard widgets expose AI usage and job-management summaries. Filament authentication protects the panel.

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
  |-- has many SavedJobs and JobApplications
  |-- has many AiRequests
  |-- has many Subscriptions
  `-- has many CreditTransactions

Company
  |-- has many JobDescriptions
  |-- has many SavedJobs
  `-- has many JobApplications
```

The CV aggregate is relational rather than a single JSON document. Ordered sections use `sort_order` where applicable. `CvHistory.snapshot` is the deliberate denormalised record of a complete CV state at an event in time.

Foreign keys generally cascade when their owning aggregate is deleted. A CV variant's `parent_cv_id` is set to null when its parent is removed.

## AI Subsystem

### Provider boundary

`AIProviderInterface` defines the application-owned provider contract. `AIService` resolves a configured provider driver from the Laravel container, calls it, measures elapsed time, and delegates response normalisation to `ResponseParser`.

`OpenAIService` is the configured implementation. It calls `/v1/responses` with Laravel's HTTP client, supplies model and generation limits, and reads pricing configuration to estimate cost.

Although the Laravel AI SDK package is installed, this path currently does not use SDK `Agent` classes or the `Promptable` trait. A future migration should be an explicit architecture change, retaining the application-level provider boundary or deliberately replacing it with tested equivalents.

### Prompt composition

`PromptTemplateService` owns the supported feature templates:

- CV generation and rewrite;
- professional summary;
- skills optimisation;
- cover letter;
- job-match analysis.

`PromptCompiler` replaces a fixed allow-list of placeholders. Arrays and objects are encoded as readable JSON, missing values become empty strings, and unknown placeholders are not dynamically evaluated.

### Request orchestration

`AIRequestService` owns persistence and state transitions. It creates and queues requests, marks work as processing, records completion metrics, deducts credits, creates CV-linked history where appropriate, and records failures.

`ProcessAIRequest` is the asynchronous entry point. Generic features compile and send their prompts through `AIService`. CV generation delegates to `CVGenerationService` because it has a larger transactional workflow.

```text
Feature action
   |
   v
AIRequestService::create --> ai_requests: queued
   |
   v
ProcessAIRequest
   |-- generic feature --> compile --> provider --> normalise --> complete
   `-- cv_generation --> CVGenerationService
                            |-- verify ownership and active template
                            |-- build profile/job context
                            |-- request structured JSON
                            |-- parse and validate
                            `-- transaction
                                 |-- create CV and section rows
                                 |-- create history snapshot
                                 |-- complete AI request
                                 `-- deduct credits
```

### Failure semantics

- Malformed configuration, invalid domain input, and selected 4xx provider responses are non-retryable.
- Other exceptions are rethrown so the queue can apply retry and backoff policy.
- The queue failure hook ensures an exhausted request reaches `failed` state.
- CV records, their sections, history, request completion, and credit deduction are committed together, preventing partial generated CVs.

## CV Generation Components

| Component | Responsibility |
| --- | --- |
| `CVGenerationService` | Coordinates queueing, context construction, generation, validation, and the transaction |
| `CVJsonParser` | Removes an optional Markdown JSON fence and decodes a top-level JSON object |
| `CVValidationService` | Enforces the generated CV shape and required section fields |
| `CVBuilderService` | Creates the CV aggregate and allow-lists attributes for each child section |
| `CVHistoryService` | Captures a loaded, complete CV snapshot |
| `CVExportService` | Reserved boundary for future provider-neutral exports |

The generation prompt instructs the provider to use supplied facts only. That instruction is a quality control, not a security boundary; server-side validation and attribute allow-lists remain required.

## Data and Accounting

`ai_requests` is the audit record for prompt, response, model, status, token use, cost, and processing time. Credits are append-only entries in `credit_transactions`; AI consumption creates negative amounts. Subscription records hold plan state and the remaining-credit value.

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

Feature tests use Pest with `RefreshDatabase`. `FakeAIProvider` supplies deterministic provider responses without network access.

The AI engine tests cover prompt compilation, queue dispatch, request transitions, normalised usage/cost/credits, failure state, and the outgoing OpenAI request shape. CV generation tests cover a complete aggregate, invalid JSON, provider failure without partial writes, empty-profile rejection, credits, and history snapshots.

Tests should target the service or feature boundary that owns the behaviour. Provider calls should be faked; real API credentials are never required by the test suite.

## Extension Points

- Add providers by implementing `AIProviderInterface` and registering a driver in `config/ai.php`.
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
