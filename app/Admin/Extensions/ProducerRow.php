<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;

class ProducerRow
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    protected function script()
    {
        return <<<SCRIPT

$('.grid-check-row').on('click', function () {

    console.log($(this).data('cartoon_id'));
    console.log($(this).data('month'));
    var data = {};
    data.cartoon_id =  $(this).data('cartoon_id');
    data.month =  $(this).data('month');
    data.bqfcsj =  $(this).data('money');
    data.name =  $(this).data('name');
    
    $.ajax({
            type: "GET",
            url: "/admin/balance/pro_approval",
            data: data,
            contentType: "application/json; charset=utf-8",
            dataType: "json",
            success: function (data) {
                if(data.code == -1){
                   alert('当前日期不能申请结算');
                   window.location.reload();
                }else{
                alert('申请成功');
                window.location.reload();}
            },
            error: function (msg) {
                alert(msg);
            }
        });
      
});

SCRIPT;
    }

    protected function render()
    {
        Admin::script($this->script());
//        dump($this->id->month);
//        dump($this->id->$this->id->cartoon_id);

        return "<button class='btn btn-success grid-check-row' data-cartoon_id='{$this->id->cartoon_id}' data-month='{$this->id->month}' data-name='{$this->id->producer_name}' data-money='{$this->id->bqfcsj}'>去申请结算</button>";
    }

    public function __toString()
    {
        return $this->render();
    }
}