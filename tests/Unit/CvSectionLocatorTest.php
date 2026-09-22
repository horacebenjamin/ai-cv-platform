<?php

use App\Services\Profile\CvSectionLocator;

test('it locates common cv sections', function (): void {
    $sourceText = <<<'TEXT'
        Alex Taylor

        Professional Experience
        Senior Developer
        Acme Ltd
        January 2024 - Present

        Technical Skills
        PHP, Laravel, MySQL

        Education
        BSc Computer Science
        University of Sheffield

        Projects
        Customer Portal

        Certifications
        AWS Certified Developer
        TEXT;

    $locator = app(CvSectionLocator::class);

    expect($locator->experience($sourceText))
        ->toContain('Senior Developer')
        ->not->toContain('Technical Skills')
        ->and($locator->skills($sourceText))
        ->toContain('PHP, Laravel, MySQL')
        ->not->toContain('Education')
        ->and($locator->education($sourceText))
        ->toContain('BSc Computer Science')
        ->and($locator->projects($sourceText))
        ->toContain('Customer Portal')
        ->and($locator->certifications($sourceText))
        ->toContain('AWS Certified Developer');
});
