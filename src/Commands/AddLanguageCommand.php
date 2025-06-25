
<?php

namespace anuragsingk\LaravelAiTranslator\Commands;

use Illuminate\Console\Command;
use anuragsingk\LaravelAiTranslator\Services\TranslatorService;
use Illuminate\Support\Facades\File;

class AddLanguageCommand extends Command
{
    protected $signature = 'ai-translate:add-language {language}';
    protected $description = 'Add a new language and translate source files.';

    public function handle(TranslatorService $translatorService)
    {
        $targetLanguage = $this->argument('language');
        $sourceLanguage = config('ai-translator.source_language');
        $sourceFiles = config('ai-translator.source_files');

        if (in_array($targetLanguage, config('ai-translator.target_languages'))) {
            $this->error("Language '{$targetLanguage}' already exists.");
            return Command::FAILURE;
        }

        $this->info("Translating to '{$targetLanguage}'...");

        foreach ($sourceFiles as $type => $files) {
            foreach ($files as $file) {
                $sourceFilePath = lang_path($type === 'json' ? $file : "{$sourceLanguage}/{$file}");

                if (!File::exists($sourceFilePath)) {
                    $this->warn("Source file not found: {$sourceFilePath}");
                    continue;
                }

                $this->line("Processing {$file}...");

                if ($type === 'json') {
                    $sourceContent = json_decode(File::get($sourceFilePath), true);
                    $translatedContent = $translatorService->translateArray($sourceContent, $targetLanguage, $sourceLanguage);
                    $targetFilePath = lang_path("{$targetLanguage}.json");
                    File::put($targetFilePath, json_encode($translatedContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                } elseif ($type === 'php') {
                    $sourceContent = require $sourceFilePath;
                    $translatedContent = $translatorService->translateArray($sourceContent, $targetLanguage, $sourceLanguage);
                    $targetDir = lang_path($targetLanguage);
                    if (!File::isDirectory($targetDir)) {
                        File::makeDirectory($targetDir, 0755, true);
                    }
                    $targetFilePath = lang_path("{$targetLanguage}/{$file}");
                    File::put($targetFilePath, '<?php\n\nreturn ' . var_export($translatedContent, true) . ';');
                }
                $this->info("Translated {$file} to {$targetLanguage}.");
            }
        }

        $this->updateTargetLanguages($targetLanguage);

        $this->info("Language '{$targetLanguage}' added successfully.");
        return Command::SUCCESS;
    }

    protected function updateTargetLanguages(string $language)
    {
        $configPath = config_path('ai-translator.php');
        $config = require $configPath;
        $targetLanguages = data_get($config, 'target_languages', []);
        $targetLanguages[] = $language;
        data_set($config, 'target_languages', array_unique($targetLanguages));
        File::put($configPath, '<?php\n\nreturn ' . var_export($config, true) . ';');
    }
}
