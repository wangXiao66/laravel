<?php

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return view('welcome');
});

Route::domain('index.hky.com')->group(function (){
    Route::get('test/admin','TestController@admin');
     Route::get('test/error_upd','TestController@error_upd');
     Route::get('test/v_unlock','TestController@v_unlock');
     
});

Route::get('login', function () {
    return view('login');
});
Route::get('authion/admin/add_authion_goods','Authion\Admin@add_authion_goods');    //添加竞拍商品
Route::get('authion/admin/add_do_authion_goods','Authion\Admin@add_do_authion_goods'); 

Route::get('authion/index/index','Authion\Index@index'); //竞拍商品展示
Route::get('authion/index/join_auction/{id}','Authion\Index@join_auction');    //参与竞拍
Route::get('authion/index/add_join_auction','Authion\Index@add_join_auction');  
Route::get('authion/index/join_auction_list','Authion\Index@join_auction_list'); 
Route::get('authion/index/record/{id}','Authion\Index@record');




Route::get('login/login','LoginController@login');
Route::post('login/login_do','LoginController@login_do');
Route::get('login/check','LoginController@check')->middleware('checklogin');
//微信扫码登录
Route::any('wechat/index','LoginController@index');
Route::any('login/checkWechatLogin','LoginController@checkWechatLogin');
//扣扣登录










