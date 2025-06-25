
<?php

namespace anuragsingk\LaravelAiTranslator\Commands;

use Illuminate\Console\Command;

class ShowConfigCommand extends Command
{
    protected $signature = 'ai-translate:show-config';
    protected $description = 'Display the current configuration of the AI Translator package.';

    public function handle()
    {
        $this->info('Current Laravel AI Translator Configuration:');

        $config = config('ai-translator');

        $headers = ['Setting', 'Value'];
        $rows = [];

        foreach ($config as $key => $value) {
            if ($key === 'api_key') {
                $rows[] = [$key, $value ? '********' : 'Not Set'];
            } elseif (is_array($value)) {
                $rows[] = [$key, json_encode($value, JSON_PRETTY_PRINT)];
            } else {
                $rows[] = [$key, $value];
            }
        }

        $this->table($headers, $rows);

        return Command::SUCCESS;
    }
}
