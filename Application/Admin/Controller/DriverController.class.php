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
 * Date: 2015-09-09
 */
namespace Admin\Controller;

use Think\AjaxPage;
use Admin\Logic\OrderLogic;
class DriverController extends BaseController {

    public function driver_list(){
        $begin = date('Y/m/d',(time()-30*60*60*24));//30天前
        $end = date('Y/m/d',strtotime('+1 days'));
        $this->assign('timegap',$begin.'-'.$end);
        $this->display();
    }

    /*
     *Ajax列表页
     */
    public function ajaxDriverList(){
        // 搜索条件
        $condition = array();
        $condition['del'] = 1;
        I('mobile') ? $condition['mobile'] = I('mobile') : false;
        I('driver_name') ? $condition['driver_name'] = I('driver_name') : false;
        $sort_order = I('order_by','driver_id').' '.I('sort','desc');

        $model = M('drivers');
        $count = $model->where($condition)->count();
        $Page  = new AjaxPage($count,10);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =   urlencode($val);
        }
//        $userList = $model->where($condition)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
        $driverList = $model->where($condition)->order($sort_order)->limit($Page->firstRow.','.$Page->listRows)->select();
//        $user_id_arr = get_arr_column($userList, 'user_id');

        $show = $Page->show();
        $this->assign('driverList',$driverList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    public function add_driver(){
        if(IS_POST){
            $data = I('post.');
            $res['msg']='';
            if(M('drivers')->where("mobile='".$data['mobile']."'")->count()>0){
                $res['msg'].='手机号码已存在<br />';
            }
            if(M('drivers')->where("driver_no='".$data['driver_no']."'")->count()>0){
                $res['msg'].='司机编码已存在<br />';
            }
            if(M('drivers')->where("car_no='".$data['car_no']."'")->count()>0){
                $res['msg'].='车牌号已存在<br />';
            }

            if($res['msg'] == ''){
                M('drivers')->add($data);
                $this->success('添加成功',U('Driver/driver_list'));exit;
            }else{
                $this->error('添加失败<br />'.$res['msg']);exit;
            }
        }
        $this->display();
    }

    public function modify_driver(){
        if(IS_POST){
            $data = I('post.');
            $res['msg']='';
            if(M('drivers')->where("mobile='".$data['mobile']."' AND driver_id!='".$data['driver_id']."'")->count()>0){
                $res['msg'].='手机号码已存在<br />';
            }
            if(M('drivers')->where("driver_no='".$data['driver_no']."' AND driver_id!='".$data['driver_id']."'")->count()>0){
                $res['msg'].='司机编码已存在<br />';
            }
            if(M('drivers')->where("car_no='".$data['car_no']."' AND driver_id!='".$data['driver_id']."'")->count()>0){
                $res['msg'].='车牌号已存在<br />';
            }
            if($res['msg'] == ''){
                $r = D('drivers')->where('driver_id='.$data['driver_id'])->save($data);
                $this->success('修改成功',U('Driver/driver_list'));exit;
            }else{
                $this->error('修改失败<br />'.$res['msg']);exit;
            }

        }
        $driverId = I('get.driver_id');
        $info = M('drivers')->where("driver_id='".$driverId."'")->find();
        $this->assign('info',$info);
        $this->display();
    }

    public function delete_driver(){
        $data = I('post.');
        D('drivers')->where('driver_id='.$data['driver_id'])->save(array('del'=>0));
        exit(json_encode(1));
    }

    public function orderDriver(){
        $data = I('post.');
        $today = strtotime(date('Y/m/d'));
        if($data['driverId']==""){
            $save['driver_id']=0;
            $save['deliver_opt_time']=0;
            $save['delivery_sort'] = 0;
        }else{
            $save['driver_id']=$data['driverId'];
            $save['deliver_opt_time']=$today;
            $save['delivery_sort'] = M('order')->where("deliver_opt_time=$today AND driver_id=".$data['driverId'])->max('delivery_sort')+1;
        }

        D('order')->where('order_id='.$data['orderId'])->save($save);
        $info = M('drivers')->where("driver_id='".$data['driverId']."'")->find();
        exit(json_encode($info));
    }

    /*
     *Ajax首页
     */
    public function ajaxOrderList(){
        $orderLogic = new OrderLogic();
        $timegap = I('timegap');
        if($timegap){
            $gap = explode('-', $timegap);
            $begin = strtotime($gap[0]);
            $end = strtotime($gap[1]);
        }
        // 搜索条件
        $condition = array();
        I('consignee') ? $condition['consignee'] = trim(I('consignee')) : false;
        if($begin && $end){
            $condition['add_time'] = array('between',"$begin,$end");
        }
        I('order_sn') ? $condition['order_sn'] = trim(I('order_sn')) : false;
        I('order_status') != '' ? $condition['order_status'] = I('order_status') : false;
        I('pay_status') != '' ? $condition['pay_status'] = I('pay_status') : false;
        I('pay_code') != '' ? $condition['pay_code'] = I('pay_code') : false;
        I('shipping_status') != '' ? $condition['shipping_status'] = I('shipping_status') : false;
        I('user_id') ? $condition['user_id'] = trim(I('user_id')) : false;
        $condition['shipping_code'] = 'ziyouwuliu';
        $sort_order = 'o.district DESC';
        $count = M('order')->where($condition)->count();
        $Page  = new AjaxPage($count,20);
        //  搜索条件下 分页赋值
        foreach($condition as $key=>$val) {
            $Page->parameter[$key]   =  urlencode($val);
        }
        $show = $Page->show();
        //获取订单列表
        $orderList = $orderLogic->getOrderList($condition,$sort_order,$Page->firstRow,$Page->listRows);
        foreach ($orderList as $k=>$v){
            $orderList[$k]['product_sum'] = $orderLogic->getOrderGoodsSum($v['order_id'])['sum'];
            $orderList[$k]['action_note'] = $orderLogic->getConfirmNote($v['order_id']);
        }
        $driverList = M('drivers')->where("del=1")->select();
        $this->assign('orderList',$orderList);
        $this->assign('driverList',$driverList);
        $this->assign('page',$show);// 赋值分页输出
        $this->display();
    }

    public function order_list(){
        $begin = date('Y/m/d',(time()-1*60*60*24));//30天前
//        $end = date('Y/m/d',strtotime('+1 days'));
        $end = date('Y/m/d');
        $month = date('Y/m')."/01";
        $this->assign('timegap',$begin.'-'.$end);
        $this->assign('begin',$begin);
        $this->assign('month',$month);
        $this->display();
    }


    public function welcome(){
    	$this->assign('sys_info',$this->get_sys_info());
    	$today = strtotime("-1 day");
    	$count['handle_order'] = M('order')->where("order_status=0 and (pay_status=1 or pay_code='cod')")->count();//待处理订单
    	$count['new_order'] = M('order')->where("add_time>$today")->count();//今天新增订单
    	$count['goods'] =  M('goods')->where("1=1")->count();//商品总数
    	$count['article'] =  M('article')->where("1=1")->count();//文章总数
    	$count['users'] = M('users')->where("1=1")->count();//会员总数
    	$count['today_login'] = M('users')->where("last_login>$today")->count();//今日访问
    	$count['new_users'] = M('users')->where("reg_time>$today")->count();//新增会员
    	$count['comment'] = M('comment')->where("is_show=0")->count();//最新评论
    	$this->assign('count',$count);
        $this->display();
    }

    public function get_sys_info(){
		$sys_info['os']             = PHP_OS;
		$sys_info['zlib']           = function_exists('gzclose') ? 'YES' : 'NO';//zlib
		$sys_info['safe_mode']      = (boolean) ini_get('safe_mode') ? 'YES' : 'NO';//safe_mode = Off		
		$sys_info['timezone']       = function_exists("date_default_timezone_get") ? date_default_timezone_get() : "no_timezone";
		$sys_info['curl']			= function_exists('curl_init') ? 'YES' : 'NO';	
		$sys_info['web_server']     = $_SERVER['SERVER_SOFTWARE'];
		$sys_info['phpv']           = phpversion();
		$sys_info['ip'] 			= GetHostByName($_SERVER['SERVER_NAME']);
		$sys_info['fileupload']     = @ini_get('file_uploads') ? ini_get('upload_max_filesize') :'unknown';
		$sys_info['max_ex_time'] 	= @ini_get("max_execution_time").'s'; //脚本最大执行时间
		$sys_info['set_time_limit'] = function_exists("set_time_limit") ? true : false;
		$sys_info['domain'] 		= $_SERVER['HTTP_HOST'];
		$sys_info['memory_limit']   = ini_get('memory_limit');		
        $sys_info['version']   	    = file_get_contents('./Application/Admin/Conf/version.txt');
		$mysqlinfo = M()->query("SELECT VERSION() as version");
		$sys_info['mysql_version']  = $mysqlinfo['version'];
		if(function_exists("gd_info")){
			$gd = gd_info();
			$sys_info['gdinfo'] 	= $gd['GD Version'];
		}else {
			$sys_info['gdinfo'] 	= "未知";
		}
		return $sys_info;
    }
    
    
    public function pushVersion()
    {            
        if(!empty($_SESSION['isset_push']))
            return false;    
        $_SESSION['isset_push'] = 1;    
        error_reporting(0);//关闭所有错误报告
        $app_path = dirname($_SERVER['SCRIPT_FILENAME']).'/';
        $version_txt_path = $app_path.'/Application/Admin/Conf/version.txt';
        $curent_version = file_get_contents($version_txt_path);

        $vaules = array(            
                'domain'=>$_SERVER['SERVER_NAME'], 
                'last_domain'=>$_SERVER['SERVER_NAME'], 
                'key_num'=>$curent_version, 
                'install_time'=>INSTALL_DATE,
                'serial_number'=>SERIALNUMBER,
         );     
         $url = "http://service.tp-shop.cn/index.php?m=Home&c=Index&a=user_push&".http_build_query($vaules);
         stream_context_set_default(array('http' => array('timeout' => 3)));
         file_get_contents($url);         
    }
    
    /**
     * ajax 修改指定表数据字段  一般修改状态 比如 是否推荐 是否开启 等 图标切换的
     * table,id_name,id_value,field,value
     */
    public function changeTableVal(){  
            $table = I('table'); // 表名
            $id_name = I('id_name'); // 表主键id名
            $id_value = I('id_value'); // 表主键id值
            $field  = I('field'); // 修改哪个字段
            $value  = I('value'); // 修改字段值                        
            M($table)->where("$id_name = $id_value")->save(array($field=>$value)); // 根据条件保存修改的数据
    }	    

}