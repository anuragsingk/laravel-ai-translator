# Laravel AI Translator

![Laravel AI Translator Screenshot](https://i.ibb.co/cMkJtQc/Screenshot-2025-06-25-112121.png)

**Laravel AI Translator** is a powerful package designed to automate the translation of your application's language files using **Google Gemini AI Studio API**. It simplifies your localization workflow, letting you scale your app to multiple languages with ease and precision.

---

## ✨ Features

- **AI-Powered Translations**  
  High-quality, context-aware translations powered by Google Gemini AI.

- **Dual File Support**  
  Automatically translates both JSON files (`resources/lang/en.json`) and PHP array files (`resources/lang/en/messages.php`).

- **Smart Updates**  
  Detects and translates only missing or identical keys, preserving your custom edits.

- **Structured Arrays Preserved**  
  Maintains the original structure of nested PHP arrays without any flattening.

- **Interactive CLI**  
  Artisan commands make setup, translation, and management intuitive and fast.

- **Dry Run Mode**  
  Preview translations directly in the console before saving them.

- **Configurable & Extensible**  
  Customize API keys, source/target languages, and AI prompts via config file.

- **Laravel Ready**  
  Compatible with Laravel 10.x, 11.x and PHP >= 8.1.

- **Robust Error Handling**  
  Graceful messaging for API errors and misconfigurations.

---

## 🚀 Installation

Install the package via Composer:

```bash
composer require anuragsingk/laravel-ai-translator
```

---

## 🛠️ Setup

Before you begin, you’ll need a **Google Gemini API Key**.  
👉 [Generate your API Key from Google AI Studio](https://aistudio.google.com/apikey)

After that, run the setup command to configure the package:

```bash
php artisan ai-translate:setup
```

This command will:

1. Prompt for your Gemini API key (saved as `AI_TRANSLATOR_API_KEY` in `.env`)
2. Auto-detect source language files (e.g., `en.json`, `en/messages.php`)
3. Attempt to auto-detect the source language (e.g., `en`) and confirm it

_Optional_: To manually publish the configuration file:

```bash
php artisan vendor:publish --tag=ai-translator-config
```

---

## 📖 Usage

### Add a New Language

```bash
php artisan ai-translate:add-language es
```

Translates your source language files into a new target language (e.g., Spanish `es`) and saves them as:

- `resources/lang/es.json`
- `resources/lang/es/messages.php`

---

### Update Existing Translations

```bash
php artisan ai-translate:update
```

Updates all configured target languages by translating only missing or unchanged keys.

---

### Preview (Dry Run)

```bash
php artisan ai-translate:update --dry-run
```

Previews the translations in your console without writing to any files.

---

### Delete a Language

```bash
php artisan ai-translate:delete-language ta
```

Deletes all language files associated with a specific language code.

---

### List Configured Languages

```bash
php artisan ai-translate:list
```

Displays source and target languages and associated files.

---

### Show Current Configuration

```bash
php artisan ai-translate:show-config
```

Displays the current configuration (API key masked).

---

## 📂 File Handling

### JSON File Example

```json
{
    "welcome": "Welcome to our application!",
    "greeting": "Hello, {name}!",
    "messages": {
        "success": "Operation completed successfully.",
        "error": "An error occurred."
    }
}
```

### PHP Array File Example

```php
<?php

return [
    'welcome_message' => 'Welcome to our website!',
    'goodbye_message' => 'Goodbye, see you soon!',
    'validation' => [
        'required' => 'The :attribute field is required.',
        'email' => 'The :attribute must be a valid email address.',
    ],
];
```

The package maintains file structure and nesting in both formats.

---

## 🧭 Roadmap

- ✅ Enhanced interactive CLI experience  
- ✅ Dry run mode for safe previews  
- 🔜 Native PHP support (without Laravel dependency)  
- 🔜 Inline review & editing before saving translations  
- 🔜 Support for alternative APIs: **OpenAI**, **DeepSeek**, **Google Translate**, **DeepL**  
- 🔜 File merge options before translation  
- 🔜 Detailed logging to `storage/logs/ai-translator.log`  
- 🔜 Explicit option to skip unchanged keys  
- 🔜 Per-file custom AI prompts  
- 🔜 Comprehensive test suite

---

## 🤝 Contributing

Contributions are welcome! To contribute:

1. Fork the repo  
2. Create a feature branch  
3. Make your changes and commit  
4. Write tests (if applicable)  
5. Open a Pull Request to `main`

For local development, link the package using Composer's `path` repository feature:

```json
"repositories": [
    {
        "type": "path",
        "url": "../path/to/your/local/laravel-ai-translator"
    }
],
"require": {
    "anuragsingk/laravel-ai-translator": "*"
}
```

Then run:

```bash
composer update
```

---
