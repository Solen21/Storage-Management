<?php
// This file should be included at the very top of header.php

// 1. Set default language if not already set
if (!isset($_SESSION['lang'])) {
    $_SESSION['lang'] = 'en'; // Default to English
}

// 2. Include the language file
$lang_file = __DIR__ . '/../lang/' . $_SESSION['lang'] . '.php';
if (file_exists($lang_file)) {
    include_once $lang_file;
} else {
    // Fallback to English if the language file doesn't exist
    include_once __DIR__ . '/../lang/en.php';
}

/**
 * Translation function
 * Takes a key and returns the translated string.
 * If the key is not found, it returns the key itself.
 * @param string $key The key for the language string.
 * @return string The translated string.
 */
function __($key) {
    global $lang;
    return $lang[$key] ?? $key;
}