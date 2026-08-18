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

CV generation --> GenerateCvAgent --> Laravel AI SDK --> configured provider
Generic AI services --> legacy provider boundary --> OpenAI Responses API
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

### Coexisting provider boundaries

CV generation uses `GenerateCvAgent` under `app/Ai/Agents`. The agent implements Laravel AI SDK's `Agent` and `HasStructuredOutput` contracts, owns the CV-generation instructions and schema, and does not perform persistence or accounting. The SDK owns provider transport, structured response decoding, normalized usage, and provider/model metadata for this path.

Generic text generation temporarily retains the application-owned `AIProviderInterface`. `AIService` resolves its legacy driver, calls it, measures elapsed time, and delegates response normalization to `ResponseParser`. `OpenAIService` remains the configured legacy implementation.

`config/ai.php` temporarily contains both SDK-compatible OpenAI keys and a `legacy_driver`. Both paths therefore share credentials, model, timeout, pricing, and accounting configuration without changing the generic runtime path. Provider-side SDK response storage is disabled by default for CV/profile privacy.

### Prompt composition

`GenerateCvAgent` owns CV-generation instructions and receives explicitly labelled JSON context. `PromptTemplateService` continues to own the generic feature templates:

- CV rewrite;
- professional summary;
- skills optimisation;
- cover letter;
- job-match analysis.

`PromptCompiler` continues to replace a fixed allow-list of placeholders for generic text requests. Arrays and objects are encoded as readable JSON, missing values become empty strings, and unknown placeholders are not dynamically evaluated.

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

## CV Generation Components

| Component | Responsibility |
| --- | --- |
| `GenerateCvAgent` | Defines CV instructions and structured output schema, and invokes the configured provider through Laravel AI SDK |
| `CVGenerationService` | Coordinates queueing, context construction, generation, validation, and the transaction |
| `CVJsonParser` | Retained temporarily with the legacy stack but no longer used by CV generation |
| `CVValidationService` | Enforces the generated CV shape and required section fields |
| `CVBuilderService` | Creates the CV aggregate and allow-lists attributes for each child section |
| `CVHistoryService` | Captures a loaded, complete CV snapshot |
| `CVExportService` | Reserved boundary for future provider-neutral exports |

The generation prompt instructs the provider to use supplied facts only. That instruction is a quality control, not a security boundary; server-side validation and attribute allow-lists remain required.

## Data and Accounting

`ai_requests` is the audit record for prompt, normalized response, actual provider, model, status, token use, cost, and processing time. Legacy rows may have a null provider and the admin UI falls back to model-name inference for those records. Credits are append-only entries in `credit_transactions`; AI consumption creates negative amounts. Subscription records hold plan state and the remaining-credit value.

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

Feature tests use Pest with `RefreshDatabase`. CV generation uses the SDK's `GenerateCvAgent::fake()` support with structured responses, explicit usage/meta where accounting is asserted, prompt assertions, and stray-prompt prevention. `FakeAIProvider` remains for generic legacy requests.

The AI engine tests cover the legacy generic prompt compilation, queue dispatch, request transitions, normalized usage/cost/credits, failure state, and outgoing OpenAI request shape. CV generation tests cover request transitions, the complete aggregate and sections, structured domain validation, agent failure, empty and invalid input, idempotency, provider/model usage accounting, history, credits, and transactional rollback.

Tests should target the service or feature boundary that owns the behaviour. Provider calls should be faked; real API credentials are never required by the test suite.

## Extension Points

- Add SDK providers for CV generation through Laravel AI configuration. Generic providers still require `AIProviderInterface` until the generic path is migrated.
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
