<?php
namespace anuragsingk\LaravelAiTranslator;

use Illuminate\Support\ServiceProvider;

class AiTranslatorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $config = config('ai-translator', []);
        if (!is_array($config)) {
            $config = require __DIR__ . '/Config/ai-translator.php';
        }
        $this->mergeConfigFrom(
            __DIR__ . '/Config/ai-translator.php',
            'ai-translator'
        );
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/Config/ai-translator.php' => config_path('ai-translator.php'),
            ], 'ai-translator-config');

            $this->commands([
                Commands\SetupCommand::class,
                Commands\AddLanguageCommand::class,
                Commands\UpdateCommand::class,
                Commands\DeleteLanguageCommand::class,
                Commands\ListCommand::class,
                Commands\ShowConfigCommand::class,
            ]);
        }
    }
}