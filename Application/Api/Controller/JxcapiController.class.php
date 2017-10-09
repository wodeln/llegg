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

class JxcapiController extends BaseController{

    const SERVER_IP="http://e.com";

    public function index(){      
        $this->display();
    }

    //用户API开始

    /**
     * 获取所有用户
     */
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

    /**
     * 添加用户
     * @param $data 用户信息
     */
    public function insertUser($data){
        $user="";

        $user['name']=$data['nickname'];
        $user['number']="DS".$data['user_id'];
        $user['cCategory']="5";
        $user['cCategoryName']="001";
        $user['cLevelName']="零售客户";
        $user['cLevel']="0";
        $user['type']=-10;
        $user['shop_user_id']=$data['user_id'];
        $address=M("user_address ua")
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
        $user['linkMans']=json_encode($address,JSON_UNESCAPED_UNICODE);
        $this->postData($user,"api/insertUser","user",__FUNCTION__);
    }

    /**
     * 编辑用户
     * @param $data 用户信息
     * @param $userId 用户ID
     */
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
        $this->postData($user,"api/updateUser","user",__FUNCTION__);
    }
    //用户API结束

    //商品API开始
    /**
     * 初始化进销存商品信息
     */
    public function initGoods(){
        $goodsList = M('goods g')
                    ->field('g.*,gc.name category_name,gt.name spec_name')
                    ->join("tp_goods_category gc ON g.cat_id=gc.id")
                    ->join("tp_goods_type gt ON g.spec_type=gt.id")
                    ->select();
        foreach ($goodsList as $k=>$v){
            $goods="";
            $goods['name'] = $v['goods_name'];
            $goods['number'] = $v['goods_sn'];
            $goods['unitName'] = $v['spec_name'];
            $goods['categoryName'] = $v['category_name'];
            $goods['shop_goods_id'] = $v['goods_id'];
            $this->postData($goods,"api/insertGoods","goods",__FUNCTION__);
        }
    }

    /**
     * 添加编辑商品信息
     * @param $data 商品信息
     * @param $type 1 表示插入 2 表示更新
     */
    public function insertEditGoods($data,$goods_id,$type){
        $goods="";
        $goods['name'] = $data['goods_name'];
        $goods['number'] = $data['goods_sn'];
        $goods['unitName'] = M('goods_type')->where('id='.$data['spec_type'])->getField('`name`');
        $goods['categoryName'] = M('goods_category')->where('id='.$data['cat_id_3'])->getField('`name`');
        $goods['shop_goods_id'] = $goods_id;
        $url = $type==1?"api/insertGoods":"api/updateGoods";
        $this->postData($goods,$url,"goods",__FUNCTION__);
    }

    //商品API结束
    /**
     * POST 数据到指定地址
     * @param $data POST 数据
     * @param $url
     * @param string $functionName 调用 postDate 的方法名
     */
    public function postData($data,$url,$type,$functionName=""){
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
//        $output = "ok";
        curl_close($ch);
        if($output!=""){
            $this->save_log($data,$url,$type,$functionName);
        }
    }

/*
 * 劳尔  10:07:15
网商到进销存（单向）
客户资料（自动，网商客户id唯一识别号），
订单资料（客服确认订单后触发同步，网商订单号写入进销存订单表备注 ），
商品资料（自动，sku编号唯一识别号）
进销存到网商 （库存同步，手动操作，提供同步按钮，sku编号唯一识别号）
*/
}