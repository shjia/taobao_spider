<?php
require_once dirname(__DIR__) . '/vendor/autoload.php';

function ezLoader($class)
{
    $class = str_replace('Ezspider\\','',$class);
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . DIRECTORY_SEPARATOR . $path . '.php';
    if (file_exists($file)) {
        require_once $file;
    }
}
spl_autoload_register('ezLoader');

