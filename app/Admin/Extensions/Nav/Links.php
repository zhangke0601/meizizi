<?php

namespace App\Admin\Extensions\Nav;

use Illuminate\Support\Facades\DB;

class Links
{
    public function __toString()
    {
        $sql = 'select pfdatas.month,pfdatas.id,cartoons.cartoon_name,pfs.pfname from pfdatas LEFT JOIN cartoons on cartoons.id=pfdatas.cartoon_id LEFT JOIN pfs on pfs.id=pfdatas.pf_id where (pfdatas.plan_money > 0 or pfdatas.infact_money > 0) and pfdatas.pf_id in (select id from pfs where type = 2)';
        $datas = DB::select($sql);
        $count = count($datas);
        $infoList = $this->liHtml($datas);

        return <<<HTML
        
<li class="dropdown notifications-menu">
<a href="#" class="dropdown-toggle" data-toggle="dropdown" aria-expanded="false">
  <i class="fa fa-bell-o"></i>
  <span class="label label-warning">{$count}</span>
</a>
<ul class="dropdown-menu" style="width: 340px !important;">
  <li class="header">您有{$count}条未读消息</li>
  <li>
    <!-- inner menu: contains the actual data -->
    <ul class="menu">
      
      
      {$infoList}
      
    </ul>
  </li>
  <!--<li class="footer"><a href="#">查看全部</a></li>-->
</ul>
</li>
HTML;
    }

    private function liHtml($datas)
    {
        $html = '';
        foreach ($datas as $data) {
            $html .= ('<li><a href="#"><i class="fa fa-warning text-yellow"></i>'.$data->month.$data->cartoon_name.'渠道'.$data->pfname.'的数据存在异常</a></li>');
        }

        return $html;
    }
}