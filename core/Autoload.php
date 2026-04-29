<?php

class Autoload
{
    public static function register()
    {
        spl_autoload_register(function ($className) {
            $prefixes = [
                'Core\\' => __DIR__ . '/',
                'App\\' => __DIR__ . '/../app/',
            ];

            foreach ($prefixes as $prefix => $baseDir) {
                $len = strlen($prefix);
                if (strncmp($prefix, $className, $len) !== 0) {
                    continue;
                }

                $relativeClass = substr($className, $len);
                $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';

                if (file_exists($file)) {
                    require $file;
                    return;
                }
            }
        });
    }
}

Autoload::register();
