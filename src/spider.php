<?php
/**
 * Created by PhpStorm.
 * User: shjia
 * Date: 2018/6/8
 * Time: 下午10:39
 */
namespace Ezspider;

use Ezspider\Common;
use Ezspider\Model\Tmall;

require_once __DIR__ . '/autoload.php';

class spider{

    private $goodsPlatform ;
    private $shareLink = '';//用户分享的链接
    private $goodsName = '';//商品名称
    private $goodsPageHtml = '';//商品页html代码
    private $debugMode = false;
    public $itemid = '';//商品id
    public $userid = '';//用户id

    public function __construct(){
    }

    public function setItemId($itemid){
        $this->itemid = $itemid;
    }

    public function initGoodsPlatform(){
        if(empty($this->itemid) ){return;}
        if( $this->isTmallGoods($this->itemid)){
            $this->goodsPlatform = new Tmall($this->itemid);
        }else{
            $this->goodsPlatform = new Model\Taobao($this->itemid);
        }
        return $this->goodsPlatform;
    }

    //解析文本信息，判断是京东链接还是天猫链接
    public function isValidShareMessage($content){
        preg_match('/(https?:\/\/[a-zA-Z0-9\.\/]+)/is', $content, $goodsLink);
        $this->debugMode && Log::debug('[ ImportModel/'.__FUNCTION__.' ] 识别到的链接' . var_export($goodsLink[1], true));

        if(empty($goodsLink[1])){
            //Log::record('[ ImportModel/'.__FUNCTION__.' ] 无效的文本' . var_export($content, true), 'debug');
            return;
        }
        return $goodsLink[1];

    }

    /**
     * 形如v.cvz5.com的域名无法正常解析时，做一个替换
     *
     */
    public function replaceDomain($link){
        $host = parse_url($link, PHP_URL_HOST);
        if(!empty($host) && $host!='cname.tb.cn'){
            return str_replace($host, 'cname.tb.cn', $link);
        }
        return '';
    }

    public function getGoodsLink($shareLink){
        if(empty($shareLink)){
            return;
        }
        $html = $this->curl_get($shareLink);
        //$this->debugMode && Log::debug('[ ImportModel/'.__FUNCTION__.' ] HTML '. var_export($html, true));
        if (empty($html)) {
            Log::record('[ ImportModel/'.__FUNCTION__.' ] ' . 'HTML获取失败,Link:'.$shareLink, 'debug');
            return $this->getGoodsLink($this->replaceDomain($shareLink));
        }
        //形如https://a.m.taobao.com/i552304055876.htm?price=400
        preg_match("/https?:\/\/a.m.taobao.com\/i(\d+).htm/isU", $html, $redirectUrl);
        $this->debugMode && Log::debug('[ ImportModel/'.__FUNCTION__.' ] redirectUrl '. var_export($redirectUrl, true));
        if(!empty($redirectUrl[1])){
            $this->itemid = $redirectUrl[1];
            return $shareLink;
        }
        preg_match("/'(https?:\/\/.*)'/isU",$html, $h5link);
        if (empty($h5link)) {
            Log::record('[ ImportModel/'.__FUNCTION__.' ] ' . 'H5链接匹配失败', 'debug');
            return;
        }
        $this->debugMode && Log::debug('[ ImportModel/'.__FUNCTION__.' ] H5链接 '. var_export($h5link[1], true), 'debug');
        $request = Common::getRequestClient();
        $request->set_curlopts(array(
            CURLOPT_FOLLOWLOCATION=>true
        ));
        $request->set_request_url($h5link[1]);

        try {
            $request->send_request();
        } catch (RequestCore_Exception $e) {
            //throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            return;
        } catch (Exception $e){
            Log::record('[ ImportModel/'.__FUNCTION__.' ] 无效的文本' . var_export($header, true), 'debug');
            return;
        }
        $header = $request->get_response_header();
        if (empty($header)) {
            # code...
            Log::record('[ ImportModel/'.__FUNCTION__.' ] ' . '二次跳转页面获取为空', 'debug');
            return;
        }

        if(!empty($header['info']['redirect_url'])) {
            Log::write('[ ImportModel/'.__FUNCTION__.' ] ' . '链接跳转至: '. $header['info']['redirect_url'], 'debug');
            return trim($header['info']['redirect_url']);
        }
        $this->debugMode && Log::debug('[ ImportModel/'.__FUNCTION__.' ] ' . '获取到的链接地址为: '. $header['info']['url']);
        $link = $header['info']['url'];
        if(empty($this->parseItemidFromUrl($link))){
            return;
        }
        return $link;
    }

    /**
     * 获取淘宝itemid
     */
    public function parseItemidFromUrl($link){
        if(empty($link))        return;
        $urlinfo = parse_url($link);
        $this->debugMode && Log::debug('[ ImportModel/'.__FUNCTION__.' ] ' . var_export($urlinfo, true), 'debug');
        if(empty($urlinfo['query']))        return;
        parse_str($urlinfo['query'], $params);
        if (!empty($params['id'])) {
            $this->itemid = $params['id'];
        } else if(!empty($params['url'])){
            parse_str($params['url'], $params);
            if(!empty($params['itemId'])){
                $this->itemid = $params['itemId'];
            }
        }
        Log::write('[ ImportModel/'.__FUNCTION__.' ] ' . $link .'获取到的itemId为'. $this->itemid , 'debug');
        return $this->itemid;
    }

    public function isTmallGoods($itemid){
        if(empty($itemid))        return false;
        $link = "https://detail.m.tmall.com/item.htm?id=". $itemid;
        $request = Common::getRequestClient();
        $request->set_curlopts(array(CURLOPT_FOLLOWLOCATION=>false));
        $request->set_request_url($link);

        try {
            $request->send_request();
        } catch (RequestCore_Exception $e) {
            //throw(new OssException('RequestCoreException: ' . $e->getMessage()));
            return false;
        }
        //$this->debugMode && Log::record('[ ImportModel/'.__FUNCTION__.' ] ' . $link ."\n". var_export($request, true), 'debug');
        if($request->get_response_code() == 200){
            return true;
        }
        return false;
    }

    public function getGoodsTitle(){
        return $this->goodsPlatform->getGoodsTitle();
    }

    public function getPreviewImg(){
        return $this->goodsPlatform->getPreviewImg();
    }

    public function getDetails(){
        return $this->goodsPlatform->getDetails();
    }

    public function downloadImages($imagelist){
        if(empty($imagelist)) return;
        $this->debugMode && Log::debug('[ ImportModel/'.__FUNCTION__.' ] ' . var_export($imagelist, true));
        foreach($imagelist as $k => $v){
            if(false === strpos($v, 'http'))
                continue;
            $localImg = '/tmp/'. md5($v) .substr($v, strrpos($v, '.'));
            if(!file_exists($localImg)){
                $image = $this->curl_get($v);
                //echo $localImg."\n";
                $success = file_put_contents( $localImg, $image, LOCK_EX);
                if($success !== false){
                    $list[$k] = $localImg;
                } else {
                    Log::write('[ ImportModel/'.__FUNCTION__.' ] ' . '商品图片下载失败', 'debug');
                }
            } else {
                $list[$k] = $localImg;
            }
        }
        Log::write('[ ImportModel/'.__FUNCTION__.' ] ' . '共下载'. count($list) .'/'.count($imagelist) . '张图片', 'debug');
        return $list;
    }


    /**
     * 检查html的编码，如果是gbk或者gb2312则尝试将其转化为utf8编码
     *
     * @param mixed $options 参数
     */
    public function precheckObjectEncoding($html)
    {
        $tmp_obj = $html;
        try {
            if (OssUtil::isGb2312($html)) {
                $tmp_obj = iconv('GB2312', "UTF-8//IGNORE", $html);
            } elseif (OssUtil::checkChar($html, true)) {
                $tmp_obj = iconv('GBK', "UTF-8//IGNORE", $html);
            }
        } catch (\Exception $e) {
            try {
                $tmp_obj = iconv(mb_detect_encoding($tmp_obj), "UTF-8", $tmp_obj);
            } catch (\Exception $e) {
            }
        }
        return $tmp_obj;
    }


}

