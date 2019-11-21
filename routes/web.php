<?php
use Illuminate\Http\Request;
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

//Auth::routes();

Route::get('/home', 'HomeController@index')->name('home');

//上传处理
Route::group(['middleware' => ['cors']], function () {
    Route::post('/uploadFiles', function(\App\Services\UploadService $service){
        return $service->uploadFiles();
    });
});

//后台管理系统路由
Route::any('admin/loginCenter', 'Admin\LoginController@login');
Route::group(['namespace' => 'Admin'], function () {
    Route::group(['middleware' => ['api', 'multiauth:admin']], function () {
        Route::any('admin/user', 'UserController@user');
        Route::any('admin/menu', 'UserController@menu');
        Route::any('user/updatePassword', 'UserController@updatePassword');
        Route::group(['middleware' => ['permission']], function () {
            //菜单管理
            Route::get('menu/index', 'MenuController@index');
            Route::post('menu/create', 'MenuController@create');
            Route::post('menu/update', 'MenuController@update');
            Route::get('menu/delete', 'MenuController@delete');

            //角色管理
            Route::get('role/index', 'RoleController@index');
            Route::post('role/create', 'RoleController@create');
            Route::post('role/update', 'RoleController@update');
            Route::get('role/delete', 'RoleController@delete');
            Route::get('role/permission', 'RoleController@getPermission');
            Route::post('role/setPermission', 'RoleController@setPermission');
            Route::get('role/getUsers', 'RoleController@getUsers');
            Route::post('role/bindUsers', 'RoleController@bindUsers');

            //后台用户
            Route::get('user/index', 'UserController@index');
            Route::get('user/getRoles', 'UserController@getRoles');
            Route::post('user/create', 'UserController@create');
            Route::post('user/update', 'UserController@update');
            Route::get('user/updateStatus', 'UserController@updateStatus');
            Route::get('user/permission', 'UserController@getPermission');
            Route::post('user/setPermission', 'UserController@setPermission');
        });
    });
});




