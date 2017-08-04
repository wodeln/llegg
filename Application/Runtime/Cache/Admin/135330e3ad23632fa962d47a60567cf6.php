<?php if (!defined('THINK_PATH')) exit();?><style type="text/css">
    <!--
    .paper {
        width: 217mm;
        padding: 0mm;
        font-size:11pt;
        background: #ffffff;
    }
    .stick {
        position:relative;
        float:left;
        width: 217mm;
        height: 140mm;
        padding: 2mm 0 0 2mm;
        background: #ffffff;
    }
    .stick .qrcode img{
        position: absolute;
        width: 100px;
    }
    .stick .title{
        position: absolute;
        font-size: 40pt;
        top: 2mm;
        left: 60mm;
    }
    .stick .print_time{
        position: absolute;
        top: 15mm;
        left: 155mm;
    }
    .stick .cut-off{
        position: absolute;
        width: 70%;
        border-bottom: 1px dashed black;
        top: 25mm;
        left: 30mm;

    }
    .stick .info_left{
        position: absolute;
        width: 60%;
        top: 30mm;
    }
    .stick .info_right{
        position: absolute;
        width: 28%;
        top: 30mm;
        right: 0mm;
    }
    .stick .product{
        position: absolute;
        width: 97%;
        top: 55mm;
    }
    .stick .product table{
        width: 100%;
    }
    .stick .total{
        width: 20%;
        float: right;
        margin-right: 0mm;
    }
    .stick .bottom_right{
        position: absolute;
        width: 27%;
        top:120mm;
        right: 0mm;
    }
    .stick .bottom_left{
        position: absolute;
        width: 60%;
        top: 102mm;
    }
    .stick .bottom_left .money{
        margin-bottom: 5mm;
    }
    .stick .bottom_left .money span{
        margin-right: 10mm;
        display: inline-block;
        width: 20mm;
    }
    .stick .bottom_left .underline{
        margin-bottom: 3mm;
    }
    .stick .bottom_left .underline span{
        display: inline-block;
        margin-right: 14mm;
        width: 16mm;
        border-bottom: 1px solid black;
    }
    .stick .memo{
        margin-top:1mm;
    }
    -->
</style>
<div class="paper">
    <?php if(is_array($orderList)): $i = 0; $__LIST__ = $orderList;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$list): $mod = ($i % 2 );++$i;?><div class="stick">
        <div class="top">
            <span class="qrcode"><img src="/Public/images/qrcode.jpg"></span>
            <span class="title">龙龙蛋出库单</span>
            <span class="print_time">打印时间：<?php echo (date('Y-m-d H:i:s',$now_time)); ?></span>
            <span class="cut-off"></span>
        </div>
        <div class="info_left">
            <div class="customer">客户名称: <?php echo ($list["nickname"]); ?></div>
            <div class="address">收货地址：<?php echo ($list["district"]); echo ($list["address"]); ?></div>
            <div class="get_name">收货人: <?php echo ($list["consignee"]); ?></div>
            <dvi class="mobile">联系电话: <?php echo ($list["mobile"]); ?></dvi>
        </div>
        <div class="info_right">
            <div class="customer">订单号: <?php echo ($list["order_sn"]); ?></div>
            <div class="customer">下单时间: <?php echo (date('Y-m-d H:i',$list["add_time"])); ?> </div>
            <div class="memo">
                <?php echo ($list["user_note"]); ?>
            </div>
        </div>
        <div class="product">
            <table border="1">
                <tr>
                    <td>NO</td>
                    <td>品名</td>
                    <td>规格</td>
                    <td>数量</td>
                    <td>单价</td>
                    <td>总价</td>
                    <td>备注</td>
                </tr>
                <?php if(is_array($list['products'])): $k = 0; $__LIST__ = $list['products'];if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$pro): $mod = ($k % 2 );++$k;?><tr>
                        <td><?php echo ($k); ?></td>
                        <td><?php echo ($pro["goods_name"]); ?></td>
                        <td><?php echo ($pro["spec_key_name"]); ?></td>
                        <td><?php echo ($pro["goods_num"]); ?></td>
                        <td><?php echo ($pro["member_goods_price"]); ?></td>
                        <td><?php echo ($pro["goods_total"]); ?></td>
                        <td></td>
                    </tr><?php endforeach; endif; else: echo "" ;endif; ?>
            </table>

            <div class="total">
                <div>运费：<?php echo ($list["shipping_price"]); ?></div>
                <div>总价：<?php echo ($list["total_amount"]); ?></div>
            </div>
        </div>
        <div class="bottom_right">
            <div class="company">上海夏实信息科技有限公司</div>
            <div class="company_address">地址：上海市青浦区蔡家路88号</div>
            <div class="company_phone">订购电话：15317003665</div>
        </div>
        <div class="bottom_left">
            <div class="money"><span>专用框(只)</span><span>杂框(只)</span><span>蛋液(斤)</span><span>实收金额</span></div>
            <div class="underline"><span></span><span></span><span></span><span></span></div>
            <div class="money"><span>收货人</span><span>送货人</span></div>
            <div class="underline"><span></span><span></span></div>
        </div>
    </div><?php endforeach; endif; else: echo "" ;endif; ?>
</div>
<script>
    $(".pagination  a").click(function(){
        var page = $(this).data('p');
        ajax_get_table('search-form2',page);
    });
</script>