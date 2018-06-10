<?php
require_once dirname(__DIR__) . '/vendor/aliyuncs/oss-sdk-php/autoload.php';

function ezLoader($class)
{
    $class = str_replace('Ezspider\\','',$class);
    $path = str_replace('\\', DIRECTORY_SEPARATOR, $class);
    $file = __DIR__ . DIRECTORY_SEPARATOR . $path . '.php';
    echo $class."\t";
    echo $file."\n";
    if (file_exists($file)) {
        require_once $file;
    }
}
spl_autoload_register('ezLoader');

