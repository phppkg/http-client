# HTTP Client 

[![License](https://img.shields.io/packagist/l/php-comp/http-client.svg?style=flat-square)](LICENSE)
[![Php Version](https://img.shields.io/badge/php-%3E=7.0-brightgreen.svg?maxAge=2592000)](https://packagist.org/packages/php-comp/http-client)
[![Latest Stable Version](http://img.shields.io/packagist/v/php-comp/http-client.svg)](https://packagist.org/packages/php-comp/http-client)

PHP http client library.

- 可用的驱动包括: `curl` `swoole` `fsockopen` `stream` `fopen`
- 支持 `GET,POST,PATCH,PUT,HEAD,DELETE` 等请求方法
- 支持设置代理，自定义headers
- 实现接口 [PSR 18](https://github.com/php-fig/http-client) 

## 安装

```bash
composer require php-comp/http-client
```

## 使用

### 创建客户端实例

```php
// use factory
$client = Client::factory([
    'driver' => 'curl', // stream, fsock, fopen, file, co, co2
    
    // ... 更多选项
]);

// 或者直接使用指定的类
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
$client->get('/users/1');

$post = ['name' => 'john'];
$client->post('/users/1', $post);

// add ajax header
$client->byAjax()->post('/users/1', $post);

// add json content type
$client->byJson()->post('/users/1', json_encode($post));

$statusCode = $client->getStatusCode();
$headers = $client->getResponseHeaders();
$data = $client->getResponseBody();
$array = $client->getArrayData();
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

## LICENSE

[MIT](LICENSE)
