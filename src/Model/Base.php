<?php
namespace Ezspider\Model;

use OSS\Core\OssUtil;

class Base{

    /**
     * 检查html的编码，如果是gbk或者gb2312则尝试将其转化为utf8编码
     *
     * @param mixed $options 参数
     */
    public function precheckObjectEncoding($html) {
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
