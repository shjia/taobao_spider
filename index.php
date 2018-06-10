<?php
require_once 'src/spider.php';

use Ezspider\spider;

$importModel = new \Ezspider\spider;

$importModel->setItemId('523818840180');
if(empty($importModel->initGoodsPlatform())){
    return;
}

$params['name'] = $importModel->getGoodsTitle();
$params['image'] = $importModel->getPreviewImg();
$params['detail'] = $importModel->getDetails();


print_r($params);


