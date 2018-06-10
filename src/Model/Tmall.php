<?php
/**
 * Created by PhpStorm.
 * User: shjia
 * Date: 2018/6/8
 * Time: 下午10:42
 */

namespace Ezspider\Model;

use Ezspider\Common;
use Contracts\GoodsPlatform;

class Tmall extends Base implements GoodsPlatform{
    public $itemid = '';

    public function __construct($itemid){
        $this->itemid = $itemid;
    }

    private function getTmallGoodsPage($itemid){
        if(empty($itemid))        return;
        if(empty($this->goodsPageHtml)){
            $link = "https://detail.m.tmall.com/item.htm?id=". $itemid;
            $request = Common::getRequestClient();
            $request->set_curlopts(array(CURLOPT_FOLLOWLOCATION=>false));
            $request->set_request_url($link);

            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            $html = $request->get_response_body();
            if( !empty($request->get_response_header()['info']['redirect_url'])) {
                #Log::record('[ ImportModel/'.__FUNCTION__.' ] ' . $link ."\n". var_export($request, true), 'debug');
            }
            $this->goodsPageHtml = $this->precheckObjectEncoding($html);
            #Log::write('[ ImportModel/'.__FUNCTION__.' ] ' . $request->request_url .' '. $request->get_response_code() .' '. $request->get_response_header()['info']['redirect_url'] , 'debug');
        }
        return $this->goodsPageHtml;
    }

    public function getGoodsTitle(){
        $html = $this->getTmallGoodsPage($this->itemid);
        preg_match('/<div\sclass="module-title"[^>]*>(.*)<\/div>/isU',$html, $title);
        //print_r($title);
        if(!empty($title)){
            $this->goodsName = strip_tags($title[1]);
            #Log::write('[ ImportModel/'.__FUNCTION__.' ] ' . '获取到的标题为'. $this->goodsName, 'debug');
            return $this->goodsName;
        }
    }

    public function getPreviewImg(){
        $html = $this->getTmallGoodsPage($this->itemid);
        if(empty($html)){
            return;
        }
        //preg_match_all('/<div\sclass="scroller\spreview-scroller">.*(<img.*\/>)<\/div>/isU', $html, $title);
        preg_match_all('/<img\s.*data-src="(.*)".*"商品主图"\/?>/isU', $html, $imagelist);
        //print_r($imagelist);
        #Log::write( sprintf('[%s/%s]匹配到%d张缩略图', __CLASS__,__FUNCTION__,count($imagelist[1])), 'debug');
        if(!empty($imagelist[1])){
            foreach($imagelist[1] as $k => $v){
                if(substr($v,0,2) == "//"){
                    $imagelist[1][$k] = 'http:'.$v;
                }
            }
	    return $imagelist[1];
	    /*
            $localImgList = $this->downloadImages($imagelist[1]);
            if(empty($localImgList)){
                return;
            }
            $ossFileList = $this->uploadImages($localImgList);
            if(!empty($ossFileList)){
                foreach($ossFileList as $k => $v){
                    $previewImg[] = $v;
                }
                //print_r($previewImg);exit;
                return json_encode($previewImg);
            }
	    */
        }
        return ;
    }

    public function getDetails(){
        $html = $this->getTmallGoodsPage($this->itemid);
        if(empty($html))        return;
        preg_match_all('/<div\sclass="mui-custommodule-item[^\"]*">.*data-ks-lazyload=\"([^\"]*)\".*<\/div>/isU', $html, $match);
        if(empty($match[1])){
            #Log::record('[ ImportModel/'.__FUNCTION__.' ] 没有匹配到商品详情' . var_export($html, true), 'debug');
            return;
        }
        //Log::write('[ ImportModel/'.__FUNCTION__.' ] ' . var_export($match[1], true), 'debug');
	return $match[1];
        $localImgList = $this->downloadImages($match[1]);
        if(empty($localImgList))        return;
        $ossFileList = $this->uploadImages($localImgList);
        if(!empty($ossFileList)){
            foreach($ossFileList as $k => $v){
                $detail[] = array('type'=>'img','value'=>$v);
            }
            //print_r($detail);exit;
            return json_encode($detail);
        }
        return ;
    }
}
