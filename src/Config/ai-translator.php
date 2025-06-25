<?php

  return [
      'api_key' => env('AI_TRANSLATOR_API_KEY', null),
      'source_language' => 'en',
      'source_files' => [
          'json' => [
              'en.json',
          ],
      ],
      'target_languages' => [],
      'ai_prompt' => 'Provide only the direct translation of the following text into {language} without any additional explanations, suggestions, or formatting tags.',
      'log_translations' => true,
      'log_file' => storage_path('logs/ai-translator.log'),
  ];
  