<?php
/**
 * tpshop
 * ============================================================================
 * * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * $Author: IT宇宙人 2015-08-10 $
 */ 
namespace Api\Controller;
use Think\Controller;
class BaseController extends Controller {

    function save_log($res,$url,$type,$functionName) {
        $date = date("Y-m-d", time());
        //$address = '/var/log/error';
        $address = './Application/Api/log';
        if (!is_dir($address)) {
            mkdir($address, 0777, true);
        }
        $address = $address.'/'.$date . '_'.$type.'.log';
        $error_date = date("Y-m-d H:i:s", time());
        if(!empty($_SERVER['HTTP_REFERER'])) {
            $file = $_SERVER['HTTP_REFERER'];
        } else {
            $file = $_SERVER['REQUEST_URI'];
        }

        $res_real = "$error_date\t$file\t$functionName";
        file_put_contents($address, $res_real . PHP_EOL, FILE_APPEND);
        file_put_contents($address, $url . PHP_EOL, FILE_APPEND);
        $res = var_export($res,true);
        $res = $res."\n";
        file_put_contents($address, $res . PHP_EOL, FILE_APPEND);

    }
}
