<?php
/**
 * tpshop
 * ============================================================================
 * 版权所有 2015-2027 深圳搜豹网络科技有限公司，并保留所有权利。
 * 网站地址: http://www.tp-shop.cn
 * ----------------------------------------------------------------------------
 * 这不是一个自由软件！您只能在不用于商业目的的前提下对程序代码进行修改和使用 .
 * 不允许对程序代码以任何形式任何目的的再发布。
 * ============================================================================
 * Author: 当燃
 * Date: 2016-03-19
 */

namespace Home\Logic;

use Think\Model\RelationModel;
/**
 *
 * Class orderLogic
 * @package Home\Logic
 */
class DistributLogic extends RelationModel
{
    function rebate_log($order){
        $user_id=$order["user_id"];
        $user = M('users')->where("user_id = $user_id")->find();
        if($user["first_leader"]!=0){
            $data["order_id"]=$order["order_id"];
            $data["goods_price"]=$order["goods_price"];
            $data["order_amount"]=$order["order_amount"];
            $data["order_rate"]=tpCache('distribut.first_rate')>0 ? tpCache('distribut.first_rate'):tpCache('distribut.order_rate');
            $data["leader_id"]=$user["first_leader"];
            $data["leader_level"]=1;
            $data["user_id"]=$user_id;
            M('distribut')->add($data);
        }
        if($user["second_leader"]!=0){
            $data["order_id"]=$order["order_id"];
            $data["goods_price"]=$order["goods_price"];
            $data["order_amount"]=$order["order_amount"];
            $data["order_rate"]=tpCache('distribut.second_rate')>0 ? tpCache('distribut.second_rate'):tpCache('distribut.order_rate');
            $data["leader_id"]=$user["second_leader"];
            $data["leader_level"]=2;
            $data["user_id"]=$user_id;
            M('distribut')->add($data);
        }
        if($user["third_leader"]!=0){
            $data["order_id"]=$order["order_id"];
            $data["goods_price"]=$order["goods_price"];
            $data["order_amount"]=$order["order_amount"];
            $data["order_rate"]=tpCache('distribut.third_rate')>0 ? tpCache('distribut.third_rate'):tpCache('distribut.order_rate');
            $data["leader_id"]=$user["third_leader"];
            $data["leader_level"]=3;
            $data["user_id"]=$user_id;
            M('distribut')->add($data);
        }
    }
}