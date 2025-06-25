
<?php

namespace anuragsingk\LaravelAiTranslator\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DeleteLanguageCommand extends Command
{
    protected $signature = 'ai-translate:delete-language {language}';
    protected $description = 'Delete a translated language and its files.';

    public function handle()
    {
        $language = $this->argument('language');

        if (!in_array($language, config('ai-translator.target_languages'))) {
            $this->error("Language '{$language}' is not configured as a target language.");
            return Command::FAILURE;
        }

        if ($this->confirm("Are you sure you want to delete all files for language '{$language}'?")) {
            $this->info("Deleting language '{$language}'...");

            // Delete JSON files
            $jsonFilePath = lang_path("{$language}.json");
            if (File::exists($jsonFilePath)) {
                File::delete($jsonFilePath);
                $this->line("Deleted: {$jsonFilePath}");
            }

            // Delete PHP language directory
            $phpLangDir = lang_path($language);
            if (File::isDirectory($phpLangDir)) {
                File::deleteDirectory($phpLangDir);
                $this->line("Deleted directory: {$phpLangDir}");
            }

            $this->removeTargetLanguage($language);

            $this->info("Language '{$language}' deleted successfully.");
            return Command::SUCCESS;
        }

        $this->info("Deletion cancelled.");
        return Command::SUCCESS;
    }

    protected function removeTargetLanguage(string $language)
    {
        $configPath = config_path('ai-translator.php');
        $config = require $configPath;
        $targetLanguages = data_get($config, 'target_languages', []);
        $updatedTargetLanguages = array_diff($targetLanguages, [$language]);
        data_set($config, 'target_languages', array_values($updatedTargetLanguages));
        File::put($configPath, '<?php\n\nreturn ' . var_export($config, true) . ';');
    }
}
