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

class JxcapiController extends Controller {

    const SERVER_IP="http://n.llegg.cn";

    public function index(){      
        $this->display();
    }

    public function getAllUser()
    {
        $users = M("users")->select();
        foreach ($users as $k=>$v){
            $users[$k]['address']=M("user_address ua")
                ->field("ua.mobile,
                              ua.address,
                              ua.consignee,
                              ua.address_id,
                              ua.is_default,
                              (SELECT `name` FROM tp_region WHERE id=province) province_str,
                              (SELECT `name` FROM tp_region WHERE id=city) city_str,
                              (SELECT `name` FROM tp_region WHERE id=district) country_str
                              ")
                ->where("ua.user_id=".$v['user_id'])
                ->select();
        }
        $this->ajaxReturn($users);
    }

    public function insertUser($data){
        $user="";

        $user['name']=$data['nickname'];
        $user['number']="DS".$data['user_id'];
        $user['cCategory']="5";
        $user['cCategoryName']="001";
        $user['cLevelName']=iconv('GB2312', 'UTF-8',"零售客户");
        $user['cLevel']="0";
        $user['type']=-10;
        $user['shop_user_id']=$data['user_id'];
        $user['address']=M("user_address ua")
                        ->field("ua.mobile,
                                          ua.address,
                                          ua.consignee,
                                          ua.address_id,
                                          ua.is_default,
                                          (SELECT `name` FROM tp_region WHERE id=province) province_str,
                                          (SELECT `name` FROM tp_region WHERE id=city) city_str,
                                          (SELECT `name` FROM tp_region WHERE id=district) country_str
                                          ")
                        ->where("ua.user_id=".$data['user_id'])
                        ->select();
        $this->postData($user,"api/insertUser");
    }

    public function updateUser($data,$userId){
        $user="";

        $user['name']=$data['nickname'];
        $user['number']="DS".$userId;
        $user['cCategory']="5";
        $user['cCategoryName']="001";
        $user['cLevelName']="零售客户";
        $user['cLevel']="0";
        $user['type']=-10;
        $user['userId']=$userId;
        $this->postData($user,"api/updateUser");
    }

    public function postData($data,$url){
        $url = self ::SERVER_IP."/index.php/".$url;
//        $url = "http://e.com/index.php/".$url;
        $post_data = $data;
        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        // post数据
        curl_setopt($ch, CURLOPT_POST, 1);
        // post的变量
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $output = curl_exec($ch);
        curl_close($ch);
    }
    
}