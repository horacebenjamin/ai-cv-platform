# AI Context

This file is the fast-start context for coding assistants working on the AI CV Platform. Read [ARCHITECTURE.md](ARCHITECTURE.md) for the system design and `AGENTS.md` for mandatory project instructions.

## Product Purpose

AI CV Platform is an **AI-powered job application workspace**. It helps job seekers:

- maintain a factual career profile as their source of truth;
- create and manage multiple CV versions;
- tailor CVs to specific job descriptions;
- generate cover letters from real profile and CV data;
- compare their experience with job requirements;
- track saved jobs, applications, interviews, outcomes, and next actions;
- reduce repetitive application work; and
- improve the relevance, consistency, and organisation of their applications.

The product supports the full, repeatable job-application workflow rather than only generating one-off CVs. It is not a generic chatbot: AI should appear in the context of a concrete job-search task and use the user's stored factual context.

## Primary Users

The primary users are professional job seekers, including software engineers, designers, product managers, analysts, graduates, career changers, people returning to the job market, people who have been made redundant, and other professionals applying to multiple roles.

A particularly strong use case is a person who already has useful experience but needs to tailor many applications efficiently without repeatedly re-entering or inconsistently rewriting the same career facts.

## Core User Journey

The intended end-to-end journey is:

```text
Career Profile
  -> Save Job
  -> Analyse Job Description
  -> Tailor CV
  -> Generate Cover Letter
  -> Review / Edit
  -> Export Documents
  -> Mark Application as Applied
  -> Track Screening / Interview / Offer / Rejection
```

This journey is product direction, not a claim that every step is available in the customer UI today.

### Current capabilities

- The domain includes profiles, relational CVs and CV variants, cover letters, companies, job descriptions, saved jobs, and job applications.
- CV generation can use a `Profile`, sections from the user's latest CV, and an optional `JobDescription` as factual context.
- The generic AI path supports CV rewriting, professional-summary generation, skills optimisation, cover-letter generation, and job-match analysis.
- Filament provides most current domain-management workflows. The customer-facing Inertia application currently provides the Breeze shell, authentication, dashboard, and account-profile editing.
- Applications can record progress statuses and notes. Document export is not implemented; `CVExportService` is a reserved boundary.

### Intended product direction

- Make the career profile a complete factual source of truth. At present, identity and summary data live in `Profile`, while detailed career facts used for generation come from CV section records.
- Join saved opportunities, job analysis, tailored CVs, cover letters, applications, interviews, outcomes, and next actions into a coherent customer workflow.
- Associate CVs and cover letters with specific job opportunities where relevant. Do not assume these database relationships already exist: current CVs only retain a target job title, and cover letters retain company/job-title text plus an optional CV link.
- Add review, editing, and export experiences behind the existing domain and service boundaries rather than presenting disconnected AI prompt boxes.

## Product Principles

- The career profile is the factual source of truth.
- AI must not invent employment history, education, projects, certifications, skills, dates, achievements, contact information, or other personal details.
- AI transforms, organises, and tailors real user data; it does not fabricate credentials.
- AI functionality should normally be exposed as a workflow action, not as a generic prompt box.
- CVs and cover letters should be associated with specific job opportunities where relevant, while respecting which relationships are actually implemented.
- The product should reduce application-preparation time while improving relevance, consistency, and organisation.
- Features should support repeat job-search workflows rather than one-off document generation.
- Business and product decisions should favour useful job-search outcomes over adding AI features for novelty.

## Product Positioning

The product is best understood as **an AI-powered job application workspace**. Its value comes from connecting:

```text
career profile -> jobs -> tailored CVs -> cover letters -> applications -> interviews and outcomes
```

Use this positioning to guide implementation choices without making unsupported claims about market leadership, guaranteed interviews, or employment outcomes.

The authenticated customer UI is an Inertia/Vue application. Operational and content-management screens live in the Filament panel at `/admin`.

## Current Stack

- PHP 8.4 and Laravel 12
- MySQL 8.4 in the Sail environment
- Inertia 2, Vue 3, TypeScript, Vite 7, and Tailwind CSS
- Filament 5 with Livewire 4
- Pest 3
- Laravel Sail for all local commands
- `laravel/ai` 0.10 is installed; structured CV generation uses `GenerateCvAgent`, and supported generic career-content requests use `CareerContentAgent`

Check installed versions before using package APIs. Do not infer an API version from this document.

## Frontend Component Strategy

The customer-facing UI architecture is Inertia + Vue 3 + TypeScript + shadcn-vue + Tailwind CSS. TypeScript is the default for new customer-facing Vue code, and shadcn-vue is configured to generate TypeScript components. Filament remains the component system for the `/admin` panel.

- Do not use Filament components in customer-facing Inertia/Vue pages, and do not introduce another general-purpose Vue UI library without approval.
- Prefer existing shadcn-vue primitives over equivalent low-level custom components. The initial foundation includes Button, Input, Textarea, Label, Card, Badge, and Separator; add other primitives incrementally when features require them.
- Keep product- and domain-specific UI as custom Vue components composed from those primitives. Examples include `CvEditor`, `CvSectionEditor`, `CvPreview`, `AiGenerationPanel`, `JobMatchScore`, and `ApplicationTracker`.

## Working Rules

1. Run PHP, Artisan, Composer, and Node commands through `vendor/bin/sail`.
2. Before editing, read `.ai/rules/index.md` and all matching rules when `.ai/rules` exists, then search `.ai/rules` for relevant terms.
3. Search version-specific Laravel documentation through Laravel Boost before code changes.
4. Follow neighbouring files for naming, structure, types, and conventions.
5. Keep controllers and Filament resources thin; domain behaviour belongs in services and queued jobs.
6. Laravel AI SDK is the sole provider boundary for both AI generation paths.
7. Treat model output as untrusted. Parse and validate it before writing domain records.
8. Keep CV generation writes, history creation, request completion, and credit deduction atomic.
9. Never expose API keys, full sensitive prompts, CV content, or personal profile data in logs or exceptions.
10. Add or update Pest coverage for every behaviour change and run the smallest relevant test set.
11. After changing PHP, run `vendor/bin/sail bin pint --dirty --format agent`.
12. Do not change dependencies or create new top-level directories without approval.

## Important Paths

| Area | Location |
| --- | --- |
| Web routes | `routes/web.php`, `routes/auth.php` |
| Customer UI | `resources/js/Pages`, `resources/js/Components`, `resources/js/Layouts` |
| Admin panel | `app/Filament` |
| Domain models | `app/Models` |
| AI orchestration | `app/Services/AI` |
| Laravel AI SDK agents | `app/Ai/Agents` |
| CV generation | `app/Services/CV` |
| Queue worker | `app/Jobs/ProcessAIRequest.php` |
| AI configuration | `config/ai.php` |
| Schema | `database/migrations` |
| AI feature tests | `tests/Feature` |

## AI Request Lifecycle

1. A feature service creates an `AiRequest` through `AIRequestService`.
2. `ProcessAIRequest` is dispatched to the configured database queue.
3. For the five approved generic text features, the job marks the request as processing, builds explicitly labelled feature/context input, and synchronously prompts `CareerContentAgent` inside the existing application job.
4. For `cv_generation`, `CVGenerationService` validates ownership and input, builds explicitly labelled context, and synchronously prompts `GenerateCvAgent` inside the existing application job.
5. Laravel AI SDK returns either structured CV data or plain career-content text plus normalized usage and actual provider/model metadata.
6. `AIUsageService` converts usage into application-configured cost and consumed credits.
7. The request is completed or marked failed. CV-linked operations may also create history records.

`cv_generation` has a specialised SDK branch in `CVGenerationService`: it verifies ownership and inputs, requests structured output through `GenerateCvAgent`, validates the normalized array, and then builds the CV and child sections, creates a complete history snapshot, completes the AI request, and deducts credits in one database transaction. The provider call remains outside the transaction.

The generic SDK branch supports `cv_rewrite`, `professional_summary`, `skills_optimisation`, `cover_letter`, and `job_match_analysis`. `CareerContentAgent` owns the feature-specific behavioral instructions and accepts labelled JSON context. Unknown feature names and malformed structured context fail without an SDK prompt. `AIRequestService` transactionally completes the request and deducts credits under a row lock so repeated or concurrent completion cannot charge twice.

## AI Contracts and Invariants

- Supported generic feature names and behavioral instructions are explicitly allow-listed by `CareerContentAgent`.
- Generic requests may contain structured `context` or a plain prompt, which is passed as a labelled `request` context only for an approved feature.
- Both agents receive provider, model, and input/output token usage from Laravel AI SDK response metadata. CV generation persists normalized structured content; generic generation persists normalized text only, never the raw provider payload.
- AI request states are `queued`, `processing`, `completed`, and `failed`.
- `ProcessAIRequest` tries three times with 10, 30, and 60 second backoffs and a 120 second timeout.
- Invalid input and non-retryable HTTP responses fail immediately; transient failures are allowed to retry.
- Generated CV structured output must have `title`, `summary`, and every configured section array. SDK schema enforcement supplements but does not replace `CVValidationService` domain validation.
- Credit entries are negative for usage. Token-to-credit and cost rates come from `config/ai.php` and environment variables.

## Local Workflow

```bash
vendor/bin/sail up -d
vendor/bin/sail artisan migrate
vendor/bin/sail npm install
vendor/bin/sail npm run dev
vendor/bin/sail artisan queue:work --tries=3 --timeout=120
```

Useful checks:

```bash
vendor/bin/sail artisan test --compact
vendor/bin/sail artisan test --compact tests/Feature/AIEngineTest.php
vendor/bin/sail artisan test --compact tests/Feature/CVGenerationTest.php
vendor/bin/sail bin pint --dirty --format agent
vendor/bin/sail npm run type-check
vendor/bin/sail npm run build
docker compose config --quiet
```

The Compose project bind-mounts the repository at `/var/www/html`, so repository files do not need individual volume entries.

## Environment

AI settings are declared in `.env.example` and consumed by `config/ai.php`. Important keys include:

- `AI_PROVIDER`
- `OPENAI_API_KEY`
- `OPENAI_BASE_URL`
- `OPENAI_MODEL`
- `OPENAI_STORE`
- `OPENAI_MAX_TOKENS`
- `OPENAI_TIMEOUT`
- `OPENAI_INPUT_COST_PER_MILLION`
- `OPENAI_OUTPUT_COST_PER_MILLION`
- `AI_TOKENS_PER_CREDIT`
- `AI_MINIMUM_CREDITS`
- `DB_QUEUE_RETRY_AFTER`

Never commit real credentials. Queue processing requires a running worker because `QUEUE_CONNECTION` is database-backed by default.

## Known Boundaries

- The customer-facing Inertia pages currently cover the Laravel Breeze shell, authentication, dashboard, and profile. Most domain management exists in Filament.
- `CVExportService` is a reserved placeholder; export is not implemented.
- Provider configuration is extensible, but only the OpenAI driver is configured in `config/ai.php`. Provider-side OpenAI response storage defaults to disabled.
- Laravel AI SDK owns provider interaction, agents own instructions and output contracts, and application services own lifecycle, accounting, validation, transactions, history, and persistence.
- AI tests use SDK agent fakes and prevent stray prompts; no custom provider fake is maintained.
