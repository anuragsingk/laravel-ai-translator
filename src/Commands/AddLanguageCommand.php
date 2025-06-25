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
        $sourceLanguage = config('ai-translator.source_language', 'en');
        $sourceFiles = config('ai-translator.source_files', []);
        $configPath = config_path('ai-translator.php');

        if (!File::exists($configPath)) {
            $this->error('Config file config/ai-translator.php not found. Run ai-translate:setup first.');
            return Command::FAILURE;
        }

        $config = require $configPath;
        if (!is_array($config)) {
            $this->error('Invalid config file format. Resetting to default.');
            $config = require __DIR__ . '/../../Config/ai-translator.php';
        }

        if (in_array($targetLanguage, data_get($config, 'target_languages', []))) {
            $this->error("Language '{$targetLanguage}' already exists.");
            return Command::FAILURE;
        }

        if (empty($sourceFiles)) {
            $this->warn('No source files configured in config/ai-translator.php. Skipping translation.');
        } else {
            $this->info("Translating to '{$targetLanguage}'...");

            $translated = false;
            foreach ($sourceFiles as $type => $files) {
                $totalItems = count($files);
                $this->output->progressStart($totalItems);

                foreach ($files as $file) {
                    $sourceFilePath = lang_path($type === 'json' ? $file : "{$sourceLanguage}/{$file}");

                    if (!File::exists($sourceFilePath)) {
                        $this->warn("Source file not found: {$sourceFilePath}");
                        $this->output->progressAdvance();
                        continue;
                    }

                    $this->line("Processing {$file}...");

                    try {
                        if ($type === 'json') {
                            $sourceContent = json_decode(File::get($sourceFilePath), true);
                            if (!is_array($sourceContent)) {
                                $this->error("Invalid JSON content in {$sourceFilePath}");
                                $this->output->progressAdvance();
                                continue;
                            }
                            $translatedContent = $translatorService->translateArray($sourceContent, $targetLanguage, $sourceLanguage);
                            // Check for null translations
                            $nullCount = count(array_filter($translatedContent, fn($value) => $value === null));
                            if ($nullCount > 0) {
                                $this->warn("{$nullCount} translations failed in {$file}. Check logs for details.");
                            }
                            $targetFilePath = lang_path("{$targetLanguage}.json");
                            File::put($targetFilePath, json_encode($translatedContent, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
                        } elseif ($type === 'php') {
                            $sourceContent = require $sourceFilePath;
                            if (!is_array($sourceContent)) {
                                $this->error("Invalid PHP array content in {$sourceFilePath}");
                                $this->output->progressAdvance();
                                continue;
                            }
                            $translatedContent = $translatorService->translateArray($sourceContent, $targetLanguage, $sourceLanguage);
                            $nullCount = count(array_filter($translatedContent, fn($value) => $value === null));
                            if ($nullCount > 0) {
                                $this->warn("{$nullCount} translations failed in {$file}. Check logs for details.");
                            }
                            $targetDir = lang_path($targetLanguage);
                            if (!File::isDirectory($targetDir)) {
                                File::makeDirectory($targetDir, 0755, true);
                            }
                            $targetFilePath = lang_path("{$targetLanguage}/{$file}");
                            File::put($targetFilePath, "<?php\n\nreturn " . $this->formatArray($translatedContent) . ";");
                        }
                        $this->info("Translated {$file} to {$targetLanguage}.");
                        $translated = true;
                    } catch (\Exception $e) {
                        $this->error("Translation failed for {$file}: {$e->getMessage()}");
                    }

                    $this->output->progressAdvance();
                }

                $this->output->progressFinish();
            }

            if (!$translated) {
                $this->warn('No files were translated due to missing or invalid source files.');
            }
        }

        $this->updateTargetLanguages($config, $targetLanguage);

        $this->info("Language '{$targetLanguage}' added successfully.");
        return Command::SUCCESS;
    }

    protected function updateTargetLanguages(array &$config, string $language)
    {
        $configPath = config_path('ai-translator.php');
        $targetLanguages = data_get($config, 'target_languages', []);
        $targetLanguages[] = $language;
        data_set($config, 'target_languages', array_unique($targetLanguages));

        $content = "<?php\n\nreturn " . $this->formatArray($config) . ";";
        File::put($configPath, $content);
    }

    protected function formatArray($array, $level = 0)
    {
        $indent = str_repeat('    ', $level);
        $lines = ["["];

        foreach ($array as $key => $value) {
            $key = var_export($key, true);
            if (is_array($value)) {
                $lines[] = "{$indent}    {$key} => " . $this->formatArray($value, $level + 1) . ",";
            } else {
                $value = var_export($value, true);
                $lines[] = "{$indent}    {$key} => {$value},";
            }
        }

        $lines[] = "{$indent}]";
        return implode("\n", $lines);
    }
}