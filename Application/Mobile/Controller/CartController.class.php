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
namespace Mobile\Controller;

use Think\Verify;
use Think\Model\RelationModel;
class CartController extends MobileBaseController {
    
    public $cartLogic; // 购物车逻辑操作类    
    public $user_id = 0;
    public $user = array();        
    /**
     * 析构流函数
     */
    public function  __construct() {   
        parent::__construct();                
        $this->cartLogic = new \Home\Logic\CartLogic();                 
        if(session('?user'))
        {
        	$user = session('user');
                $user = M('users')->where("user_id = {$user['user_id']}")->find();
                session('user',$user);  //覆盖session 中的 user               			                
        	$this->user = $user;
        	$this->user_id = $user['user_id'];
        	$this->assign('user',$user); //存储用户信息
                // 给用户计算会员价 登录前后不一样
                if($user){
                    $user[discount] = (empty($user[discount])) ? 1 : $user[discount];
                    M('Cart')->execute("update `__PREFIX__cart` set member_goods_price = goods_price * {$user[discount]} where (user_id ={$user[user_id]} or session_id = '{$this->session_id}') and prom_type = 0");
                }                 
         }            
    }
    
    public function cart(){
        $this->display('cart');
    }
    /**
     * 将商品加入购物车
     */
    function addCart()
    {
        $goods_id = I("goods_id"); // 商品id
        $goods_num = I("goods_num");// 商品数量
        $goods_spec = I("goods_spec"); // 商品规格                
        $goods_spec = json_decode($goods_spec,true); //app 端 json 形式传输过来
        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        $user_id = I("user_id",0); // 用户id        
        $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$unique_id,$user_id); // 将商品加入购物车
        exit(json_encode($result)); 
    }
    /**
     * ajax 将商品加入购物车
     */
    function ajaxAddCart()
    {
        $goods_id = I("goods_id"); // 商品id
        $goods_num = I("goods_num");// 商品数量
        $goods_spec = I("goods_spec"); // 商品规格
        $result = $this->cartLogic->addCart($goods_id, $goods_num, $goods_spec,$this->session_id,$this->user_id); // 将商品加入购物车
        exit(json_encode($result));
    }

    /*
     * 请求获取购物车列表
     */
    public function cartList()
    {
        $cart_form_data = $_POST["cart_form_data"]; // goods_num 购物车商品数量
        $cart_form_data = json_decode($cart_form_data,true); //app 端 json 形式传输过来

        $unique_id = I("unique_id"); // 唯一id  类似于 pc 端的session id
        $user_id = I("user_id"); // 用户id
        $where = " session_id = '$unique_id' "; // 默认按照 $unique_id 查询
        $user_id && $where = " user_id = ".$user_id; // 如果这个用户已经等了则按照用户id查询
        $cartList = M('Cart')->where($where)->getField("id,goods_num,selected");

        if($cart_form_data)
        {
            // 修改购物车数量 和勾选状态
            foreach($cart_form_data as $key => $val)
            {
                $data['goods_num'] = $val['goodsNum'];
                $data['selected'] = $val['selected'];
                $cartID = $val['cartID'];
                if(($cartList[$cartID]['goods_num'] != $data['goods_num']) || ($cartList[$cartID]['selected'] != $data['selected']))
                    M('Cart')->where("id = $cartID")->save($data);
            }
            //$this->assign('select_all', $_POST['select_all']); // 全选框
        }

        $result = $this->cartLogic->cartList($this->user, $unique_id,0);
        exit(json_encode($result));
    }

    /**
     * 购物车第二步确定页面
     */
    public function cart2()
    {

        if($this->user_id == 0)
            $this->error('请先登陆',U('Mobile/User/login'));
        $address_id = I('address_id');
        if($address_id)
            $address = M('user_address')->where("address_id = $address_id")->find();
        else
            $address = M('user_address')->where("user_id = $this->user_id and is_default=1")->find();
        
        if(empty($address)){
        	header("Location: ".U('Mobile/User/add_address',array('source'=>'cart2')));
        }else{
            $region_list = get_region_list();
            $this->assign('region_list', $region_list);
        	$this->assign('address',$address);
        }
        if($this->cartLogic->cart_count($this->user_id,1) == 0 )
            $this->error ('你的购物车没有选中商品','Cart/cart');

        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1); // 获取购物车商品
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->select();// 物流公司

        $Model = new \Think\Model(); // 找出这个用户的优惠券 没过期的  并且 订单金额达到 condition 优惠券指定标准的
        $sql = "select c1.name,c1.money,c1.condition, c2.* from __PREFIX__coupon as c1 inner join __PREFIX__coupon_list as c2  on c2.cid = c1.id and c1.type in(0,1,2,3) and order_id = 0  where c2.uid = {$this->user_id} and ".time()." < c1.use_end_time and c1.condition <= {$result['total_price']['total_fee']}";
        $couponList = $Model->query($sql);
        /**
         * 这里修改优惠券默认选中逻辑
         */
        $this->assign('couponList', $couponList); // 优惠券列表
        $this->assign('shippingList', $shippingList); // 物流公司
        $this->assign('cartList', $result['cartList']); // 购物车的商品
        $this->assign('total_price', $result['total_price']); // 总计
        $this->display();
    }

    /**
     * ajax 获取订单商品价格 或者提交 订单
     */
    public function cart3(){
                                
        if($this->user_id == 0)
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态

        $address_id = I("address_id"); //  收货地址id
        $shipping_code =  I("shipping_code"); //  物流编号
        $invoice_title = I('invoice_title'); // 发票
        $couponTypeSelect =  I("couponTypeSelect"); //  优惠券类型  1 下拉框选择优惠券 2 输入框输入优惠券代码
        $coupon_id =  I("coupon_id"); //  优惠券id
        $couponCode =  I("couponCode"); //  优惠券代码
        $pay_points =  I("pay_points",0); //  使用积分
        $user_money =  I("user_money",0); //  使用余额
        $user_note = I("userNote");
        $get_goods_time = strtotime(I("get_goods_time"));
        $user_money = $user_money ? $user_money : 0;

        if($this->cartLogic->cart_count($this->user_id,1) == 0 ) exit(json_encode(array('status'=>-2,'msg'=>'你的购物车没有选中商品','result'=>null))); // 返回结果状态
        if(!$address_id) exit(json_encode(array('status'=>-3,'msg'=>'请先填写收货人信息','result'=>null))); // 返回结果状态
        if(!$shipping_code) exit(json_encode(array('status'=>-4,'msg'=>'请选择物流信息','result'=>null))); // 返回结果状态
        if(strlen($user_note)>20) exif(json_encode(array('status'=>-5,'msg'=>'备注信息在20字以内','result'=>null)));

        $address = M('UserAddress')->where("address_id = $address_id")->find();
        $order_goods = M('cart')->where("user_id = {$this->user_id} and selected = 1")->select();
        $result = calculate_price($this->user_id,$order_goods,$shipping_code,0,$address[province],$address[city],$address[district],$pay_points,$user_money,$coupon_id,$couponCode);

        if($result['status'] < 0)
            exit(json_encode($result));
        // 订单满额优惠活动
        $order_prom = get_order_promotion($result['result']['order_amount']);
        $result['result']['order_amount'] = $order_prom['order_amount'] ;
        $result['result']['order_prom_id'] = $order_prom['order_prom_id'] ;
        $result['result']['order_prom_amount'] = $order_prom['order_prom_amount'] ;

        $car_price = array(
            'postFee'      => $result['result']['shipping_price'], // 物流费
            'couponFee'    => $result['result']['coupon_price'], // 优惠券
            'balance'      => $result['result']['user_money'], // 使用用户余额
            'pointsFee'    => $result['result']['integral_money'], // 积分支付
            'payables'     => $result['result']['order_amount'], // 应付金额
            'goodsFee'     => $result['result']['goods_price'],// 商品价格
            'order_prom_id' => $result['result']['order_prom_id'], // 订单优惠活动id
            'order_prom_amount' => $result['result']['order_prom_amount'], // 订单优惠活动优惠了多少钱
        );

        // 提交订单
        if($_REQUEST['act'] == 'submit_order')
        {
            if(empty($coupon_id) && !empty($couponCode))
                $coupon_id = M('CouponList')->where("`code`='$couponCode'")->getField('id');
            $result = $this->cartLogic->addOrder($this->user_id,$address_id,$shipping_code,$invoice_title,$coupon_id,$car_price,$get_goods_time,$user_note); // 添加订单
            exit(json_encode($result));
        }
        $return_arr = array('status'=>1,'msg'=>'计算成功','result'=>$car_price); // 返回结果状态
        exit(json_encode($return_arr));
    }	
    /*
     * 订单支付页面
     */
    public function cart4(){

        $order_id = I('order_id');
        $order = M('Order')->where("order_id = $order_id")->find();
        // 如果已经支付过的订单直接到订单详情页面. 不再进入支付页面
        if($order['pay_status'] == 1){
            $order_detail_url = U("Mobile/User/order_detail",array('id'=>$order_id));
            header("Location: $order_detail_url");
        }

        $paymentList = M('Plugin')->where("`type`='payment' and status = 1 and  scene in(0,1)")->select();        
        //微信浏览器
        if(strstr($_SERVER['HTTP_USER_AGENT'],'MicroMessenger')){
            $paymentList = M('Plugin')->where("`type`='payment' and status = 1 and code in('weixin','cod')")->select();            
        }        
        $paymentList = convert_arr_key($paymentList, 'code');

        foreach($paymentList as $key => $val)
        {
            $val['config_value'] = unserialize($val['config_value']);
            if($val['config_value']['is_bank'] == 2)
            {
                $bankCodeList[$val['code']] = unserialize($val['bank_code']);
            }
        }

        $bank_img = include 'Application/Home/Conf/bank.php'; // 银行对应图片
        $payment = M('Plugin')->where("`type`='payment' and status = 1")->select();
        $this->assign('paymentList',$paymentList);
        $this->assign('bank_img',$bank_img);
        $this->assign('order',$order);
        $this->assign('bankCodeList',$bankCodeList);
        $this->assign('pay_date',date('Y-m-d', strtotime("+1 day")));
        $this->display();
    }


    /*
    * ajax 请求获取购物车列表
    */
    public function ajaxCartList()
    {
        $post_goods_num = I("goods_num"); // goods_num 购物车商品数量
        $post_cart_select = I("cart_select"); // 购物车选中状态
        $where = " session_id = '$this->session_id' "; // 默认按照 session_id 查询
        $this->user_id && $where = " user_id = ".$this->user_id; // 如果这个用户已经等了则按照用户id查询

        $cartList = M('Cart')->where($where)->getField("id,goods_num,selected,prom_type,prom_id"); 

        if($post_goods_num)
        {
            // 修改购物车数量 和勾选状态
            foreach($post_goods_num as $key => $val)
            {                
                $data['goods_num'] = $val < 1 ? 1 : $val;
                if($cartList[$key]['prom_type'] == 1) //限时抢购 不能超过购买数量
                {
                    $flash_sale = M('flash_sale')->where("id = {$cartList[$key]['prom_id']}")->find();
                    $data['goods_num'] = $data['goods_num'] > $flash_sale['buy_limit'] ? $flash_sale['buy_limit'] : $data['goods_num'];
                }
                
                $data['selected'] = $post_cart_select[$key] ? 1 : 0 ;
                if(($cartList[$key]['goods_num'] != $data['goods_num']) || ($cartList[$key]['selected'] != $data['selected']))
                    M('Cart')->where("id = $key")->save($data);
            }
            $this->assign('select_all', $_POST['select_all']); // 全选框
        }

        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1);        
        if(empty($result['total_price']))
            $result['total_price'] = Array( 'total_fee' =>0, 'cut_fee' =>0, 'num' => 0, 'atotal_fee' =>0, 'acut_fee' =>0, 'anum' => 0);
        
        $this->assign('cartList', $result['cartList']); // 购物车的商品                
        $this->assign('total_price', $result['total_price']); // 总计       
        $this->display('ajax_cart_list');
    }

    /*
 * ajax 获取用户收货地址 用于购物车确认订单页面
 */
    public function ajaxAddress(){

        $regionList = M('Region')->getField('id,name');

        $address_list = M('UserAddress')->where("user_id = {$this->user_id}")->select();
        $c = M('UserAddress')->where("user_id = {$this->user_id} and is_default = 1")->count(); // 看看有没默认收货地址
        if((count($address_list) > 0) && ($c == 0)) // 如果没有设置默认收货地址, 则第一条设置为默认收货地址
            $address_list[0]['is_default'] = 1;

        $this->assign('regionList', $regionList);
        $this->assign('address_list', $address_list);
        $this->display('ajax_address');
    }

    /**
     * ajax 删除购物车的商品
     */
    public function ajaxDelCart()
    {
        $ids = I("ids"); // 商品 ids
        $result = M("Cart")->where(" id in ($ids)")->delete(); // 删除id为5的用户数据
        $return_arr = array('status'=>1,'msg'=>'删除成功','result'=>''); // 返回结果状态
        exit(json_encode($return_arr));
    }

    public function cart5(){
        if($this->user_id == 0)
            $this->error('请先登陆',U('Mobile/User/login'));

        $this->verifyHandle('message');
        $couponNo = I("coupon_no");
        $coupon = M("goods_coupon_info")->where("coupon_no='$couponNo' AND if_use=0")->find();
        if(!$coupon){
            $this->error('兑换券编码错误', U('Mobile/User/get_coupon_goods'));
            exit;
        }
        $this->cart8($couponNo);
    }

    public function cart8($couponNo){
        if(!$couponNo) $couponNo=I("couponNo");
        $coupon = M("goods_coupon_info")->where("coupon_no='$couponNo' AND if_use=0")->find();
        $address_id = I('address_id');
        if($address_id)
            $address = M('user_address')->where("address_id = $address_id")->find();
        else
            $address = M('user_address')->where("user_id = $this->user_id and is_default=1")->find();

        if(empty($address)){
            header("Location: ".U('Mobile/User/add_address',array('source'=>'cart8','couponNo'=>$couponNo)));
        }else{
            $region_list = get_region_list();
            $this->assign('region_list', $region_list);
            $this->assign('address',$address);
        }

//        $result = $this->cartLogic->cartList($this->user, $this->session_id,1,1); // 获取购物车商品
        $cartList = M('goodscoupon_goods gg')
            ->join('tp_goods g on gg.goods_id=g.goods_id')
            ->field("g.*,gg.goods_num,gg.key_name")
            ->where("goods_coupon_id=".$coupon["goods_coupon_id"])
            ->select();
        $anum = $total_price =  $cut_fee = 0;
        foreach ($cartList as $k=>$val){
            $cartList[$k]['goods_fee'] = $val['goods_num'] * $val['shop_price'];
            //$cartList[$k]['store_count']  = getGoodNum($val['goods_id'],$val['spec_key']); // 最多可购买的库存数量
            $anum += $val['goods_num'];

            $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price'];
            $total_price += $val['goods_num'] * $val['shop_price'];
        }


        $total_price = array('total_fee' =>$total_price , 'cut_fee' => $cut_fee,'num'=> $anum,); // 总计
        $result['total_price']=$total_price;
        $result['cartList']=$cartList;
        $shippingList = M('Plugin')->where("`type` = 'shipping' and status = 1")->select();// 物流公司

        $this->assign('shippingList', $shippingList); // 物流公司
        $this->assign('cartList', $result['cartList']); // 购物车的商品
        $this->assign('total_price', $result['total_price']); // 总计
        $this->assign('coupon', $coupon); // 总计
        $this->assign('couponNo', $couponNo);
        $this->display("cart5");
    }

    public function cart6(){
        if($this->user_id == 0)
            exit(json_encode(array('status'=>-100,'msg'=>"登录超时请重新登录!",'result'=>null))); // 返回结果状态

        $address_id = I("address_id"); //  收货地址id
        $shipping_code =  I("shipping_code"); //  物流编号
        $invoice_title = I('invoice_title'); // 发票
        $couponTypeSelect =  I("couponTypeSelect"); //  优惠券类型  1 下拉框选择优惠券 2 输入框输入优惠券代码
        $coupon_id =  I("coupon_id"); //  优惠券id
        $couponCode =  I("couponCode"); //  优惠券代码
        $pay_points =  I("pay_points",0); //  使用积分
        $user_money =  I("user_money",0); //  使用余额
        $user_note = I("userNote");
        $coupon_no = I("coupon_no");
        $goods_coupon_id = I("goods_coupon_id");
        $goods_coupon_info_id = I("goods_coupon_info_id");
        $get_goods_time = strtotime(I("get_goods_time"));
        $user_money = $user_money ? $user_money : 0;
        $user_id = $this->user_id;

        if(!$address_id) exit(json_encode(array('status'=>-3,'msg'=>'请先填写收货人信息','result'=>null))); // 返回结果状态
        if(!$shipping_code) exit(json_encode(array('status'=>-4,'msg'=>'请选择物流信息','result'=>null))); // 返回结果状态
        if(strlen($user_note)>20) exif(json_encode(array('status'=>-5,'msg'=>'备注信息在20字以内','result'=>null)));

        $cartList = M('goodscoupon_goods gg')
            ->join('tp_goods g on gg.goods_id=g.goods_id')
            ->field("g.*,gg.goods_num,gg.key_name,gg.spec_key")
            ->where("goods_coupon_id=".$goods_coupon_id)
            ->select();
        $anum = $total_price =  $cut_fee = 0;
        foreach ($cartList as $k=>$val){
            $cartList[$k]['goods_fee'] = $val['goods_num'] * $val['shop_price'];
            //$cartList[$k]['store_count']  = getGoodNum($val['goods_id'],$val['spec_key']); // 最多可购买的库存数量
            $anum += $val['goods_num'];

            $cut_fee += $val['goods_num'] * $val['market_price'] - $val['goods_num'] * $val['member_goods_price'];
            $total_price += $val['goods_num'] * $val['shop_price'];
        }

        $address = M('UserAddress')->where("address_id = $address_id")->find();
        $shipping = M('Plugin')->where("code = '$shipping_code'")->find();
        $data = array(
            'order_sn'         => date('YmdHis').rand(1000,9999), // 订单编号
            'user_id'          =>$user_id, // 用户id
            'consignee'        =>$address['consignee'], // 收货人
            'province'         =>$address['province'],//'省份id',
            'city'             =>$address['city'],//'城市id',
            'district'         =>$address['district'],//'县',
            'twon'             =>$address['twon'],// '街道',
            'address'          =>$address['address'],//'详细地址',
            'mobile'           =>$address['mobile'],//'手机',
            'zipcode'          =>$address['zipcode'],//'邮编',
            'email'            =>$address['email'],//'邮箱',
            'shipping_code'    =>$shipping_code,//'物流编号',
            'shipping_name'    =>$shipping['name'], //'物流名称',
            'invoice_title'    =>$invoice_title, //'发票抬头',
            'goods_price'      =>$total_price,//'商品价格',
            'shipping_price'   =>0,//'物流价格',
            'user_money'       =>0,//'使用余额',
            'coupon_price'     =>0,//'使用优惠券',
            'integral'         =>0, //'使用积分',
            'integral_money'   =>0,//'使用积分抵多少钱',
            'total_amount'     =>$total_price,// 订单总额
            'order_amount'     =>0,//'应付款金额',
            'add_time'         =>time(), // 下单时间
            'order_prom_id'    =>0,//'订单优惠活动id',
            'order_prom_amount'=>0,//'订单优惠活动优惠了多少钱',
            'get_goods_time'   =>$get_goods_time,
            'user_note'        =>$user_note,
            'pay_status'       =>1,
            'pay_code'         =>'duihuanquan',
            'pay_name'         =>'兑换券'
        );

        $order_id = M("Order")->data($data)->add();

        if(!$order_id)
            return array('status'=>-8,'msg'=>'添加订单失败','result'=>NULL);

        // 记录订单操作日志
        logOrder($order_id,'您提交了兑换订单，请等待系统确认','提交订单',$user_id);

        $order = M('Order')->where("order_id = $order_id")->find();

        // 1插入order_goods 表
//        $cartList = M('Cart')->where("user_id = $user_id and selected = 1")->select();
        foreach($cartList as $key => $val)
        {
            $goods = M('goods')->where("goods_id = {$val['goods_id']} ")->find();
            $data2['order_id']           = $order_id; // 订单id
            $data2['goods_id']           = $val['goods_id']; // 商品id
            $data2['goods_name']         = $val['goods_name']; // 商品名称
            $data2['goods_sn']           = $val['goods_sn']; // 商品货号
            $data2['goods_num']          = $val['goods_num']; // 购买数量
            $data2['market_price']       = $val['market_price']; // 市场价
            $data2['goods_price']        = $val['market_price']; // 商品价
            $data2['spec_key']           = $val['spec_key']; // 商品规格
            $data2['spec_key_name']      = $val['key_name']; // 商品规格名称
            $data2['sku']           	 = $val['sku']; // 商品sku
            $data2['member_goods_price'] = $val['shop_price']; // 会员折扣价
            $data2['cost_price']         = $goods['cost_price']; // 成本价
            $data2['give_integral']      = $goods['give_integral']; // 购买商品赠送积分
            $data2['prom_type']          = 4; // 0 普通订单,1 限时抢购, 2 团购 , 3 促销优惠 , 4 兑换券
            $data2['prom_id']            = $val['prom_id']; // 活动id
            $order_goods_id              = M("OrderGoods")->data($data2)->add();
            // 扣除商品库存  扣除库存移到 付完款后扣除
            //M('Goods')->where("goods_id = ".$val['goods_id'])->setDec('store_count',$val['goods_num']); // 商品减少库存
        }

        //更新兑换券状态

        $data3['if_use'] = 1;
        $data3['user_id'] = $user_id;
        $data3['use_date'] = time();
        $data3['order_id'] = $order_id;

        M("goods_coupon_info")->where("goods_coupon_info_id=$goods_coupon_info_id")->save($data3);

        //更新兑换券使用数量
        $data4['use_num'] = M("goods_coupon_info")->where("goods_coupon_id=$goods_coupon_id AND if_use=1")->count();
        M("goods_coupon")->where("goods_coupon_id=$goods_coupon_id")->save($data4);

        // 如果有微信公众号 则推送一条消息到微信
        $user = M('users')->where("user_id = $user_id")->find();
        if($user['oauth']== 'weixin')
        {
            $wx_user = M('wx_user')->find();
            $jssdk = new \Mobile\Logic\Jssdk($wx_user['appid'],$wx_user['appsecret']);
            $wx_content = "感谢您订购本公司产品，订单号：:{$order['order_sn']}，您可要进入个人中心查询订单详情";
            $jssdk->push_msg($user['openid'],$wx_content);
        }

        $return_arr = array('status'=>1,'msg'=>'计算成功','result'=>$order['order_sn']); // 返回结果状态
        exit(json_encode($return_arr));
    }

    public function cart7(){
        $order_sn = I('order_sn');
        $order = M('order')->where("order_sn = '$order_sn'")->find();
        $this->assign('order', $order);
        $this->display('success');
    }

    /**
     * 验证码验证
     * $id 验证码标示
     */
    private function verifyHandle($id)
    {
        $verify = new Verify();
        if (!$verify->check(I('post.verify_code'), $id ? $id : 'user_login')) {
            $this->error("验证码错误");
        }
    }
}
