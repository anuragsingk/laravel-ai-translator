<?php

namespace anuragsingk\LaravelAiTranslator\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class TranslatorService
{
    protected $apiKey;
    protected $aiPrompt;
    protected $logTranslations;
    protected $logFile;

    public function __construct()
    {
        $this->apiKey = config('ai-translator.api_key');
        $this->aiPrompt = config('ai-translator.ai_prompt');
        $this->logTranslations = config('ai-translator.log_translations');
        $this->logFile = config('ai-translator.log_file');
    }

    public function translate(string $text, string $targetLanguage, string $sourceLanguage = 'en'): ?string
    {
        if (!$this->apiKey) {
            Log::error('Gemini API Key is not set.');
            return null;
        }

        $prompt = str_replace('{language}', $targetLanguage, $this->aiPrompt) . "\n\n" . $text;

        try {
            $response = Http::post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-pro:generateContent?key={$this->apiKey}",
                [
                    'contents' => [
                        [
                            'parts' => [
                                ['text' => $prompt]
                            ]
                        ]
                    ]
                ]
            );

            $response->throw();

            $translatedText = $response->json('candidates.0.content.parts.0.text');

            if ($this->logTranslations) {
                Log::build([
                    'driver' => 'single',
                    'path' => $this->logFile,
                ])->info("Translated '{$text}' from {$sourceLanguage} to {$targetLanguage}: '{$translatedText}'");
            }

            return $translatedText;
        } catch (\Exception $e) {
            Log::error("Gemini API translation failed: " . $e->getMessage());
            return null;
        }
    }

    public function translateArray(array $data, string $targetLanguage, string $sourceLanguage = 'en'): array
    {
        $translatedData = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $translatedData[$key] = $this->translateArray($value, $targetLanguage, $sourceLanguage);
            } else {
                $translatedData[$key] = $this->translate($value, $targetLanguage, $sourceLanguage);
            }
        }
        return $translatedData;
    }
}
