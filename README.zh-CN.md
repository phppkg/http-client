# HTTP Client 

[![License](https://img.shields.io/packagist/l/phppkg/http-client.svg?style=flat-square)](LICENSE)
[![Php Version](https://img.shields.io/packagist/php-v/phppkg/http-client?maxAge=2592000)](https://packagist.org/packages/phppkg/http-client)
[![Latest Stable Version](http://img.shields.io/packagist/v/phppkg/http-client.svg)](https://packagist.org/packages/phppkg/http-client)
[![GitHub tag (latest SemVer)](https://img.shields.io/github/tag/phppkg/http-client)](https://github.com/phppkg/http-client)
[![Github Actions Status](https://github.com/phppkg/http-client/workflows/Unit-tests/badge.svg)](https://github.com/phppkg/http-client/actions)

An easy-to-use HTTP client library for PHP. Support CURL, file, fsockopen, stream drivers.

- 简单易于使用的HTTP客户端
- 可用的驱动包括: `curl` `swoole` `fsockopen` `stream` `fopen`
- 支持 `GET,POST,PATCH,PUT,HEAD,DELETE` 等请求方法
- 支持设置代理，自定义headers，auth，content-type 等
- 实现接口 [PSR 18](https://github.com/php-fig/http-client) 

## 安装

```bash
composer require phppkg/http-client
```

## 使用

### 创建客户端实例

**自动选择驱动类**:

```php
use PhpPkg\Http\Client\Client;

// use factory
$client = Client::factory([
    'driver' => 'curl', // stream, fsock, fopen, file, co, co2
    
    // ... 更多选项
    'baseUrl' =>  'http://my-site.com'
]);
```

**直接使用指定的类**:

```php
$options = [
  'baseUrl' =>  'http://my-site.com'
  // ...
];
$client = CurlClient::create($options);
$client = FileClient::create($options);
$client = FSockClient::create($options);
$client = FOpenClient::create($options);
$client = CoClient::create($options);
```

### 基本使用

```php
use PhpPkg\Http\Client\Client;

$client = Client::factory([]);

$client->get('/users/1');

$post = ['name' => 'john'];
$client->post('/users/1', $post);

// add ajax header
$client->byAjax()->post('/users/1', $post);

// add json content type
$client->json('/users/1', json_encode($post));
// or
$client->byJson()->post('/users/1', json_encode($post));

$statusCode = $client->getStatusCode();
$headers = $client->getResponseHeaders();

// get body data
$data = $client->getResponseBody();
$array = $client->getArrayData();
```

**解析响应Body**:

```php
$data = $client->getDataObject();
$data->getInt('createTime', 0);

$user = new User();
$client->bindBodyTo($user);
vdump($user->name);
```

### 文件上传下载

- `public function upload(string $url, string $field, string $filePath, string $mimeType = '')`
- `public function download(string $url, string $saveAs)`
- `public function downloadImage(string $imgUrl, string $saveDir, string $rename = '')`

```php
$client = CurlClient::create([
  // ...
]);

$client->upload(...);
```

## 常用方法

- `getJsonArray/getArrayData(): array`
- `getJsonObject(): stdClass`
- `getDataObject(): DataObject`
- `bindBodyTo(object $obj): void`

## LICENSE

[MIT](LICENSE)
