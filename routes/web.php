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

Route::get('admin/login', 'Admin\LoginController@showLoginForm')->name('admin.login');
Route::post('admin/login', 'Admin\LoginController@login');
Route::get('admin/register', 'Admin\RegisterController@showRegistrationForm')->name('admin.register');
Route::post('admin/register', 'Admin\RegisterController@register');
Route::post('admin/logout', 'Admin\LoginController@logout')->name('admin.logout');
Route::get('admin', 'AdminController@index')->name('admin.home');
Route::any('admin/user', 'AdminController@user')->name('admin.user');
Auth::routes();
Route::get('/home', 'HomeController@index')->name('home');


//后台管理系统路由
Route::any('admin/getToken', 'AdminController@getToken');
Route::group(['middleware' => ['api', 'multiauth:admin']], function () {

    Route::any('admin/user', 'Admin\UserController@user');
    Route::any('admin/menu', 'Admin\UserController@menu');

    Route::get('menu/index', 'Admin\MenuController@index');
    Route::get('menu/detail', 'Admin\MenuController@detail');
    Route::post('menu/create', 'Admin\MenuController@create');
    Route::post('menu/update', 'Admin\MenuController@update');
    Route::get('menu/delete', 'Admin\MenuController@delete');

    Route::get('role/index', 'Admin\RoleController@index');
    Route::post('role/create', 'Admin\RoleController@create');
    Route::post('role/update', 'Admin\RoleController@update');
    Route::get('role/delete', 'Admin\RoleController@delete');

    Route::get('user/index', 'Admin\UserController@index');



});

