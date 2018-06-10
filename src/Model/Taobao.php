<?php
/**
 * Created by PhpStorm.
 * User: shjia
 * Date: 2018/6/8
 * Time: 下午10:41
 */
namespace Ezspider\Model;

use Ezspider\Common;
use Ezspider\Model\Base;
use Ezspider\Contracts\GoodsPlatform;

class Taobao extends Base implements GoodsPlatform{

    public $goodsConfig ;
    public function __construct($itemid){
        $this->itemid = $itemid;
    }

    private function getGoodsConfig($itemid){
        if(empty($itemid))        return;
        if(empty($this->goodsConfig)){
            $link = "https://item.taobao.com/item.htm?id=".$itemid;

            $request = Common::getRequestClient();
            $request->set_curlopts(array(
                CURLOPT_FOLLOWLOCATION=>false
            ));
            //要设置成PC的UA，防止跳转到H5页
            $request->set_useragent("Mozilla/5.0 (Macintosh; Intel Mac OS X 10_12_6) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/62.0.3202.94 Safari/537.36");
            $request->set_request_url($link);

            try {
                $request->send_request();
            } catch (RequestCore_Exception $e) {
                throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            }
            $html = $request->get_response_body();
            if( !empty($request->get_response_header()['info']['redirect_url'])) {
                //Log::record('[ ImportModel/'.__FUNCTION__.' ] ' . $link ."\n". var_export($request, true), 'debug');
                return;
            }
            $pagehtml = $this->precheckObjectEncoding($html);
            #Log::write('[ ImportModel/'.__FUNCTION__.' ] ' . $request->request_url .' '. $request->get_response_code() .' '. $request->get_response_header()['info']['redirect_url'] , 'debug');
            if(!empty($pagehtml)){
                preg_match('/g_config\s=\s(\{.*\});/isU',$pagehtml, $match);
                if(!empty($match[0])){
                    $this->goodsConfig = $match[1];
                }
            }
        }
        return $this->goodsConfig;
    }

    public function getGoodsTitle(){
        $goodsConfig = $this->getGoodsConfig($this->itemid);
        if(empty($goodsConfig))        return;
        preg_match("/title\s*:\s'(.*)',/isU", $goodsConfig, $match);
        $json = '{"title":"'.$match[1].'"}';
        //print_r($str);
        $title = json_decode($json,true);
        if(!empty($title)){
            return $title['title'];
        }
    }

    public function getPreviewImg(){
        $goodsConfig = $this->getGoodsConfig($this->itemid);
        if(empty($goodsConfig))        return;
        preg_match('/auctionImages\s*:\s(\[.*\])/isU',$goodsConfig,$match);
        $images = json_decode($match[1],true);
        if(!empty($images)){
            foreach($images as $k => $v){
                if(substr($v,0,2) == "//"){
                    $images[$k] = 'http:'.$v;
                }
            }
	    return $images;
            /*
            $localImgList = $this->downloadImages($images);
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
    }

    public function getDetails(){
        $link = 'https://hws.m.taobao.com/cache/desc/5.0?callback=backToDesc&id='.$this->itemid.'&type=1';
        $request = Common::getRequestClient();
        $request->set_curlopts(array(
            CURLOPT_FOLLOWLOCATION=>false
        ));
        $request->set_request_url($link);

        try {
            $request->send_request();
        } catch (RequestCore_Exception $e) {
            throw(new OssException('RequestCoreException: ' . $e->getMessage()));
        }
        $html = $request->get_response_body();
        preg_match('/backToDesc\((.*)\)/',$html, $match);
        if(empty($match))
            return;
        $val = json_decode($match[1],true);
        preg_match_all('/<p[^>]*>.*<\/p>/isU',$val['pcDescContent'], $match2);
        //Log::debug(var_export($match2, true));

        if(empty($match2[0]) && 0==preg_match_all('/<div[^>]*>.*<\/div>/isU', $val['pcDescContent'], $match2) )
            return;
        $detail = array();
        $i = 0;
        foreach($match2[0] as $val){
            preg_match_all('/<img\s[^<]*src="([^\"]*)"[^>]*\/?>/isU', $val, $imgs);
            if(empty($imgs[0])){
                $text = strip_tags($val);
                if(!empty(trim($text)))
                    $detail[$i++] = array('type'=>'text', 'value'=>$text);
            } else {
                foreach($imgs[1] as $img){
                    $j = $i++;
                    $detail[$j] = array('type'=>'img', 'value'=>$img);
                    $imagelist[$j] = $img;
                }
            }
        }
        if(!empty($imagelist)){
            //Log::write('[ '.__CLASS__.'/'.__FUNCTION__.' ] details: ' . var_export($detail,true)  , 'debug');
            foreach($imagelist as $k => $v){
                if(substr($v,0,2) == "//"){
                    $imagelist[$k] = 'http:'.$v;
                }
            }
	    return $imagelist;
	    /*
            $localImgList = $this->downloadImages($imagelist);
            if(empty($localImgList)){
                return;
            }
            $ossFileList = $this->uploadImages($localImgList);
            if(!empty($ossFileList)){
                //Log::write('[ '.__CLASS__.'/'.__FUNCTION__.' ] osslist: ' . var_export($ossFileList, true)  , 'debug');
                foreach($ossFileList as $k => $v){
                    $detail[$k]['value'] = $v;
                }
	    }*/
        }
        return json_encode($detail);
    }
}
