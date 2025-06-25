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

    public function translate(string $text, string $targetLanguage, string $sourceLanguage = 'en', int $retries = 2): ?string
    {
        if (!$this->apiKey) {
            Log::error('Gemini API Key is not set.');
            throw new \Exception('Gemini API Key is not set.');
        }

        $prompt = str_replace('{language}', $targetLanguage, $this->aiPrompt) . "\n\n" . $text;

        for ($attempt = 1; $attempt <= $retries; $attempt++) {
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
                        'attempt' => $attempt,
                    ]);
                    if ($attempt < $retries && $response->status() == 429) {
                        sleep(2); // Wait for rate limit
                        continue;
                    }
                    throw new \Exception('Gemini API request failed: ' . $response->body());
                }

                $jsonResponse = $response->json();

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
                Log::error("Gemini API translation failed: " . $e->getMessage(), [
                    'text' => $text,
                    'attempt' => $attempt,
                ]);
                if ($attempt == $retries) {
                    return null;
                }
            }
        }

        return null;
    }

    public function translateArray(array $data, string $targetLanguage, string $sourceLanguage = 'en', $progressCallback = null): array
    {
        $translatedData = [];
        $totalItems = count($data);
        $currentItem = 0;

        if ($progressCallback) {
            $progressCallback($totalItems, 0);
        }

        foreach ($data as $key => $value) {
            if (is_array($value)) {
                $translatedData[$key] = $this->translateArray($value, $targetLanguage, $sourceLanguage, $progressCallback);
            } else {
                try {
                    $translatedData[$key] = $this->translate($value, $targetLanguage, $sourceLanguage);
                } catch (\Exception $e) {
                    $translatedData[$key] = null;
                    Log::error("Failed to translate '{$value}' to {$targetLanguage}: {$e->getMessage()}");
                }
            }

            $currentItem++;
            if ($progressCallback) {
                $progressCallback($totalItems, $currentItem);
            }
        }

        return $translatedData;
    }
}