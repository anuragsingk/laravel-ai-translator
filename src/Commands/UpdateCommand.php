
<?php

namespace anuragsingk\LaravelAiTranslator\Commands;

use Illuminate\Console\Command;
use anuragsingk\LaravelAiTranslator\Services\TranslatorService;
use Illuminate\Support\Facades\File;

class UpdateCommand extends Command
{
    protected $signature = 'ai-translate:update {--dry-run}';
    protected $description = 'Update all languages by translating missing keys.';

    public function handle(TranslatorService $translatorService)
    {
        $dryRun = $this->option('dry-run');
        $sourceLanguage = config('ai-translator.source_language');
        $sourceFiles = config('ai-translator.source_files');
        $targetLanguages = config('ai-translator.target_languages');

        if (empty($targetLanguages)) {
            $this->info('No target languages configured. Please add languages using ai-translate:add-language.');
            return Command::SUCCESS;
        }

        $this->info('Updating translations...');

        foreach ($targetLanguages as $targetLanguage) {
            $this->line("\nProcessing language: {$targetLanguage}");
            foreach ($sourceFiles as $type => $files) {
                foreach ($files as $file) {
                    $sourceFilePath = lang_path($type === 'json' ? $file : "{$sourceLanguage}/{$file}");
                    $targetFilePath = lang_path($type === 'json' ? "{$targetLanguage}.json" : "{$targetLanguage}/{$file}");

                    if (!File::exists($sourceFilePath)) {
                        $this->warn("Source file not found: {$sourceFilePath}");
                        continue;
                    }

                    $sourceContent = ($type === 'json') ? json_decode(File::get($sourceFilePath), true) : require $sourceFilePath;
                    $targetContent = File::exists($targetFilePath) ? (($type === 'json') ? json_decode(File::get($targetFilePath), true) : require $targetFilePath) : [];

                    $updatedContent = $this->updateMissingKeys($sourceContent, $targetContent, $targetLanguage, $sourceLanguage, $translatorService);

                    if ($dryRun) {
                        $this->info("Dry run for {$file} ({$targetLanguage}):");
                        $this->line(json_encode($updatedContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                    } else {
                        if ($type === 'json') {
                            File::put($targetFilePath, json_encode($updatedContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        } elseif ($type === 'php') {
                            $targetDir = lang_path($targetLanguage);
                            if (!File::isDirectory($targetDir)) {
                                File::makeDirectory($targetDir, 0755, true);
                            }
                            File::put($targetFilePath, '<?php\n\nreturn ' . var_export($updatedContent, true) . ';');
                        }
                        $this->info("Updated {$file} for {$targetLanguage}.");
                    }
                }
            }
        }

        $this->info('Translation update complete.');
        return Command::SUCCESS;
    }

    protected function updateMissingKeys(array $source, array $target, string $targetLanguage, string $sourceLanguage, TranslatorService $translatorService): array
    {
        foreach ($source as $key => $value) {
            if (is_array($value)) {
                $target[$key] = $this->updateMissingKeys($value, $target[$key] ?? [], $targetLanguage, $sourceLanguage, $translatorService);
            } else {
                if (!isset($target[$key]) || $target[$key] === $value) { // Only translate if key is missing or identical to source
                    $translated = $translatorService->translate($value, $targetLanguage, $sourceLanguage);
                    if ($translated !== null) {
                        $target[$key] = $translated;
                    } else {
                        $this->warn("Failed to translate '{$value}' for key '{$key}' to {$targetLanguage}.");
                        $target[$key] = $value; // Keep original if translation fails
                    }
                }
            }
        }
        return $target;
    }
}
