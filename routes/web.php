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

//Route::get('admin/login', 'Admin\LoginController@showLoginForm')->name('admin.login');
//Route::post('admin/login', 'Admin\LoginController@login');
//Route::get('admin/register', 'Admin\RegisterController@showRegistrationForm')->name('admin.register');
//Route::post('admin/register', 'Admin\RegisterController@register');
//Route::post('admin/logout', 'Admin\LoginController@logout')->name('admin.logout');
//Route::get('admin', 'AdminController@index')->name('admin.home');
//Route::any('admin/user', 'AdminController@user')->name('admin.user');
Auth::routes();
Route::get('/home', 'HomeController@index')->name('home');

Route::get('user/login', function () {
    $user = session('wechat.oauth_user.default');
   print_r($user);
})->middleware('wechat.oauth');
//后台管理系统路由
Route::any('admin/loginCenter', 'AdminController@login');
Route::group(['middleware' => ['api', 'multiauth:admin',]], function () {
    Route::any('admin/user', 'Admin\UserController@user');
    Route::any('admin/menu', 'Admin\UserController@menu');
    Route::any('user/updatePassword', 'Admin\UserController@updatePassword');
    Route::group(['middleware' => ['permission']], function () {
        Route::get('menu/index', 'Admin\MenuController@index');
        Route::post('menu/create', 'Admin\MenuController@create');
        Route::post('menu/update', 'Admin\MenuController@update');
        Route::get('menu/delete', 'Admin\MenuController@delete');

        Route::get('role/index', 'Admin\RoleController@index');
        Route::post('role/create', 'Admin\RoleController@create');
        Route::post('role/update', 'Admin\RoleController@update');
        Route::get('role/delete', 'Admin\RoleController@delete');
        Route::get('role/permission', 'Admin\RoleController@getPermission');
        Route::post('role/setPermission', 'Admin\RoleController@setPermission');
        Route::get('role/getUsers', 'Admin\RoleController@getUsers');
        Route::post('role/bindUsers', 'Admin\RoleController@bindUsers');

        Route::get('user/index', 'Admin\UserController@index');
        Route::get('user/getRoles', 'Admin\UserController@getRoles');
        Route::post('user/create', 'Admin\UserController@create');
        Route::post('user/update', 'Admin\UserController@update');
        Route::get('user/updateStatus', 'Admin\UserController@updateStatus');
        Route::get('user/permission', 'Admin\UserController@getPermission');
        Route::post('user/setPermission', 'Admin\UserController@setPermission');
    });
});

