# php http client 

[![License](https://img.shields.io/packagist/l/php-comp/http-client.svg?style=flat-square)](LICENSE)
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg?maxAge=2592000)](https://packagist.org/packages/php-comp/http-client)
[![Latest Stable Version](http://img.shields.io/packagist/v/php-comp/http-client.svg)](https://packagist.org/packages/php-comp/http-client)

PHP http client library.

- driver contains: `curl` `swoole`
- implement the [PSR 18](https://github.com/php-fig/http-client)

## Install

```bash
composer require php-comp/http-client
```

## Usage

### CURL

- simple

```php
use PhpComp\Http\Client\Curl\Curl;

$curl = Curl::create([
  'baseUrl' =>  'http://my-site.com'
]);
$curl->get('/users/1');

$headers = $curl->getResponseHeaders();
$data = $curl->getResponseBody();
$array = $curl->getArrayData();

$post = ['name' => 'john'];
$curl->reset()->post('/users/1', $post);
// $curl->reset()->byAjax()->post('/users/1', $post);
// $curl->reset()->byJson()->post('/users/1', json_encode($post));
$array = $curl->getArrayData();
```

- file upload/download

```text
    public function upload(string $url, string $field, string $filePath, string $mimeType = '')
    public function download(string $url, string $saveAs)
    public function downloadImage(string $imgUrl, string $saveDir, string $rename = '')
```

```php
$curl = Curl::create([
  // ...
]);

$curl->upload(...);
```

## LICENSE

[MIT](LICENSE)
