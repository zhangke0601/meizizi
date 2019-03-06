<?php

use Illuminate\Routing\Router;

Admin::registerAuthRoutes();

Route::group([
    'prefix'        => config('admin.route.prefix'),
    'namespace'     => config('admin.route.namespace'),
    'middleware'    => config('admin.route.middleware'),
], function (Router $router) {

    $router->get('/', 'HomeController@index');
    $router->get('test', 'TestController@index')->name('test.index');
    $router->get('statistics', 'StatisticsController@index')->name('statistics.index');
    $router->get('statistics/prefer', 'StatisticsController@prefer')->name('statistics.prefer');
    $router->resource('pfs', PfController::class);
    $router->resource('copyrights', CopyrightController::class);
    $router->resource('cartoons', CartoonController::class);
    $router->resource('incomes', IncomeController::class);
    $router->resource('mins', MinController::class);
    $router->post('incomes/all', 'IncomeController@all')->name('admin.income.all');
    $router->post('incomes/one', 'IncomeController@one')->name('admin.income.one');
    $router->get('income/changes', 'IncomeController@changes')->name('admin.income.changes');
    $router->post('income/changes', 'IncomeController@changep')->name('admin.income.changep');

    $router->get('income/edits', 'IncomeController@editAll')->name('admin.income.editall');
    $router->post('income/edits', 'IncomeController@storeAll')->name('admin.income.storeAll');
    $router->resource('producers', ProducerController::class);
    $router->resource('partners', PartnerController::class);
    $router->resource('outcomes', OutcomeController::class);
    $router->resource('contracts', ContractController::class);
    $router->resource('accounts', AccountController::class);
    $router->resource('bills', BillController::class);

    $router->get('bill', 'BillController@pfDetail')->name('admin.bill.pf_detail');
    $router->get('account/make', 'AccountController@make');

    $router->put('account/edit', 'AccountController@editOne')->name('admin.account.editOne');
    //申请结算版权方
    $router->get('balance', 'BalanceController@index')->name('balance.index');
    $router->get('balance/approval', 'BalanceController@approval')->name('balance.approval');

    //申请结算制作方
    $router->get('balance/make', 'BalanceController@index2')->name('balance.index2');
    $router->get('balance/pro_approval', 'BalanceController@pro_approval')->name('balance.pro_approval');

    $router->get('account/detail', 'AccountController@dataDetail')->name('account.dataDetail');

});
