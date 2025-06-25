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
            throw new \Exception('Gemini API Key is not set.');
        }

        $prompt = str_replace('{language}', $targetLanguage, $this->aiPrompt) . "\n\n" . $text;

        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
            ])->post(
                "https://generativelanguage.googleapis.com/v1beta/models/gemini-1.5-flash:generateContent?key={$this->apiKey}",
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

            if ($response->failed()) {
                Log::error('Gemini API request failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);
                throw new \Exception('Gemini API request failed: ' . $response->body());
            }

            $jsonResponse = $response->json();

            // Log full response for debugging
            Log::build([
                'driver' => 'single',
                'path' => storage_path('logs/ai-translator-debug.log'),
            ])->info('Gemini API response: ' . json_encode($jsonResponse, JSON_PRETTY_PRINT));

            $translatedText = $jsonResponse['candidates'][0]['content']['parts'][0]['text'] ?? null;

            if ($translatedText === null) {
                throw new \Exception('Failed to extract translated text from API response.');
            }

            if ($this->logTranslations) {
                Log::build([
                    'driver' => 'single',
                    'path' => $this->logFile,
                ])->info("Translated '{$text}' from {$sourceLanguage} to {$targetLanguage}: '{$translatedText}'");
            }

            return $translatedText;
        } catch (\Exception $e) {
            Log::error("Gemini API translation failed: " . $e->getMessage());
            throw $e;
        }
    }

    public function translateArray(array $data, string $targetLanguage, string $sourceLanguage = 'en'): array
    {
        $translatedData = [];
        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $translatedData[$key] = $this->translateArray($value, $targetLanguage, $sourceLanguage);
            } else {
                try {
                    $translatedData[$key] = $this->translate($value, $targetLanguage, $sourceLanguage);
                } catch (\Exception $e) {
                    $translatedData[$key] = null;
                    Log::error("Failed to translate '{$value}' to {$targetLanguage}: {$e->getMessage()}");
                }
            }
        }
        return $translatedData;
    }
}