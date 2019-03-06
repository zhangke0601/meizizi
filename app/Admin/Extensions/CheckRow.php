<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;

class CheckRow
{
    protected $id;

    public function __construct($id)
    {
        $this->id = $id;
    }

    public function getToken()
    {
        return csrf_token();
    }

    protected function script()
    {
        return <<<EOT

$('.grid-check-row').on('click', function () {

    var data = {};
    data.month =  $(this).data('month');
    data.name =  $(this).data('name');
    data.money =  $(this).data('money');
    data.type =  $(this).data('type');
    data.id =  $(this).data('id');
    data._method =  'get';
    swal({
        title: "确定结算么",
        type: "warning",
        showCancelButton: true,
        confirmButtonColor: "#DD6B55",
        confirmButtonText: "确定",
        showLoaderOnConfirm: true,
        cancelButtonText: "取消",
        preConfirm: function() {
            return new Promise(function(resolve) {
                $.ajax({
                    method: 'post',
                    url: '/admin/balance/approval',
                    data: data,
                    success: function (data) {
                        $.pjax.reload('#pjax-container');

                        resolve(data);
                    }
                });
            });
        }
        
    }).then(function(result) {
        var data = result.value;

            if (typeof data === 'object') {
                if (data.code == 1) {
                    swal('申请成功', '', 'success');
                } else {
                    swal('异常操作', '', 'error');
                }
            }        
    });
      
});

EOT;
    }

    protected function render()
    {
        Admin::script($this->script());
//        dump($this->id->month);
//        dump($this->id->$this->id->cartoon_id);

        return "<button class='btn btn-success grid-check-row'  data-month='{$this->id->month}' data-name='{$this->id->name}' data-money='{$this->id->money}' data-type='{$this->id->type}' data-id='{$this->id->id}'>发票已寄出</button>";
    }

    public function __toString()
    {
        return $this->render();
    }
}