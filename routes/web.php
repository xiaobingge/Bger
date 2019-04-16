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


Route::group(['middleware' => ['api', 'multiauth:admin']], function () {
    Route::any('admin/user', 'AdminController@user');
    Route::any('admin/menu', 'AdminController@menu');
    Route::get('menu/index', 'Admin\MenuController@index');
    Route::get('menu/detail', 'Admin\MenuController@detail');
    Route::post('menu/create', 'Admin\MenuController@create');
    Route::post('menu/update', 'Admin\MenuController@update');
    Route::post('menu/delete', 'Admin\MenuController@delete');

});

