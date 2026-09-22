<?php

use App\Services\Profile\ExperienceBlockSplitter;

dataset('experience layouts', [
    'separate title company and long month' => <<<'TEXT'
        Senior Software Engineer
        Acme Ltd
        January 2024 - Present

        • Built API integrations.
        • Improved database performance.
        TEXT,
    'single pipe-separated header' => <<<'TEXT'
        Acme Ltd | Senior Software Engineer | Jan 2024 – Present
        • Built API integrations.
        • Improved database performance.
        TEXT,
    'comma-separated header with location' => <<<'TEXT'
        Senior Software Engineer, Acme Ltd
        London, UK
        2022 - 2024

        Developed internal systems.
        Maintained cloud infrastructure.
        TEXT,
    'company and location before title' => <<<'TEXT'
        ACME LIMITED — MANCHESTER
        Software Engineer
        March 2021 to December 2023

        - Developed Laravel applications
        - Integrated Stripe
        TEXT,
    'employment type and duration layout' => <<<'TEXT'
        Software Engineer
        Acme Ltd · Full-time
        01/2024 - Present · 2 yrs
        Manchester, United Kingdom

        • Built customer portals.
        TEXT,
]);

test('it preserves one candidate block across common CV layouts', function (string $layout): void {
    expect(app(ExperienceBlockSplitter::class)->split($layout))->toBe([$layout]);
})->with('experience layouts');

test('it splits several differently formatted roles without interpreting their fields', function (): void {
    $roles = [
        <<<'TEXT'
        Senior Software Engineer
        Acme Ltd
        January 2024 - Present

        • Built API integrations.
        TEXT,
        <<<'TEXT'
        Beta Ltd | Platform Engineer | 2022–2023
        • Maintained cloud infrastructure.
        TEXT,
        <<<'TEXT'
        GAMMA LIMITED — LONDON
        Developer
        March 2021 to December 2021

        Responsibilities:
        - Developed Laravel applications
        TEXT,
    ];

    expect(app(ExperienceBlockSplitter::class)->split(implode("\n\n", $roles)))->toBe($roles);
});

test('it keeps a career break separate from adjacent employment', function (): void {
    $entries = [
        <<<'TEXT'
        Software Engineer
        Company One
        January 2021 - August 2022

        • Built web applications.
        TEXT,
        <<<'TEXT'
        Career Break
        August 2022 - January 2023

        Temporary personal leave.
        TEXT,
        <<<'TEXT'
        Senior Developer
        Company Two
        February 2023 - Present

        • Built APIs.
        TEXT,
    ];

    expect(app(ExperienceBlockSplitter::class)->split(implode("\n\n", $entries)))->toBe($entries);
});

test('it can use a bullet-to-header transition when dates are absent', function (): void {
    $entries = [
        "Developer\nAcme Ltd\n\n• Built web applications.",
        "Senior Developer\nBeta Ltd\n\n• Led API development.",
    ];

    expect(app(ExperienceBlockSplitter::class)->split(implode("\n\n", $entries)))->toBe($entries);
});
