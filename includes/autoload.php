<?php
/**
 * Custom autoloader for the PDF Parser library
 */
spl_autoload_register(function ($class) {
    // Only handle Smalot PDF Parser classes
    if (strpos($class, 'Smalot\\PdfParser\\') !== 0) {
        return;
    }

    // Convert namespace to path
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $path = BP_OPENAI_PDF_CHAT_PLUGIN_DIR . 'vendor' . DIRECTORY_SEPARATOR . 
           substr($path, strrpos($class, 'Smalot')) . '.php';

    if (file_exists($path)) {
        require_once $path;
    }
});