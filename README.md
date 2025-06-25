# Laravel AI Translator

A Laravel package that allows developers to automatically translate their language files (JSON or PHP array) using Google Gemini AI Studio API.

## Installation

YouYou can install the package via composer:

```bash
composer require anuragsingk/laravel-ai-translator
```

## Setup

After installing the package, you should run the setup command:

```bash
php artisan ai-translate:setup
```

This command will guide you through entering your Gemini API Key and configuring your source language files.

## Usage

### Add new language

```bash
php artisan ai-translate:add-language hi
```

### Update all languages

```bash
php artisan ai-translate:update
```

### Delete language

```bash
php artisan ai-translate:delete-language ta
```

### List current languages

```bash
php artisan ai-translate:list
```

### Show saved config

```bash
php artisan ai-translate:show-config
```

## Features

- Supports JSON and PHP array language files.
- Keeps PHP array structure intact during translation.
- Only translates missing keys when updating.
- "Dry run" option to preview translations.
- Compatible with Laravel 10.x, 11.x, and PHP >= 8.1.
- User-friendly error handling for Gemini API failures.
- Allows custom AI prompt for better translation quality.

## Contributing

Contributions are welcome! Please see [CONTRIBUTING.md](CONTRIBUTING.md) for details.

## License

This package is open-sourced software licensed under the [MIT license](LICENSE.md).
