<?php

return [
    'default' => env('AI_PROVIDER', 'openai'),

    'providers' => [
        'openai' => [
            'driver' => 'openai',
            'key' => env('OPENAI_API_KEY'),
            'url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'store' => env('OPENAI_STORE', false),
            'models' => [
                'text' => [
                    'default' => env('OPENAI_MODEL', 'gpt-5.6-luna'),
                ],
            ],
            'temperature' => env('OPENAI_TEMPERATURE'),
            'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 2000),
            'timeout' => (int) env('OPENAI_TIMEOUT', 60),
            'input_cost_per_million' => (float) env('OPENAI_INPUT_COST_PER_MILLION', 0),
            'output_cost_per_million' => (float) env('OPENAI_OUTPUT_COST_PER_MILLION', 0),
        ],
    ],

    'credits' => [
        'tokens_per_credit' => (int) env('AI_TOKENS_PER_CREDIT', 1000),
        'minimum' => (int) env('AI_MINIMUM_CREDITS', 1),
    ],
];
