<?php

use App\Services\Profile\CvTextSectionExtractor;

dataset('experience section headings', [
    'professional experience' => 'Professional Experience',
    'work experience' => 'Work Experience',
    'employment' => 'Employment',
    'employment history' => 'Employment History',
    'career history' => 'Career History',
    'work history' => 'Work History',
    'experience' => 'Experience',
    'professional history' => 'Professional History',
]);

test('it detects common experience headings and stops at another explicit section', function (string $heading): void {
    $sourceText = <<<TEXT
        Alex Taylor

        {$heading}
        Senior Developer
        Experience Education & Training Ltd
        January 2024 - Present

        Built customer systems.

        Awards
        Employee of the Year
        TEXT;

    $section = app(CvTextSectionExtractor::class)->extract($sourceText, [
        'professional experience',
        'work experience',
        'employment',
        'employment history',
        'career history',
        'work history',
        'experience',
        'professional history',
    ]);

    expect($section)
        ->toContain('Experience Education & Training Ltd')
        ->toContain('Built customer systems.')
        ->not->toContain('Awards', 'Employee of the Year');
})->with('experience section headings');

test('it removes selected sections while preserving unrelated CV sections', function (): void {
    $sourceText = <<<'TEXT'
        Alex Taylor

        Professional Summary
        Backend developer.

        Professional Experience
        Senior Developer
        Acme Ltd
        January 2024 - Present

        Built customer systems.

        Technical Skills
        PHP, Laravel

        Education
        BSc Computer Science
        TEXT;

    $filtered = app(CvTextSectionExtractor::class)->without($sourceText, [
        'professional experience',
        'work experience',
        'employment',
        'employment history',
        'career history',
        'work history',
        'experience',
        'professional history',
        'technical skills',
        'skills',
        'core skills',
        'key skills',
    ]);

    expect($filtered)
        ->toContain('Professional Summary', 'Backend developer.', 'Education', 'BSc Computer Science')
        ->not->toContain('Acme Ltd', 'Built customer systems.', 'Technical Skills', 'PHP, Laravel');
});

test('it extracts an unusual heading from original text until the next candidate boundary', function (): void {
    $sourceText = <<<'TEXT'
        Alex Taylor

        Career Journey

        Senior Developer | Acme Ltd | January 2024 - Present
        - Built internal systems.

        Technology Stack

        Backend: PHP, Laravel
        TEXT;

    $section = app(CvTextSectionExtractor::class)->extractFromHeading(
        $sourceText,
        'Career Journey',
        ['Career Journey', 'Technology Stack'],
    );

    expect($section)
        ->toBe("Senior Developer | Acme Ltd | January 2024 - Present\n- Built internal systems.")
        ->not->toContain('Technology Stack', 'PHP');
});
