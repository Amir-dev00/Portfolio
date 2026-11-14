<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Homepage AI portfolio assistant (OpenAI Responses API)
    |--------------------------------------------------------------------------
    | Keys live only in portfolio-app/.env (outside public_html). Leave disabled
    | until OPENAI_API_KEY is set — the UI shows a calm “not configured” state.
    */

    'enabled' => (bool) env('ASSISTANT_ENABLED', true),

    'api_key' => env('OPENAI_API_KEY'),

    'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),

    'endpoint' => env('OPENAI_RESPONSES_URL', 'https://api.openai.com/v1/responses'),

    /** Bounded outbound wait suitable for shared hosting. */
    'timeout' => (int) env('ASSISTANT_TIMEOUT', 22),

    'connect_timeout' => (int) env('ASSISTANT_CONNECT_TIMEOUT', 5),

    /** Max output tokens for the Responses API. */
    'max_output_tokens' => (int) env('ASSISTANT_MAX_OUTPUT_TOKENS', 400),

    /** Visitor message hard cap (characters). */
    'max_message_chars' => (int) env('ASSISTANT_MAX_MESSAGE_CHARS', 500),

    /** Turns kept in the PHP session (user+assistant pairs). */
    'max_history_turns' => (int) env('ASSISTANT_MAX_HISTORY_TURNS', 2),

    /** Per-IP requests allowed per rolling window (minutes). */
    'rate_limit' => (int) env('ASSISTANT_RATE_LIMIT', 20),

    'rate_decay_minutes' => (int) env('ASSISTANT_RATE_DECAY_MINUTES', 60),

    /** Global daily call budget across all visitors (shared hosting safety). */
    'daily_global_cap' => (int) env('ASSISTANT_DAILY_GLOBAL_CAP', 400),

    'entrance_exam_rank' => [
        'fa' => 'امیرحسین در کنکور رتبهٔ ۷۹۰ منطقهٔ ۳ را به دست آورد.',
        'en' => 'Amirhossein ranked 790th in Region 3 in Iran’s national university entrance exam (Konkur).',
    ],

    'store' => false,

];
