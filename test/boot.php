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

$inhereDir = dirname(__DIR__, 2);
$map = [
    'PhpComp\Http\Client\Test\\' => __DIR__,
    'PhpComp\Http\Client\\' => dirname(__DIR__) . '/src',
];

spl_autoload_register(function ($class) use ($map): void {
    foreach ($map as $np => $dir) {
        if (0 === strpos($class, $np)) {
            $path = str_replace('\\', '/', substr($class, strlen($np)));
            $file = $dir . "/{$path}.php";

            if (is_file($file)) {
                my_include_file($file);
            }
        }
    }
});
if (file_exists($file = dirname(__DIR__) . '/vendor/autoload.php')) {
    require $file;
} elseif (file_exists($file = dirname(__DIR__, 3) . '/autoload.php')) {
    require $file;
}

function my_include_file($file): void
{
    include $file;
}
