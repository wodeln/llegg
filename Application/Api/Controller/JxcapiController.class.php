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
use Admin\Logic\OrderLogic;
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
            ->where("ua.user_id=".$userId)
            ->select();
        $user['linkMans']=json_encode($address,JSON_UNESCAPED_UNICODE);
        $this->postData($user,"api/updateUser","user",__FUNCTION__);
    }

    public function updateUserAddress($userId){
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
            ->where("ua.user_id=".$userId)
            ->select();
        $user['linkMans']=json_encode($address,JSON_UNESCAPED_UNICODE);
        $user['userId'] = $userId;
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

    //订单API开始
    /**
     * 初始化订单
     */
    public function initOrder(){
//        $today = strtotime(date("Y-m-d",time()));
        $today = strtotime("2017-10-17 00:00:00");
        $orderLogic = new OrderLogic();
        $orderList = M('order')->where("add_time>=$today")->select();
//        $sort=1;
        foreach ($orderList as $k=>$v){
            /*$orderList[$k]['delivery_sort_c']=$sort;
            if($orderList[$k+1]['driver_id']!=$orderList[$k]['driver_id']) $sort=1;
            else $sort+=1;*/
            $order['user_id']           = $v['user_id'];
            $order['total_amount']      = $v['total_amount'];
            $order['coupon_price']      = $v['coupon_price'];
            $order['order_prom_amount'] = $v['order_prom_amount'];
            $order['total_amount']      = $v['total_amount'];
            $order['shipping_price']    = $v['shipping_price'];
            $order['order_id']          = $v['order_id'];
            $products = $orderLogic->getOrderGoods($v['order_id']);
            $storage_id = M("storage_region")->where("region_id = ".$v['district'])->getField("storage_id");
            $orderProducts="";
            foreach ($products as $key=>$value){
                $orderProducts[$key]['goods_num']       =$value['goods_num'];
                $orderProducts[$key]['goods_price']     =$value['goods_price'];
                $orderProducts[$key]['goods_id']        =$value['goods_id'];
                $orderProducts[$key]['storage_id']      =$storage_id;
            }
            $order['products'] = $orderProducts;
            $orderList[$k]['products'] = $v;
//            $orderList[$k]['action_note'] = $orderLogic->getConfirmNote($v['order_id']);
            $orderJson = json_encode($order);
            $url = "api/insertOrder";
            $this->postData($orderJson,$url,"order",__FUNCTION__);
        }

    }

    /**
     * 确认订单时插入订单
     * @param $orderId 订单ID
     *
     */
    public function insertEditOrder($orderId,$type){
        $orderLogic = new OrderLogic();
        $orderSelect = M('order')->where("order_id>=$orderId")->find();

        $order['user_id']           = $orderSelect['user_id'];
        $order['total_amount']      = $orderSelect['total_amount'];
        $order['coupon_price']      = $orderSelect['coupon_price'];
        $order['order_prom_amount'] = $orderSelect['order_prom_amount'];
        $order['total_amount']      = $orderSelect['total_amount'];
        $order['shipping_price']    = $orderSelect['shipping_price'];
        $order['order_id']          = $orderSelect['order_id'];
        $order['add_time']          = $orderSelect['add_time'];
        $order['consignee']         = $orderSelect['consignee'];
        $order['mobile']            = $orderSelect['mobile'];
        $order['address']           = $orderSelect['address'];
        $order['city']              = M('region')->where("`id`=".$orderSelect['city'])->getField('name');
        $order['district']          = M('region')->where("`id`=".$orderSelect['district'])->getField('name');
        $order['twon']              = M('region')->where("`id`=".$orderSelect['twon'])->getField('name');
        $order['order_sn']          = $orderSelect['order_sn'];
        $products = $orderLogic->getOrderGoods($order['order_id']);
        $storage_id = M("storage_region")->where("region_id = ".$orderSelect['district'])->getField("storage_id");
        $sql = M("storage_region")->getLastSql();
        $orderProducts="";
        foreach ($products as $key=>$value){
            $orderProducts[$key]['goods_num']       =$value['goods_num'];
            $orderProducts[$key]['goods_price']     =$value['goods_price'];
            $orderProducts[$key]['goods_id']        =$value['goods_id'];
            $orderProducts[$key]['storage_id']      =$storage_id;
        }
        $order['products'] = $orderProducts;
        $orderJson['data'] = json_encode($order);
        $url =  $type==1 ? "api/insertOrder" : "api/updateOrder";
        $this->postData($orderJson,$url,"order",__FUNCTION__);
    }

    public function deleteOrder($orderId){
        $data['order_id'] = $orderId;
        $url =  "api/deleteOrder";
        $this->postData($data,$url,"order",__FUNCTION__);
    }

    public function editDriver($orderId,$driverId,$driverName){
        $data['order_id'] = $orderId;
        $data['driver_id'] = $driverId==""? 0 : $driverId;
        $data['driver_name'] = $driverId==""? '自提' : $driverName;
        $url =  "api/editDriver";
        $this->postData($data,$url,"order",__FUNCTION__);
    }
    //订单API结束

    //仓库接口开始
    public function getAllStorage(){
        $storageList = file_get_contents(self ::SERVER_IP."/index.php/api/getAllStorage");
        return $storageList;
    }
    //仓库接口结束
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
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $post_data);

        $output = curl_exec($ch);
//        $output = "ok";
        curl_close($ch);
        if($output!="" && $output!=0){
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