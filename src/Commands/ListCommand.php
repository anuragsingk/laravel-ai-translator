<?php
namespace anuragsingk\LaravelAiTranslator\Commands;

use Illuminate\Console\Command;

class ListCommand extends Command
{
    protected $signature = 'ai-translate:list';
    protected $description = 'List all configured source and target languages.';

    public function handle()
    {
        $this->info('Laravel AI Translator Configuration:');

        $sourceLanguage = config('ai-translator.source_language');
        $this->line("Source Language: <info>{$sourceLanguage}</info>");

        $sourceFiles = config('ai-translator.source_files');
        $this->line("\nSource Files:");
        if (empty($sourceFiles)) {
            $this->line('  No source files configured.');
        } else {
            foreach ($sourceFiles as $type => $files) {
                $this->line("  <comment>" . ucfirst($type) . " Files:</comment>");
                foreach ($files as $file) {
                    $this->line("    - {$file}");
                }
            }
        }

        $targetLanguages = config('ai-translator.target_languages');
        $this->line("\nTarget Languages:");
        if (empty($targetLanguages)) {
            $this->line('  No target languages configured.');
        } else {
            foreach ($targetLanguages as $lang) {
                $this->line("  - <info>{$lang}</info>");
            }
        }

        return Command::SUCCESS;
    }
}
