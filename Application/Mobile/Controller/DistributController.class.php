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
 * 2015-11-21
 */
namespace Mobile\Controller;

use Think\Page;
use Think\Verify;

class DistributController extends MobileBaseController
{

    public $user_id = 0;
    public $user = array();

    /*
    * 初始化操作
    */
    public function _initialize()
    {
        parent::_initialize();
        if (session('?user')) {
            $user = session('user');
            $user = M('users')->where("user_id = {$user['user_id']}")->find();
            session('user', $user);  //覆盖session 中的 user
            $this->user = $user;
            $this->user_id = $user['user_id'];

            $order_count = M('order')->where("user_id = {$this->user_id}")->count(); // 我的订单数
            $goods_collect_count = M('goods_collect')->where("user_id = {$this->user_id}")->count(); // 我的商品收藏
            $comment_count = M('comment')->where("user_id = {$this->user_id}")->count();//  我的评论数
            $coupon_count = M('coupon_list')->where("uid = {$this->user_id}")->count(); // 我的优惠券数量
            $level_name = M('user_level')->where("level_id = '{$this->user['level']}'")->getField('level_name'); // 等级名称


            $this->assign('level_name', $level_name);
            $this->assign('order_count', $order_count);
            $this->assign('goods_collect_count', $goods_collect_count);
            $this->assign('comment_count', $comment_count);
            $this->assign('coupon_count', $coupon_count);
            $this->assign('user', $user); //存储用户信息
        }
        $nologin = array(
            'login', 'pop_login', 'do_login', 'logout', 'verify', 'set_pwd', 'finished',
            'verifyHandle', 'reg', 'send_sms_reg_code', 'find_pwd', 'check_validate_code',
            'forget_pwd', 'check_captcha', 'check_username', 'send_validate_code', 'express',
        );
        if (!$this->user_id && !in_array(ACTION_NAME, $nologin)) {
            header("location:" . U('Mobile/User/login'));
            exit;
        }

        $order_status_coment = array(
            'WAITPAY' => '待付款 ', //订单查询状态 待支付
            'WAITSEND' => '待发货', //订单查询状态 待发货
            'WAITRECEIVE' => '待收货', //订单查询状态 待收货
            'WAITCCOMMENT' => '待评价', //订单查询状态 待评价
        );
        $this->assign('order_status_coment', $order_status_coment);
    }

    /*
     * 分销首页
     */
    public function index()
    {
        $first_lower_count=M('users')->where("first_leader = {$this->user_id}")->count();//一级分销商数量
        $second_lower_count=M('users')->where("second_leader = {$this->user_id}" )->count();//二级分销商数量
        $third_lower_count=M('users')->where("third_leader = {$this->user_id}" )->count();//三级分销商数量
        $total_lower_count=$first_lower_count+$second_lower_count+$third_lower_count;//总分销商数量
        $total_order_count=M('distribut')->where("leader_id = {$this->user_id}")->count();//总分销订单数量
        $total_order_amount=M('distribut')->where("leader_id = {$this->user_id}")->sum('order_amount');//总分销销售金额

        //未付款订单 数量 金额 分成金额
        $no_pay_order_count=M('distribut d')
            ->join('LEFT JOIN tp_order AS o ON d.order_id=o.order_id')
            ->where("d.leader_id={$this->user_id} AND o.pay_status = 0 AND o.order_status = 0 AND o.pay_code !='cod'")
            ->count();
        $no_pay_order_amount=M('distribut d')
            ->join('LEFT JOIN tp_order AS o ON d.order_id=o.order_id')
            ->where("d.leader_id={$this->user_id} AND o.pay_status = 0 AND o.order_status = 0 AND o.pay_code !='cod'")
            ->sum('d.order_amount');
        $no_pay_distribut_amount=M('distribut d')
            ->join('LEFT JOIN tp_order AS o ON d.order_id=o.order_id')
            ->where("d.leader_id={$this->user_id} AND o.pay_status = 0 AND o.order_status = 0 AND o.pay_code !='cod'")
            ->sum('d.order_amount * d.order_rate * 0.01');

        // 已付款订单 数量 金额
        $pay_order_count=M('distribut d')
            ->join('LEFT JOIN tp_order AS o ON d.order_id=o.order_id')
            ->where("d.leader_id={$this->user_id} AND (pay_status=1 OR pay_code='cod')")
            ->count();
        $pay_order_amount=M('distribut d')
            ->join('LEFT JOIN tp_order AS o ON d.order_id=o.order_id')
            ->where("d.leader_id={$this->user_id} AND (pay_status=1 OR pay_code='cod')")
            ->sum('d.order_amount');

        //总分成金额
        $total_distribut_amount=M('distribut')->where("leader_id = {$this->user_id}")->sum('goods_price * order_rate * 0.01');
        //已付款未发货订单分成金额
        $pay_distribut_amount=M('distribut d')
            ->join('LEFT JOIN tp_order AS o ON d.order_id=o.order_id')
            ->where("d.leader_id={$this->user_id} AND (pay_status=1 OR pay_code='cod') AND shipping_status !=1 AND order_status in(0,1) ")
            ->sum('d.order_amount * d.order_rate * 0.01');
        //已收货订单分成金额
        $complete_distribut_amount=M('distribut d')
            ->join('LEFT JOIN tp_order AS o ON d.order_id=o.order_id')
            ->where("d.leader_id={$this->user_id} AND o.order_status in(1,4) ")
            ->sum('d.order_amount * d.order_rate * 0.01');
        //已分成金额
        $distribut_amount=M('distribut d')
            ->join('LEFT JOIN tp_order AS o ON d.order_id=o.order_id')
            ->where("d.leader_id={$this->user_id} AND o.is_distribut=1")
            ->sum('d.order_amount * d.order_rate * 0.01');

        $this->assign('first_lower_count', $first_lower_count);
        $this->assign('second_lower_count', $second_lower_count);
        $this->assign('third_lower_count', $third_lower_count);
        $this->assign('total_lower_count', $total_lower_count);
        $this->assign('total_order_count', $total_order_count);
        $this->assign('total_order_amount', $total_order_amount);
        $this->assign('pay_order_count', $pay_order_count);
        $this->assign('no_pay_order_count', $no_pay_order_count);
        $this->assign('no_pay_order_amount', $no_pay_order_amount);
        $this->assign('pay_order_amount', $pay_order_amount);
        $this->assign('total_distribut_amount', round($total_distribut_amount,2));
        $this->assign('no_pay_distribut_amount', round($no_pay_distribut_amount,2));
        $this->assign('pay_distribut_amount', round($pay_distribut_amount,2));
        $this->assign('complete_distribut_amount', round($complete_distribut_amount,2));
        $this->assign('distribut_amount',round($distribut_amount,2));
        //$no_pay_distribut_amount

        $this->display();
    }

    public  function  lower_list(){
        $level = I("get.level");
        $fild="";
        switch ($level){
            case 1:
                $fild="first_leader";
                break;
            case 2:
                $fild="second_leader";
                break;
            case 3:
                $fild="third_leader";
                break;
        }

        $lower_count=M('users')->where("$fild = {$this->user_id}")->count();

        $Page = new Page($lower_count, 10);
        $show = $Page->show();
        $user_list = M('users')->where("$fild = {$this->user_id}")->limit($Page->firstRow . ',' . $Page->listRows)->select();
        $this->assign("user_count",$lower_count);
        $this->assign("list",$user_list);
        $this->assign("level",$level);
        $this->assign('page', $show);
        if ($_GET['is_ajax']) {
            $this->display('ajax_lower_list');
            exit;
        }
        $this->display();
    }

    public function order_list(){
        $status = I("get.status");
        $where="";
        if($status=='0'){
            $where="d.leader_id={$this->user_id} AND o.pay_status = 0 AND o.order_status = 0 AND o.pay_code !='cod'";
        }elseif ($status=='1'){
            $where="d.leader_id={$this->user_id} AND (o.pay_status=1 OR o.pay_code='cod') AND o.shipping_status !=1 AND o.order_status in(0,1)";
        }elseif ($status=='2'){
            $where="d.leader_id={$this->user_id} AND o.order_status in(1,4)";
        }elseif ($status=='3'){
            $where="d.leader_id={$this->user_id} AND o.is_distribut=1";
        }
        $low_order_count=M('distribut d')
            ->join('LEFT JOIN tp_order AS o ON d.order_id=o.order_id')
            ->where($where)
            ->count();
        $Page = new Page($low_order_count, 10);
        $show = $Page->show();
        $order_list=M('distribut d')
            ->field('d.goods_price,d.order_amount,d.order_rate,d.leader_level,o.order_sn,u.head_pic')
            ->join('LEFT JOIN tp_order AS o ON d.order_id=o.order_id')
            ->join('LEFT JOIN tp_users AS u ON o.user_id=u.user_id')
            ->where($where)
            ->limit($Page->firstRow . ',' . $Page->listRows)
            ->select();
        $this->assign("list",$order_list);
        $this->assign('page', $show);
        if ($_GET['is_ajax']) {
            $this->display('ajax_order_list');
            exit;
        }
        $this->display();
    }

    public function qr_code(){
        $this->assign("user_id",$this->user_id);
        $this->display();
    }

   public function auto_confirm(){
       $switch = tpCache('distribut.date');
   }
}