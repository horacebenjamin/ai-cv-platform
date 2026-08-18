# AI Context

This file is the fast-start context for coding assistants working on the AI CV Platform. Read [ARCHITECTURE.md](ARCHITECTURE.md) for the system design and `AGENTS.md` for mandatory project instructions.

## Product

The application is an AI-assisted career platform for:

- generating and maintaining CVs and job-specific CV variants;
- drafting cover letters and professional summaries;
- analysing job descriptions and tracking applications;
- recording AI usage, cost, credits, and CV history.

The authenticated customer UI is an Inertia/Vue application. Operational and content-management screens live in the Filament panel at `/admin`.

## Current Stack

- PHP 8.4 and Laravel 12
- MySQL 8.4 in the Sail environment
- Inertia 2, Vue 3, Vite 7, and Tailwind CSS
- Filament 5 with Livewire 4
- Pest 3
- Laravel Sail for all local commands
- `laravel/ai` 0.10 is installed; CV generation uses the SDK `GenerateCvAgent`, while generic text requests temporarily continue through the application-owned `AIProviderInterface` and `OpenAIService`

Check installed versions before using package APIs. Do not infer an API version from this document.

## Working Rules

1. Run PHP, Artisan, Composer, and Node commands through `vendor/bin/sail`.
2. Before editing, read `.ai/rules/index.md` and all matching rules when `.ai/rules` exists, then search `.ai/rules` for relevant terms.
3. Search version-specific Laravel documentation through Laravel Boost before code changes.
4. Follow neighbouring files for naming, structure, types, and conventions.
5. Keep controllers and Filament resources thin; domain behaviour belongs in services and queued jobs.
6. CV generation uses Laravel AI SDK provider configuration; the temporary generic path must continue to depend on `AIProviderInterface`, not `OpenAIService`, until its migration is approved.
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
| Tests and AI fake | `tests/Feature`, `tests/Fakes/FakeAIProvider.php` |

## AI Request Lifecycle

1. A feature service creates an `AiRequest` through `AIRequestService`.
2. `ProcessAIRequest` is dispatched to the configured database queue.
3. For generic text features, the job marks the request as processing, compiles templates, and uses the legacy `AIService` provider pipeline.
4. For `cv_generation`, `CVGenerationService` validates ownership and input, builds explicitly labelled context, and synchronously prompts `GenerateCvAgent` inside the existing application job.
5. Laravel AI SDK returns structured CV data plus normalized usage and actual provider/model metadata.
6. `AIUsageService` converts usage into application-configured cost and consumed credits.
7. The request is completed or marked failed. CV-linked operations may also create history records.

`cv_generation` has a specialised SDK branch in `CVGenerationService`: it verifies ownership and inputs, requests structured output through `GenerateCvAgent`, validates the normalized array, and then builds the CV and child sections, creates a complete history snapshot, completes the AI request, and deducts credits in one database transaction. The provider call remains outside the transaction.

## AI Contracts and Invariants

- Supported prompt placeholders are defined in `PromptCompiler::PLACEHOLDERS`.
- Named prompts are defined by `PromptTemplateService`; unknown templates without a fallback are invalid.
- Legacy generic providers implement `generate`, `healthCheck`, `estimateCost`, and `modelName`.
- CV generation receives provider, model, and input/output token usage from Laravel AI SDK response metadata and persists a normalized structured response rather than the raw provider payload.
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
- CV generation has migrated to Laravel AI SDK. Generic text generation still uses the legacy provider pipeline; both paths deliberately coexist during the phased migration.
