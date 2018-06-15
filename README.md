# 淘宝、天猫商品爬虫

配置淘宝商品ID，通过Web版和H5版本的HTML、API数据结合，抓取淘宝、天猫商品的数据

## Requirement

## Installation

```shell
composer require "shjia/taobao_spider:~1.0"
```

## Usage

```php
<?php
require_once 'src/spider.php';

use Ezspider\spider;

$importModel = new \Ezspider\spider;

$importModel->setItemId('569643840385');
if(empty($importModel->initGoodsPlatform())){
    return;
}

$params['name'] = $importModel->getGoodsTitle();
$params['image'] = $importModel->getPreviewImg();
$params['detail'] = $importModel->getDetails();


print_r($params);

```
