<?php
namespace Ezspider\Contracts;

interface GoodsPlatform{
    //商品名称
    public function getGoodsTitle();

    //商品缩略图
    public function getPreviewImg();

    //商品详情
    public function getDetails();

}

