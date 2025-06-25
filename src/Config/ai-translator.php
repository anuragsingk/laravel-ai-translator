
<?php

return [
    'api_key' => env('AI_TRANSLATOR_API_KEY'),
    'source_language' => 'en',
    'source_files' => [
        // 'json' => [
        //     'en.json',
        //     'english.json',
        // ],
        // 'php' => [
        //     'messages.php',
        //     'auth.php',
        // ],
    ],
    'target_languages' => [],
    'ai_prompt' => 'Translate the following text into {language}.',
    'log_translations' => false,
    'log_file' => storage_path('logs/ai-translator.log'),
];

