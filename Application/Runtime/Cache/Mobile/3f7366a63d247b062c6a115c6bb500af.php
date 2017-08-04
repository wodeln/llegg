<?php if (!defined('THINK_PATH')) exit();?><!DOCTYPE html >
<html>
<head>
<meta name="Generator" content="tpshop" />
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width">
<title>地址列表-<?php echo ($tpshop_config['shop_info_store_title']); ?></title>
<meta http-equiv="keywords" content="<?php echo ($tpshop_config['shop_info_store_keyword']); ?>" />
<meta name="description" content="<?php echo ($tpshop_config['shop_info_store_desc']); ?>" />
<meta name="viewport" content="initial-scale=1.0, maximum-scale=1.0, user-scalable=0"/>
<link rel="stylesheet" href="/Template/mobile/new/Static/css/public.css">
<link rel="stylesheet" href="/Template/mobile/new/Static/css/user.css">

<script type="text/javascript" src="/Template/mobile/new/Static/js/jquery.js"></script>
<script src="/Public/js/global.js"></script>
<script src="/Public/js/mobile_common.js"></script>
</head>
<body>
      <header>
      <div class="tab_nav">
        <div class="header">
          <div class="h-left"><a class="sb-back" href="javascript:history.back(-1)" title="返回"></a></div>
          <div class="h-mid">地址管理</div>
          <div class="h-right">
            <aside class="top_bar">
              <div onClick="show_menu();$('#close_btn').addClass('hid');" id="show_more"><a href="javascript:;"></a> </div>
            </aside>
          </div>
        </div>
      </div>
      </header>
<script type="text/javascript" src="/Template/mobile/new/Static/js/mobile.js" ></script>
<div class="goods_nav hid" id="menu">
      <div class="Triangle">
        <h2></h2>
      </div>
      <ul>
        <li><a href="<?php echo U('Index/index');?>"><span class="menu1"></span><i>首页</i></a></li>
        <li><a href="<?php echo U('Goods/categoryList');?>"><span class="menu2"></span><i>分类</i></a></li>
        <li><a href="<?php echo U('Cart/cart');?>"><span class="menu3"></span><i>购物车</i></a></li>
        <li style=" border:0;"><a href="<?php echo U('User/index');?>"><span class="menu4"></span><i>我的</i></a></li>
   </ul>
 </div> 
									
<div id="tbh5v0">    
  <?php if(is_array($lists)): $i = 0; $__LIST__ = $lists;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$list): $mod = ($i % 2 );++$i;?><div class="address_add   <?php if($list[is_default] == 1): ?>Default<?php endif; ?>">
                
             <?php if($_GET['source'] == 'cart2'): ?><a href="<?php echo U('/Mobile/Cart/cart2',array('address_id'=>$list['address_id']));?>">
                        <?php if($list[is_default] == 1): ?><h2><img src="/Template/mobile/new/Static/images/right.png"></h2><?php endif; ?>
                        <dl>
                            <dt><span><?php echo ($list["consignee"]); ?></span><em>电话:<?php echo ($list["mobile"]); ?></em></dt>
                            <dd><?php echo ($region_list[$list['province']]['name']); ?>，<?php echo ($region_list[$list['city']]['name']); ?>，<?php echo ($region_list[$list['district']]['name']); ?>，<?php echo ($region_list[$list['twon']]['name']); ?>，<?php echo ($region_list[$list['village']]['name']); ?>，<?php echo ($list["address"]); ?></dd>
                        </dl>
                    </a>         
             <?php else: ?>
                    <?php if($list[is_default] == 1): ?><h2><img src="/Template/mobile/new/Static/images/right.png"></h2><?php endif; ?>
                    <dl>
                        <dt><span><?php echo ($list["consignee"]); ?></span><em>电话:<?php echo ($list["mobile"]); ?></em></dt>
                        <dd><?php echo ($region_list[$list['province']]['name']); ?>，<?php echo ($region_list[$list['city']]['name']); ?>，<?php echo ($region_list[$list['district']]['name']); ?>，<?php echo ($region_list[$list['twon']]['name']); ?>，<?php echo ($region_list[$list['village']]['name']); ?>，<?php echo ($list["address"]); ?></dd>
                     </dl><?php endif; ?>                 
                       
             <a href="<?php echo U('/Mobile/User/edit_address',array('id'=>$list[address_id],'source'=>$_GET['source']));?>"><img id="amend" src="/Template/mobile/new/Static/images/amend.png"/></a>
        </div><?php endforeach; endif; else: echo "" ;endif; ?> 
</div>

<div style=" width:100%; height:50px;"></div>
<div class="list_footer">
	<a href="<?php echo U('/Mobile/User/add_address',array('source'=>$_GET['source']));?>">添加新地址</a>
</div>

<script>
function goTop(){
	$('html,body').animate({'scrollTop':0},600);
}
</script>
<a href="javascript:goTop();" class="gotop"><img src="/Template/mobile/new/Static/images/topup.png"></a>		
</div>

 
</body>
</html>