<?php declare(strict_types=1);
/**
 * This file is part of php-comp/http-client.
 *
 * @author   https://github.com/inhere
 * @link     https://github.com/php-comp/http-client
 * @license  MIT
 */

error_reporting(E_ALL | E_STRICT);
date_default_timezone_set('Asia/Shanghai');

$namespaces = [
    'PhpComp\Http\ClientTest\\' => __DIR__,
    'PhpComp\Http\Client\\'     => dirname(__DIR__) . '/src',
];

spl_autoload_register(static function ($class) use ($namespaces): void {
    foreach ($namespaces as $np => $dir) {
        if (0 === strpos($class, $np)) {
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
