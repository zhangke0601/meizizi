<?php

namespace App\Admin\Extensions;

use Encore\Admin\Admin;

class BalanceButton
{
    protected $bills;

    public function __construct($bills)
    {
        $this->bills = $bills;
    }

    public function getToken()
    {
        return csrf_token();
    }

    protected function script()
    {
        return <<<EOT

$('.grid-balance-button').on('click', function () {

    var data = {};
    data.id =  $(this).data('id');
    data._method = 'get';
    swal({
        title: "我们会在收到发票后15个工作日内打款",
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

        return "<button class='btn btn-default grid-balance-button' data-id='{$this->bills->id}'>申请结算（上季度）</button>";
    }

    public function __toString()
    {
        return $this->render();
    }
}