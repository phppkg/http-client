<?php declare(strict_types=1);
/**
 * This file is part of phppkg/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/phppkg/http-client
 * @license  MIT
 */

error_reporting(E_ALL);
date_default_timezone_set('Asia/Shanghai');

$namespaces = [
    'PhpPkg\Http\ClientTest\\' => __DIR__,
    'PhpPkg\Http\Client\\'     => dirname(__DIR__) . '/src',
];

spl_autoload_register(static function ($class) use ($namespaces): void {
    foreach ($namespaces as $np => $dir) {
        if (str_starts_with($class, $np)) {
            $path = str_replace('\\', '/', substr($class, strlen($np)));
            $file = $dir . "/$path.php";

            if (is_file($file)) {
                __my_include_file($file);
            }
        }
    }
});

if (file_exists($file = dirname(__DIR__) . '/vendor/autoload.php')) {
    require $file;
} elseif (file_exists($file = dirname(__DIR__, 3) . '/autoload.php')) {
    require $file;
}

function __my_include_file($file): void
{
    require $file;
}
