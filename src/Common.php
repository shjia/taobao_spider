<?php
namespace Ezspider;

use OSS\Http\RequestCore;

class Common{

    public static function getRequestClient(){
        $request = new RequestCore();
        $request->add_header('Cookie', 't=f641f8c8f8b2ade251bfafd931ee9f23; _tb_token_=3037ef7ad1e7c; cookie2=18fbcdc3d10128c59d98a7386311bcfb;');
        $request->set_curlopts(array(CURLOPT_FOLLOWLOCATION=>false));
        $request->set_useragent('Mozilla/5.0 (iPhone; CPU iPhone OS 9_1 like Mac OS X) AppleWebKit/601.1.46 (KHTML, like Gecko) Version/9.0 Mobile/13B143 Safari/601.1');
        return $request;
    }   

}
