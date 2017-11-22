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
 * Date: 2015-12-11
 */
namespace Admin\Controller;
use Think\AjaxPage;

class CouponController extends BaseController {
    /**----------------------------------------------*/
     /*                优惠券控制器                  */
    /**----------------------------------------------*/
    /*
     * 优惠券类型列表
     */
    public function index(){
        //获取优惠券列表
        
    	$count =  M('coupon')->count();
    	$Page = new \Think\Page($count,10);        
        $show = $Page->show();
        $lists = M('coupon')->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出   
        $this->assign('coupons',C('COUPON_TYPE'));
        $this->display();
    }

    /*
     * 添加编辑一个优惠券类型
     */
    public function coupon_info(){
        if(IS_POST){
        	$data = I('post.');
            $data['send_start_time'] = strtotime($data['send_start_time']);
            $data['send_end_time'] = strtotime($data['send_end_time']);
            $data['use_end_time'] = strtotime($data['use_end_time']);
            $data['use_start_time'] = strtotime($data['use_start_time']);
            if($data['send_start_time'] > $data['send_end_time']){
                $this->error('发放日期填写有误');
            }
            if(empty($data['id'])){
            	$data['add_time'] = time();
            	$row = M('coupon')->add($data);
            }else{
            	$row =  M('coupon')->where(array('id'=>$data['id']))->save($data);
            }
            if(!$row)
                $this->error('编辑代金券失败');
            $this->success('编辑代金券成功',U('Admin/Coupon/index'));
            exit;
        }
        $cid = I('get.id');
        if($cid){
        	$coupon = M('coupon')->where(array('id'=>$cid))->find();
        	$this->assign('coupon',$coupon);
        }else{
        	$def['send_start_time'] = strtotime("+1 day");
        	$def['send_end_time'] = strtotime("+1 month");
        	$def['use_start_time'] = strtotime("+1 day");
        	$def['use_end_time'] = strtotime("+2 month");
        	$this->assign('coupon',$def);
        }     
        $this->display();
    }

    /*
    * 优惠券发放
    */
    public function make_coupon(){
        //获取优惠券ID
        $cid = I('get.id');
        $type = I('get.type');
        //查询是否存在优惠券
        $data = M('coupon')->where(array('id'=>$cid))->find();
        $remain = $data['createnum'] - $data['send_num'];//剩余派发量
    	if($remain<=0) $this->error($data['name'].'已经发放完了');
        if(!$data) $this->error("优惠券类型不存在");
        if($type != 4) $this->error("该优惠券类型不支持发放");
        if(IS_POST){
            $num  = I('post.num');
            if($num>$remain) $this->error($data['name'].'发放量不够了');
            if(!$num > 0) $this->error("发放数量不能小于0");
            $add['cid'] = $cid;
            $add['type'] = $type;
            $add['send_time'] = time();
            for($i=0;$i<$num; $i++){
                do{
                    $code = get_rand_str(8,0,1);//获取随机8位字符串
                    $check_exist = M('coupon_list')->where(array('code'=>$code))->find();
                }while($check_exist);
                $add['code'] = $code;
                M('coupon_list')->add($add);
            }
            M('coupon')->where("id=$cid")->setInc('send_num',$num);
            adminLog("发放".$num.'张'.$data['name']);
            $this->success("发放成功",U('Admin/Coupon/index'));
            exit;
        }
        $this->assign('coupon',$data);
        $this->display();
    }
    
    public function ajax_get_user(){
    	//搜索条件
    	$condition = array();
    	I('mobile') ? $condition['mobile'] = I('mobile') : false;
    	I('email') ? $condition['email'] = I('email') : false;
    	$nickname = I('nickname');
    	if(!empty($nickname)){
    		$condition['nickname'] = array('like',"%$nickname%");
    	}
    	$model = M('users');
    	$count = $model->where($condition)->count();
    	$Page  = new AjaxPage($count,10);
    	foreach($condition as $key=>$val) {
    		$Page->parameter[$key] = urlencode($val);
    	}
    	$show = $Page->show();
    	$userList = $model->where($condition)->order("user_id desc")->limit($Page->firstRow.','.$Page->listRows)->select();
        
        $user_level = M('user_level')->getField('level_id,level_name',true);       
        $this->assign('user_level',$user_level);
    	$this->assign('userList',$userList);
    	$this->assign('page',$show);
    	$this->display();
    }
    
    public function send_coupon(){
    	$cid = I('cid');    	
    	if(IS_POST){
    		$level_id = I('level_id');
    		$user_id = I('user_id');
    		$insert = '';
    		$coupon = M('coupon')->where("id=$cid")->find();
    		if($coupon['createnum']>0){
    			$remain = $coupon['createnum'] - $coupon['send_num'];//剩余派发量
    			if($remain<=0) $this->error($coupon['name'].'已经发放完了');
    		}
    		
    		if(empty($user_id) && $level_id>=0){
    			if($level_id==0){
    				$user = M('users')->where("is_lock=0")->select();
    			}else{
    				$user = M('users')->where("is_lock=0 and level_id=$level_id")->select();
    			}
    			if($user){
    				$able = count($user);//本次发送量
    				if($coupon['createnum']>0 && $remain<$able){
    					$this->error($coupon['name'].'派发量只剩'.$remain.'张');
    				}
    				foreach ($user as $k=>$val){
    					$user_id = $val['user_id'];
    					$time = time();
    					$gap = ($k+1) == $able ? '' : ',';
    					$insert .= "($cid,1,$user_id,$time)$gap";
    				}
    			}
    		}else{
    			$able = count($user_id);//本次发送量
    			if($coupon['createnum']>0 && $remain<$able){
    				$this->error($coupon['name'].'派发量只剩'.$remain.'张');
    			}
    			foreach ($user_id as $k=>$v){
    				$time = time();
    				$gap = ($k+1) == $able ? '' : ',';
    				$insert .= "($cid,1,$v,$time)$gap";
    			}
    		}
			$sql = "insert into __PREFIX__coupon_list (`cid`,`type`,`uid`,`send_time`) VALUES $insert";
			M()->execute($sql);
			M('coupon')->where("id=$cid")->setInc('send_num',$able);
			adminLog("发放".$able.'张'.$coupon['name']);
			$this->success("发放成功");
			exit;
    	}
    	$level = M('user_level')->select();
    	$this->assign('level',$level);
    	$this->assign('cid',$cid);
    	$this->display();
    }
    
    public function send_cancel(){
    	
    }

    /*
     * 删除优惠券类型
     */
    public function del_coupon(){
        //获取优惠券ID
        $cid = I('get.id');
        //查询是否存在优惠券
        $row = M('coupon')->where(array('id'=>$cid))->delete();
        if($row){
            //删除此类型下的优惠券
            M('coupon_list')->where(array('cid'=>$cid))->delete();
            $this->success("删除成功");
        }else{
            $this->error("删除失败");
        }
    }


    /*
     * 优惠券详细查看
     */
    public function coupon_list(){
        //获取优惠券ID
        $cid = I('get.id');
        //查询是否存在优惠券
        $check_coupon = M('coupon')->field('id,type')->where(array('id'=>$cid))->find();
        if(!$check_coupon['id'] > 0)
            $this->error('不存在该类型优惠券');
       
        //查询该优惠券的列表的数量
        $sql = "SELECT count(1) as c FROM __PREFIX__coupon_list  l ".
                "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid ". //联合优惠券表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = ".$cid;    //联合用户表去查询用户名        
        
        $count = M()->query($sql);
        $count = $count[0]['c'];
    	$Page = new \Think\Page($count,10);
    	$show = $Page->show();
        
        //查询该优惠券的列表
        $sql = "SELECT l.*,c.name,o.order_sn,u.nickname FROM __PREFIX__coupon_list  l ".
                "LEFT JOIN __PREFIX__coupon c ON c.id = l.cid ". //联合优惠券表查询名称
                "LEFT JOIN __PREFIX__order o ON o.order_id = l.order_id ".     //联合订单表查询订单编号
                "LEFT JOIN __PREFIX__users u ON u.user_id = l.uid WHERE l.cid = ".$cid.    //联合用户表去查询用户名
                " limit {$Page->firstRow} , {$Page->listRows}";
        $coupon_list = M()->query($sql);
        $this->assign('coupon_type',C('COUPON_TYPE'));
        $this->assign('type',$check_coupon['type']);       
        $this->assign('lists',$coupon_list);            	
    	$this->assign('page',$show);        
        $this->display();
    }
    
    /*
     * 删除一张优惠券
     */
    public function coupon_list_del(){
        //获取优惠券ID
        $cid = I('get.id');
        if(!$cid)
            $this->error("缺少参数值");
        //查询是否存在优惠券
         $row = M('coupon_list')->where(array('id'=>$cid))->delete();
        if(!$row)
            $this->error('删除失败');
        $this->success('删除成功');
    }

    /**
     * 礼品兑换券
     */
    public function goods_coupon_list(){
        //获取礼品兑换券列表

        $count =  M('goods_coupon')->count();
        $Page = new \Think\Page($count,10);
        $show = $Page->show();
        $lists = M('goods_coupon')->order('add_time desc')->limit($Page->firstRow.','.$Page->listRows)->select();
        $this->assign('lists',$lists);
        $this->assign('page',$show);// 赋值分页输出
        $this->assign('coupons',C('COUPON_TYPE'));
        $this->display();
    }

    public function goods_coupon_infos(){
        $couponId = I('get.goods_coupon_id');
        if(!$couponId)
            $this->error("缺少参数值");
//        $count =  M('goods_coupon_info')->where("coupon_id=$couponId")->count();
//        $Page = new \Think\Page($count,10);
//        $show = $Page->show();
        $lists = M('goodscoupon_goods gg')
                ->join('tp_goods g on gg.goods_id=g.goods_id')
                ->field("g.goods_name,gg.goods_num,gg.key_name,gg.spec_key")
                ->where("goods_coupon_id=$couponId")
                ->select();
        $coupon = M("goods_coupon")->where("goods_coupon_id=$couponId")->find();
        /*$begin = date('Y/m/d',(time()-30*60*60*24));//30天前
        $end = date('Y/m/d',strtotime('+1 days'));
        $this->assign('timegap',$begin.'-'.$end);*/
        $this->assign('lists',$lists);
        $this->assign('coupon',$coupon);
        $this->display();
    }

    public function ajaxcouponinfos(){
        $timegap = I('timegap');
        if($timegap){
            $gap = explode('-', $timegap);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1]);
        }

        if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
        }
        I('coupon_status') != '' ? $condition['if_use'] = I('coupon_status') : false;
        $condition['goods_coupon_id'] = I('goods_coupon_id');
        $sort_order = "use_date DESC";

        $count =  M('goods_coupon_info')->where($condition)->count();
        $Page  = new AjaxPage($count,8);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =  urlencode($val);
        }
        $show = $Page->show();
        $lists = M('goods_coupon_info')
                ->where($condition)
                ->order($sort_order)
                ->limit($Page->firstRow.','.$Page->listRows)
                ->select();
        foreach ($lists as $k=>$v){
            if($v['if_use']){
                $lists[$k]['nickname'] = M('users')->where("user_id=".$v['user_id'])->getField("nickname");
            }
        }
        $this->assign('list',$lists);
        $this->assign('page',$show);
        $this->display();
//        $orderList = $orderLogic->getOrderList($condition,$sort_order,$Page->firstRow,$Page->listRows);
    }

    /**
     * 添加编辑一个礼品兑换券
     */
    public function goods_coupon_info(){
        if(IS_POST){
            $data = I('post.');
            $data['send_start_time'] = strtotime($data['send_start_time']);
            $data['send_end_time'] = strtotime($data['send_end_time']);
            $data['use_end_time'] = strtotime($data['use_end_time']);
            $data['use_start_time'] = strtotime($data['use_start_time']);
            if($data['send_start_time'] > $data['send_end_time']){
                $this->error('发放日期填写有误');
            }
            $goodsId=$data["goods_id"];
            $goodsNum=$data["goods_num"];
            $specKey=$data["spec_key"];
            unset($data["goods_num"]);
            unset($data["goods_id"]);
            if(empty($data['id'])){
                $data['add_time'] = time();
                $row = M('goods_coupon')->add($data);
                $goods_coupon_id = M('goods_coupon')->getLastInsID();
                $couponNum=$data['coupon_createnum'];
                for ($i=0;$i<$couponNum;$i++){
                    while (true){
                        $couponNo = $this->getRandChar(10);
                        $res = M("goods_coupon_info")->where("coupon_no='$couponNo'")->find();
                        if(!$res) break;
                    }
                    $info[$i]['goods_coupon_id'] = $goods_coupon_id;
                    $info[$i]['coupon_no'] = $couponNo;
                }
                M('goods_coupon_info')->addAll($info);
                foreach ($goodsId as $k=>$v){
                    $goods[$k]["goods_id"] = $v;
                    $goods[$k]["goods_coupon_id"] = $goods_coupon_id;
                    $goods[$k]["goods_num"] = $goodsNum[$k];
                    $goods[$k]["spec_key"] = $specKey[$k];
                    if($specKey[$k]!="") $goods[$k]["key_name"] = M('spec_goods_price')->where("goods_id =$v AND `key`=".$specKey[$k])->getField("key_name");
                    else $goods[$k]["key_name"] ="";
                }
                M('goodscoupon_goods')->addAll($goods);
            }else{
                $row =  M('coupon')->where(array('id'=>$data['id']))->save($data);
            }
            if(!$row)
                $this->error('编辑兑换券失败');
            $this->success('编辑兑换券成功',U('Admin/Coupon/goods_coupon_list'));
            exit;
        }
        $cid = I('get.id');
        if($cid){
            $coupon = M('goods_coupon')->where(array('goods_coupon_id'=>$cid))->find();
            $this->assign('coupon',$coupon);
        }else{
            $def['send_start_time'] = strtotime("+1 day");
            $def['send_end_time'] = strtotime("+1 month");
            $def['use_start_time'] = strtotime("+1 day");
            $def['use_end_time'] = strtotime("+2 month");
            $this->assign('coupon',$def);
        }
        $this->display();
    }

    public function search_goods(){
        $GoodsLogic = new \Admin\Logic\GoodsLogic;
        $brandList = $GoodsLogic->getSortBrands();
        $this->assign('brandList',$brandList);
        $categoryList = $GoodsLogic->getSortCategory();
        $this->assign('categoryList',$categoryList);

        $goods_id = I('goods_id');
        $where = ' is_on_sale = 1 and prom_type=0 and store_count>0 ';//搜索条件
        if(!empty($goods_id)){
            $where .= " and goods_id not in ($goods_id) ";
        }
        I('intro')  && $where = "$where and ".I('intro')." = 1";
        if(I('cat_id')){
            $this->assign('cat_id',I('cat_id'));
            $grandson_ids = getCatGrandson(I('cat_id'));
            $where = " $where  and cat_id in(".  implode(',', $grandson_ids).") "; // 初始化搜索条件
        }
        if(I('brand_id')){
            $this->assign('brand_id',I('brand_id'));
            $where = "$where and brand_id = ".I('brand_id');
        }
        if(!empty($_REQUEST['keywords']))
        {
            $this->assign('keywords',I('keywords'));
            $where = "$where and (goods_name like '%".I('keywords')."%' or keywords like '%".I('keywords')."%')" ;
        }
        $count = M('goods')->where($where)->count();
        $Page  = new \Think\Page($count,10);
        $goodsList = M('goods')->where($where)->order('goods_id DESC')->limit($Page->firstRow.','.$Page->listRows)->select();
        foreach ($goodsList as $k=>$v){
            $goodsList[$k]['spec_list'] = M('spec_goods_price')->where("goods_id =".$v['goods_id'])->getField("key,price,store_count,key_name");
        }
        $show = $Page->show();//分页显示输出
        $this->assign('page',$show);//赋值分页输出
        $this->assign('goodsList',$goodsList);
        $tpl = I('get.tpl','search_goods');
        $this->display($tpl);
    }

    public function getRandChar($length){
        $str = null;
//        $strPol = "ABCDEFGHIJKLMNPQRSTUVWXYZ123456789";
        $strPol = "0123456789";
        $max = strlen($strPol)-1;

        for($i=0;$i<$length;$i++){
            $str.=$strPol[rand(0,$max)];//rand($min,$max)生成介于min和max两个数之间的一个随机整数
        }

        return $str;
    }
}