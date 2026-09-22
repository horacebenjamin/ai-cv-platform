<?php

use App\Services\Profile\CvHeadingCandidateExtractor;

test('it finds conservative heading candidates with only one following context line', function (): void {
    $candidates = app(CvHeadingCandidateExtractor::class)->extract(<<<'TEXT'
        Career Journey

        Senior Developer | Acme Ltd | January 2024 - Present
        - Built internal systems.

        Technology Stack

        Backend: PHP, Laravel
        Frontend: Vue.js, Tailwind CSS
        TEXT);

    expect($candidates)->toBe([
        [
            'heading' => 'Career Journey',
            'context' => 'Senior Developer | Acme Ltd | January 2024 - Present',
        ],
        [
            'heading' => 'Technology Stack',
            'context' => 'Backend: PHP, Laravel',
        ],
    ]);
});

test('it rejects company names job titles dates contact details bullets and technology lists', function (): void {
    $candidates = app(CvHeadingCandidateExtractor::class)->extract(<<<'TEXT'
        Professional Experience

        Full Stack Developer

        Built applications.

        Education Technology Solutions Ltd

        January 2024 - Present

        PHP, Laravel, Vue.js, MySQL

        person@example.com

        https://example.com

        +44 7700 900123

        - Improved performance

        Ordinary content.
        TEXT);

    expect($candidates)->toBe([]);
});
