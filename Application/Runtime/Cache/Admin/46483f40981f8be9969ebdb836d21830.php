<?php if (!defined('THINK_PATH')) exit();?><style type="text/css">
    <!--
    .paper {
        padding: 0mm 0mm 0mm 5mm;
        font-size:11pt;
    }
    .paper table tr td{
        border: 1px solid black;
    }
    .paper td{
        heith:10mm;
        line-height: 10mm;
        padding-left: 5mm;
        padding-right: 10mm;
    }
    .paper .start_end{
        margin-bottom: 5mm;
    }
    -->
</style>
<div class="paper">
    <div class="start_end"><?php echo ($start_end); ?></div>
    <table>
        <tr>
            <td>品名</td>
            <td>货号</td>
            <td>规格</td>
            <td>数量</td>
        </tr>
        <?php if(is_array($goods)): $i = 0; $__LIST__ = $goods;if( count($__LIST__)==0 ) : echo "" ;else: foreach($__LIST__ as $key=>$list): $mod = ($i % 2 );++$i;?><tr>
            <td><?php echo ($list["goods_name"]); ?></td>
            <td><?php echo ($list["goods_sn"]); ?></td>
            <td><?php echo ($list["spec_key_name"]); ?></td>
            <td><?php echo ($list["total"]); ?></td>
        </tr><?php endforeach; endif; else: echo "" ;endif; ?>
    </table>
</div>