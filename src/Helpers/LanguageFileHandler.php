<?php

namespace anuragsingk\LaravelAiTranslator\Helpers;

use Illuminate\Support\Facades\File;

class LanguageFileHandler
{
    public static function readJsonFile(string $path): array
    {
        if (File::exists($path)) {
            return json_decode(File::get($path), true);
        }
        return [];
    }

    public static function writeJsonFile(string $path, array $data)
    {
        File::put($path, json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }

    public static function readPhpFile(string $path): array
    {
        if (File::exists($path)) {
            return require $path;
        }
        return [];
    }

    public static function writePhpFile(string $path, array $data)
    {
        $content = '<?php\n\nreturn ' . var_export($data, true) . ';';
        File::put($path, $content);
    }

    public static function getLanguageFilePath(string $language, string $fileName, string $type): string
    {
        if ($type === 'json') {
            return lang_path("{$language}.json");
        } elseif ($type === 'php') {
            return lang_path("{$language}/{$fileName}");
        }
        return '';
    }
}
