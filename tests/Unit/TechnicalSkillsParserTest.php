<?php

use App\Services\Profile\TechnicalSkillsParser;

test('it parses categorized skills without returning category headings as skills', function (): void {
    $skills = app(TechnicalSkillsParser::class)->parse(<<<'TEXT'
        Backend: PHP, Laravel, CakePHP
        Frontend: Alpine.js, Tailwind CSS, Livewire
        TEXT);

    expect($skills)->toBe([
        ['name' => 'PHP', 'category' => 'Backend', 'proficiency' => null],
        ['name' => 'Laravel', 'category' => 'Backend', 'proficiency' => null],
        ['name' => 'CakePHP', 'category' => 'Backend', 'proficiency' => null],
        ['name' => 'Alpine.js', 'category' => 'Frontend', 'proficiency' => null],
        ['name' => 'Tailwind CSS', 'category' => 'Frontend', 'proficiency' => null],
        ['name' => 'Livewire', 'category' => 'Frontend', 'proficiency' => null],
    ])->and(array_column($skills, 'name'))->not->toContain('Backend', 'Frontend');
});

test('it keeps parenthesized comma-separated details inside one skill', function (): void {
    $skills = app(TechnicalSkillsParser::class)->parse(
        'Cloud & DevOps: Docker, AWS (EC2, S3, Elastic Beanstalk), CI/CD, Linux'
    );

    expect($skills)->toBe([
        ['name' => 'Docker', 'category' => 'Cloud & DevOps', 'proficiency' => null],
        ['name' => 'AWS (EC2, S3, Elastic Beanstalk)', 'category' => 'Cloud & DevOps', 'proficiency' => null],
        ['name' => 'CI/CD', 'category' => 'Cloud & DevOps', 'proficiency' => null],
        ['name' => 'Linux', 'category' => 'Cloud & DevOps', 'proficiency' => null],
    ]);
});

test('it parses a flat skill list without inventing categories', function (): void {
    $skills = app(TechnicalSkillsParser::class)->parse('PHP, Laravel, CakePHP, MySQL');

    expect($skills)->toBe([
        ['name' => 'PHP', 'category' => null, 'proficiency' => null],
        ['name' => 'Laravel', 'category' => null, 'proficiency' => null],
        ['name' => 'CakePHP', 'category' => null, 'proficiency' => null],
        ['name' => 'MySQL', 'category' => null, 'proficiency' => null],
    ]);
});

test('it trims blanks and removes duplicates case insensitively', function (): void {
    $skills = app(TechnicalSkillsParser::class)->parse(<<<'TEXT'
        Backend:  PHP, , Laravel

        Tools: Git, git,  GitHub,
        TEXT);

    expect($skills)->toBe([
        ['name' => 'PHP', 'category' => 'Backend', 'proficiency' => null],
        ['name' => 'Laravel', 'category' => 'Backend', 'proficiency' => null],
        ['name' => 'Git', 'category' => 'Tools', 'proficiency' => null],
        ['name' => 'GitHub', 'category' => 'Tools', 'proficiency' => null],
    ]);
});

test('it only parses an explicit skills section when given a complete CV', function (): void {
    $skills = app(TechnicalSkillsParser::class)->parse(<<<'TEXT'
        Professional Experience
        Developer, Education Software Ltd

        Technical Skills
        Backend: C#, .NET, Node.js, Vue.js

        Education
        BSc Computer Science
        TEXT);

    expect($skills)->toBe([
        ['name' => 'C#', 'category' => 'Backend', 'proficiency' => null],
        ['name' => '.NET', 'category' => 'Backend', 'proficiency' => null],
        ['name' => 'Node.js', 'category' => 'Backend', 'proficiency' => null],
        ['name' => 'Vue.js', 'category' => 'Backend', 'proficiency' => null],
    ]);
});


test('it does not treat an unsectioned full CV as a flat skills list', function (): void {
    $skills = app(TechnicalSkillsParser::class)->parse(<<<'TEXT'
        Alex Taylor
        Software Engineer
        Acme Ltd
        January 2024 - Present
        Built PHP applications for customers.
        TEXT, allowUnsectioned: false);

    expect($skills)->toBe([]);
});
