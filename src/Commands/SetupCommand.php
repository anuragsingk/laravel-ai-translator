<?php

namespace anuragsingk\LaravelAiTranslator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class SetupCommand extends Command
{
    protected $signature = 'ai-translate:setup';
    protected $description = 'Setup the Laravel AI Translator package.';

    public function handle()
    {
        $this->info('Setting up Laravel AI Translator...');

        // 1. Get Gemini API Key
        $apiKey = $this->ask('Please enter your Gemini API Key:');
        $this->updateEnvFile('AI_TRANSLATOR_API_KEY', $apiKey);

        // 2. Detect or ask for source language file(s)
        $this->info('Detecting source language files...');
        $langPath = lang_path();

        $jsonFiles = File::glob("{$langPath}/*.json");
        $phpFiles = File::glob("{$langPath}/*/*.php");

        $sourceFiles = [];

        if (!empty($jsonFiles)) {
            $this->info('Detected JSON language files:');
            foreach ($jsonFiles as $file) {
                $fileName = basename($file);
                $this->line("- {$fileName}");
                $sourceFiles['json'][] = $fileName;
            }
        }

        if (!empty($phpFiles)) {
            $this->info('Detected PHP array language files:');
            foreach ($phpFiles as $file) {
                $relativePath = str_replace(
                    "{$langPath}/",
                    '',
                    $file
                );
                $this->line("- {$relativePath}");
                $sourceFiles['php'][] = $relativePath;
            }
        }

        if (empty($sourceFiles)) {
            $this->warn('No language files detected. Please add them manually to config/ai-translator.php later.');
        } else {
            $this->updateConfigFile('source_files', $sourceFiles);
        }

        // 3. Auto-detect source language from filename (fallback to ask user)
        $sourceLanguage = config('ai-translator.source_language');
        if (empty($sourceFiles['json']) && empty($sourceFiles['php'])) {
            $sourceLanguage = $this->ask('Could not auto-detect source language. Please enter your source language code (e.g., en):', 'en');
        } else {
            // Try to guess from common file names like en.json or en/messages.php
            foreach ($sourceFiles as $type => $files) {
                foreach ($files as $file) {
                    if ($type === 'json') {
                        if (preg_match('/^([a-z]{2})\.json$/i', $file, $matches)) {
                            $sourceLanguage = $matches[1];
                            break 2;
                        }
                    } elseif ($type === 'php') {
                        if (preg_match('/^([a-z]{2})\//i', $file, $matches)) {
                            $sourceLanguage = $matches[1];
                            break 2;
                        }
                    }
                }
            }
            $sourceLanguage = $this->ask('Auto-detected source language: ' . $sourceLanguage . '. Is this correct?', $sourceLanguage);
        }
        $this->updateConfigFile('source_language', $sourceLanguage);

        $this->info('Setup complete!');
        $this->call('vendor:publish', ['--tag' => 'ai-translator-config']);
    }

    protected function updateEnvFile(string $key, string $value)
    {
        $envFile = base_path('.env');
        if (File::exists($envFile)) {
            $content = File::get($envFile);
            if (str_contains($content, "{$key}=")) {
                $content = preg_replace("/^{$key}=.*$/m", "{$key}=" . $value, $content);
            } else {
                $content .= "\n{$key}=" . $value;
            }
            File::put($envFile, $content);
        } else {
            $this->warn('No .env file found. Please add AI_TRANSLATOR_API_KEY manually.');
        }
    }

    protected function updateConfigFile(string $key, $value)
    {
        $configPath = config_path('ai-translator.php');
        if (File::exists($configPath)) {
            $config = require $configPath;
            data_set($config, $key, $value);
            $content = '<?php\n\nreturn ' . var_export($config, true) . ';';
            File::put($configPath, $content);
        } else {
            $this->warn('Config file config/ai-translator.php not found. Please publish it first.');
        }
    }
}
